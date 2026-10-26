<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const ROLES = ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang'];

    public function index(Request $request): View
    {
        $roles = $this->rolesFor($request);

        return view('admin.users.index', [
            'users' => User::query()
                ->with('branch')
                ->when($request->user()->role !== 'super_admin', fn ($query) => $query->where('role', '!=', 'super_admin'))
                ->orderBy('name')
                ->paginate(20),
            'branches' => Branch::query()->orderBy('name')->get(),
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($this->rolesFor($request))],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $values['name'],
            'email' => $values['email'],
            'role' => $values['role'],
            'branch_id' => $values['branch_id'] ?? null,
            'password' => Hash::make($values['password']),
        ]);
        $this->recordAudit($request, 'user_created', $user, 'Pengguna '.$user->email.' dibuat.');

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->assertCanManageTarget($request, $user);
        $values = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in($this->rolesFor($request))],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);
        $protectedRoles = ['admin' => 'Admin terakhir', 'super_admin' => 'Super Admin terakhir'];
        foreach ($protectedRoles as $protectedRole => $description) {
            abort_if(
                $user->role === $protectedRole
                    && $values['role'] !== $protectedRole
                    && User::query()->where('role', $protectedRole)->count() <= 1,
                422,
                $description.' tidak dapat diubah perannya.',
            );
        }
        $before = ['role' => $user->role, 'branch_id' => $user->branch_id, 'email' => $user->email];

        $user->fill([
            'name' => $values['name'],
            'email' => $values['email'],
            'role' => $values['role'],
            'branch_id' => $values['branch_id'] ?? null,
        ]);
        if (filled($values['password'] ?? null)) {
            $user->password = Hash::make($values['password']);
        }
        $user->save();

        $this->recordAudit($request, 'user_updated', $user, 'Data pengguna '.$user->email.' diperbarui.', [
            'before' => $before,
            'after' => ['role' => $user->role, 'branch_id' => $user->branch_id, 'email' => $user->email],
            'password_reset' => filled($values['password'] ?? null),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->assertCanManageTarget($request, $user);
        abort_if($request->user()->is($user), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        abort_if($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1, 422, 'Admin terakhir tidak dapat dihapus.');
        abort_if($user->role === 'super_admin' && User::query()->where('role', 'super_admin')->count() <= 1, 422, 'Super Admin terakhir tidak dapat dihapus.');

        $email = $user->email;
        $user->delete();
        $this->recordAudit($request, 'user_deleted', $user, 'Pengguna '.$email.' dihapus.', ['email' => $email]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    private function rolesFor(Request $request): array
    {
        return $request->user()->role === 'super_admin'
            ? self::ROLES
            : array_values(array_diff(self::ROLES, ['super_admin']));
    }

    private function assertCanManageTarget(Request $request, User $user): void
    {
        abort_if($user->role === 'super_admin' && $request->user()->role !== 'super_admin', 403);
    }

    private function recordAudit(Request $request, string $event, User $subject, string $description, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => $event,
            'subject_type' => 'user',
            'subject_id' => (string) $subject->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
