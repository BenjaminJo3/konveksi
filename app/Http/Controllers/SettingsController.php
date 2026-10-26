<?php

namespace App\Http\Controllers;

use App\Services\DatabaseResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(DatabaseResetService $reset): View
    {
        return view('admin.settings', [
            'counts' => $reset->counts(auth()->id()),
            'databaseName' => DB::connection()->getDatabaseName(),
            'driver' => DB::connection()->getDriverName(),
        ]);
    }

    public function truncate(Request $request, DatabaseResetService $reset): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $values = $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', 'string', 'in:HAPUS SEMUA DATA'],
            'acknowledge_backup' => ['accepted'],
        ]);

        $deletedCounts = $reset->reset(
            $request->user(),
            (string) $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Data aplikasi berhasil dihapus. Akun Super Admin yang sedang digunakan tetap aktif.')
            ->with('deleted_counts', $deletedCounts);
    }
}
