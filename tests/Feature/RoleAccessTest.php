<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_edit_role_access_and_changes_are_enforced_on_routes(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $permissions = app(RolePermissionService::class)->definitions();
        $payload = ['permissions' => ['_present' => '1']];
        foreach ($permissions as $permission) {
            $payload['permissions'][$permission['key']] = array_fill_keys(
                array_values(array_diff($permission['default'], ['super_admin'])),
                '1',
            );
        }
        unset($payload['permissions']['manage.master_data']['admin']);

        $this->actingAs($superAdmin)
            ->get(route('admin.role-access.index'))
            ->assertOk()
            ->assertSee('Simpan hak akses')
            ->assertSee('permissions[orders.create][admin]', false);

        $this->put(route('admin.role-access.update'), $payload)
            ->assertRedirect(route('admin.role-access.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'admin',
            'permission_key' => 'manage.master_data',
            'allowed' => false,
            'updated_by' => $superAdmin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'role_permissions_updated']);

        $this->actingAs($admin)
            ->get(route('master-data.products'))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('master-data.products'))
            ->assertOk();
    }

    public function test_super_admin_permissions_are_immutable_and_regular_admin_cannot_edit_matrix(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $permissions = app(RolePermissionService::class)->definitions();
        $payload = ['permissions' => ['_present' => '1']];
        foreach ($permissions as $permission) {
            $payload['permissions'][$permission['key']] = array_fill_keys(
                array_values(array_diff($permission['default'], ['super_admin'])),
                '1',
            );
        }

        $this->actingAs($admin)
            ->get(route('admin.role-access.index'))
            ->assertOk()
            ->assertDontSee('Simpan hak akses');

        $this->put(route('admin.role-access.update'), $payload)->assertForbidden();

        $this->actingAs($superAdmin)
            ->put(route('admin.role-access.update'), $payload)
            ->assertRedirect();

        $this->assertTrue($superAdmin->canPermission('manage.users'));
        $this->assertTrue($superAdmin->canPermission('manage.master_data'));
    }

    public function test_super_admin_can_grant_a_module_to_another_role(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $mydent = User::factory()->create(['role' => 'mydent']);
        $permissions = app(RolePermissionService::class)->definitions();
        $payload = ['permissions' => ['_present' => '1']];
        foreach ($permissions as $permission) {
            $payload['permissions'][$permission['key']] = array_fill_keys(
                array_values(array_diff($permission['default'], ['super_admin'])),
                '1',
            );
        }
        $payload['permissions']['manage.master_data']['mydent'] = '1';

        $this->actingAs($superAdmin)
            ->put(route('admin.role-access.update'), $payload)
            ->assertRedirect();

        $this->actingAs($mydent)
            ->get(route('master-data.products'))
            ->assertOk();
    }
}
