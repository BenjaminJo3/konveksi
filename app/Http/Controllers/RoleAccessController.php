<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleAccessController extends Controller
{
    public function index(RolePermissionService $permissions): View
    {
        abort_unless(auth()->user()->canPermission('manage.roles'), 403);

        return view('admin.role-access', [
            'roles' => $permissions->roleList(),
            'permissions' => $permissions->matrix(),
            'canEdit' => auth()->user()->role === 'super_admin',
        ]);
    }

    public function update(Request $request, RolePermissionService $permissions): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $validKeys = collect($permissions->definitions())->pluck('key')->all();
        $validRoles = array_diff(array_column($permissions->roleList(), 'key'), ['super_admin']);
        $submitted = $request->validate(['permissions' => ['required', 'array']])['permissions'];
        foreach ($submitted as $permissionKey => $roles) {
            if ($permissionKey === '_present') {
                continue;
            }
            abort_unless(in_array($permissionKey, $validKeys, true) && is_array($roles), 422);
            foreach ($roles as $role => $allowed) {
                abort_unless(
                    in_array($role, $validRoles, true) && in_array($allowed, ['1', 1, true], true),
                    422,
                );
            }
        }

        $before = $permissions->matrix();
        $permissions->update($submitted, $request->user()->id);
        $after = $permissions->matrix();

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'role_permissions_updated',
            'subject_type' => 'role_permissions',
            'description' => 'Matriks Role & Akses diperbarui oleh Super Admin.',
            'metadata' => ['before' => $before, 'after' => $after],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.role-access.index')->with('success', 'Hak akses role berhasil diperbarui dan langsung diterapkan.');
    }
}
