<?php

namespace Tests\Feature;

use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkflowEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_open_settings_and_reset_requires_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->post(route('admin.settings.truncate'), [
            'current_password' => 'password',
            'confirmation' => 'HAPUS SEMUA DATA',
            'acknowledge_backup' => '1',
        ])->assertForbidden();
    }

    public function test_super_admin_must_confirm_password_and_phrase_before_reset(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'password' => Hash::make('secure-current-password'),
        ]);
        $order = $this->createOrder($superAdmin);

        $this->actingAs($superAdmin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Truncate seluruh data aplikasi')
            ->assertSee('HAPUS SEMUA DATA')
            ->assertSee('sudah membuat backup');

        $this->post(route('admin.settings.truncate'), [
            'current_password' => 'wrong-password',
            'confirmation' => 'HAPUS SEMUA DATA',
            'acknowledge_backup' => '1',
        ])->assertSessionHasErrors('current_password');

        $this->post(route('admin.settings.truncate'), [
            'current_password' => 'secure-current-password',
            'confirmation' => 'HAPUS',
            'acknowledge_backup' => '1',
        ])->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('production_orders', ['id' => $order->id]);
    }

    public function test_super_admin_reset_deletes_application_data_and_keeps_current_account_and_audit_record(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'password' => Hash::make('secure-current-password'),
        ]);
        $otherUser = User::factory()->create(['role' => 'mydent']);
        $order = $this->createOrder($otherUser);
        WorkflowEntry::query()->create([
            'production_order_id' => $order->id,
            'user_id' => $otherUser->id,
            'action' => 'request_fabric',
            'quantity' => 5,
        ]);
        DB::table('production_order_groups')->insert([
            'po_number' => $order->po_number,
            'created_by' => $otherUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_permissions')->insert([
            'role' => 'admin',
            'permission_key' => 'manage.users',
            'allowed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.settings.truncate'), [
                'current_password' => 'secure-current-password',
                'confirmation' => 'HAPUS SEMUA DATA',
                'acknowledge_backup' => '1',
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('deleted_counts.production_orders', 1);

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'role' => 'super_admin']);
        $this->assertDatabaseMissing('users', ['id' => $otherUser->id]);
        $this->assertDatabaseCount('production_orders', 0);
        $this->assertDatabaseCount('workflow_entries', 0);
        $this->assertDatabaseCount('production_order_groups', 0);
        $this->assertDatabaseCount('role_permissions', 0);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'event' => 'database_truncated',
        ]);
        $this->assertDatabaseCount('audit_logs', 1);

        $this->get(route('admin.settings.index'))->assertOk();
    }

    private function createOrder(User $creator): ProductionOrder
    {
        return ProductionOrder::query()->create([
            'po_number' => 'RESET-'.str()->upper(str()->random(8)),
            'product_name' => 'Produk reset',
            'order_quantity' => 10,
            'fabric_estimate' => 2,
            'fabric_ratio' => 1,
            'created_by' => $creator->id,
            'status' => 'draft',
        ]);
    }
}
