<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    public function test_admin_can_create_and_download_an_integrity_checked_sqlite_backup(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'ruang-produksi-test-');
        $backupPath = null;

        try {
            config(['database.connections.sqlite.database' => $databasePath]);
            DB::purge('sqlite');
            DB::connection()->getPdo()->exec(
                'CREATE TABLE audit_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NULL,
                    event VARCHAR(100) NOT NULL,
                    subject_type VARCHAR(120) NULL,
                    subject_id VARCHAR(120) NULL,
                    description TEXT NOT NULL,
                    metadata TEXT NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )'
            );
            DB::connection()->getPdo()->exec(
                'CREATE TABLE role_permissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    role VARCHAR(40) NOT NULL,
                    permission_key VARCHAR(80) NOT NULL,
                    allowed BOOLEAN NOT NULL DEFAULT 0,
                    updated_by INTEGER NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    UNIQUE (role, permission_key)
                )'
            );

            $response = $this->actingAs(User::factory()->make(['id' => 1, 'role' => 'admin']))
                ->post(route('admin.backup.create'));

            $response->assertOk()
                ->assertHeader('content-type', 'application/vnd.sqlite3');
            $backupPath = $response->baseResponse->getFile()->getPathname();
            $this->assertFileExists($backupPath);

            $backup = new PDO('sqlite:'.$backupPath);
            $this->assertSame('ok', $backup->query('PRAGMA integrity_check')->fetchColumn());
            $this->assertSame(0, (int) $backup->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn());
            $backup = null;

            $this->assertSame(
                'database_backup_created',
                DB::table('audit_logs')->value('event'),
            );
        } finally {
            DB::purge('sqlite');
            config(['database.connections.sqlite.database' => ':memory:']);

            foreach ([$backupPath, $databasePath, $databasePath.'-wal', $databasePath.'-shm'] as $path) {
                if (is_string($path) && is_file($path)) {
                    unlink($path);
                }
            }
        }
    }
}
