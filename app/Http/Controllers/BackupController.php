<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function index(): View
    {
        $disk = Storage::disk('local');
        $backupFiles = $disk->directoryExists('backups') ? $disk->files('backups') : [];
        $backups = collect($backupFiles)
            ->filter(fn (string $path): bool => preg_match('/\.(?:sqlite|sql)$/', $path) === 1)
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'size' => $disk->size($path),
                'modified' => $disk->lastModified($path),
            ])
            ->sortByDesc('modified')
            ->values();

        return view('admin.backup', [
            'backups' => $backups,
            'driver' => DB::connection()->getDriverName(),
            'databaseName' => DB::connection()->getDatabaseName(),
        ]);
    }

    public function create(Request $request): BinaryFileResponse|RedirectResponse
    {
        $directory = Storage::disk('local')->path('backups');
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            abort(500, 'Folder backup tidak dapat dibuat.');
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        abort_unless(in_array($driver, ['sqlite', 'mysql'], true), 422, 'Backup otomatis hanya didukung untuk SQLite dan MySQL.');

        $databaseName = $connection->getDatabaseName();
        $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $filename = sprintf('ruang-produksi-%s-%s.%s', now()->format('Ymd-His'), bin2hex(random_bytes(3)), $extension);
        $backupPath = $directory.DIRECTORY_SEPARATOR.$filename;
        if ($driver === 'sqlite') {
            abort_if($databaseName === ':memory:' || ! is_file($databaseName), 422, 'Database SQLite aktif tidak tersedia sebagai file untuk dibuatkan backup.');

            $pdo = $connection->getPdo();
            $pdo->exec('PRAGMA wal_checkpoint(FULL)');
            $pdo->exec('VACUUM INTO '.$pdo->quote($backupPath));
        } else {
            $dumpPath = config('database.connections.mysql.mysqldump_path')
                ?: (new ExecutableFinder)->find('mysqldump');
            abort_if(! is_string($dumpPath) || ! is_file($dumpPath), 500, 'Program mysqldump tidak ditemukan. Atur MYSQLDUMP_PATH pada konfigurasi aplikasi.');

            $mysql = config('database.connections.mysql');
            $process = new Process([
                $dumpPath,
                '--single-transaction',
                '--quick',
                '--host='.$mysql['host'],
                '--port='.$mysql['port'],
                '--user='.$mysql['username'],
                '--default-character-set='.$mysql['charset'],
                '--result-file='.$backupPath,
                $databaseName,
            ]);
            $process->setTimeout(300);
            $process->setEnv(['MYSQL_PWD' => (string) ($mysql['password'] ?? '')]);
            $process->run();
            if (! $process->isSuccessful()) {
                if (is_file($backupPath)) {
                    unlink($backupPath);
                }

                abort(500, 'Backup MySQL gagal: '.trim($process->getErrorOutput()));
            }
        }

        if (! is_file($backupPath) || filesize($backupPath) === 0) {
            abort(500, 'File backup tidak terbentuk dengan benar.');
        }

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'database_backup_created',
            'subject_type' => 'database_backup',
            'subject_id' => $filename,
            'description' => 'Membuat dan mengunduh backup database '.strtoupper($driver).'.',
            'metadata' => [
                'file_name' => $filename,
                'file_size' => filesize($backupPath),
                'database' => basename($databaseName),
                'driver' => $driver,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->download($backupPath, $filename, [
            'Content-Type' => $driver === 'sqlite' ? 'application/vnd.sqlite3' : 'application/sql',
        ]);
    }

    public function download(string $filename): BinaryFileResponse
    {
        abort_unless(preg_match('/^ruang-produksi-\d{8}-\d{6}-[a-f0-9]{6}\.(?:sqlite|sql)$/', $filename) === 1, 404);

        $path = Storage::disk('local')->path('backups'.DIRECTORY_SEPARATOR.$filename);
        abort_unless(is_file($path), 404);

        return response()->download($path, $filename, [
            'Content-Type' => str_ends_with($filename, '.sqlite') ? 'application/vnd.sqlite3' : 'application/sql',
        ]);
    }
}
