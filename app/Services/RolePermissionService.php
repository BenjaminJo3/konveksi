<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    public const ROLES = [
        'super_admin' => ['label' => 'Super Admin', 'description' => 'Administrator sistem penuh'],
        'admin' => ['label' => 'Admin Workspace', 'description' => 'Manajer operasional / pemilik'],
        'mydent' => ['label' => 'Toko Mydent', 'description' => 'Staf toko / merchandiser'],
        'warehouse' => ['label' => 'Gudang', 'description' => 'Kepala gudang bahan / kain'],
        'gunung_karang' => ['label' => 'Gunung Karang', 'description' => 'Kepala pabrik / mandor sentral'],
        'cibentang' => ['label' => 'Cibentang', 'description' => 'Kepala jahit sub-konveksi'],
    ];

    public const PERMISSIONS = [
        ['key' => 'dashboard.view', 'label' => 'Melihat ringkasan dashboard', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'orders.view', 'label' => 'Melihat Purchase Order dan detailnya', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'orders.create', 'label' => 'Menerbitkan PO', 'default' => ['super_admin', 'admin', 'mydent']],
        ['key' => 'orders.request_fabric', 'label' => 'Meminta kain untuk PO', 'default' => ['super_admin', 'admin', 'mydent']],
        ['key' => 'fabric.manage', 'label' => 'Mengirim / menerima kain dan mengeluarkan material', 'default' => ['super_admin', 'admin', 'warehouse']],
        ['key' => 'delivery_orders.view', 'label' => 'Melihat Delivery Order (DO)', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'delivery_orders.manage', 'label' => 'Menerbitkan DO / mengirim barang', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'goods_receipts.view', 'label' => 'Melihat Penerimaan (GRN)', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'goods_receipts.manage', 'label' => 'Menerima barang dan mencatat GRN', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'stock_cards', 'label' => 'Mengelola kartu stok barang', 'default' => ['super_admin', 'admin', 'warehouse']],
        ['key' => 'barcodes', 'label' => 'Mencetak barcode barang', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'cutting', 'label' => 'Mencatat hasil cutting', 'default' => ['super_admin', 'admin', 'gunung_karang']],
        ['key' => 'spk.view', 'label' => 'Melihat daftar dan dokumen SPK', 'default' => ['super_admin', 'admin', 'gunung_karang']],
        ['key' => 'spk.issue', 'label' => 'Menerbitkan SPK', 'default' => ['super_admin', 'admin']],
        ['key' => 'spk.sign', 'label' => 'Menandatangani SPK', 'default' => ['super_admin', 'admin', 'gunung_karang']],
        ['key' => 'production.gunung_karang', 'label' => 'Vendor, transfer Cibentang, jahit, finishing, QC, dan pengiriman Gunung Karang', 'default' => ['super_admin', 'admin', 'gunung_karang']],
        ['key' => 'production.cibentang', 'label' => 'Menerima potongan dan mencatat proses jahit / retur Cibentang', 'default' => ['super_admin', 'admin', 'cibentang']],
        ['key' => 'orders.close_receive', 'label' => 'Penerimaan barang jadi Mydent dan Close PO', 'default' => ['super_admin', 'admin', 'mydent']],
        ['key' => 'reports', 'label' => 'Melihat dan mengekspor laporan', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'rejects.view', 'label' => 'Melihat catatan reject vendor, retur, dan QC', 'default' => ['super_admin', 'admin', 'mydent', 'warehouse', 'gunung_karang', 'cibentang']],
        ['key' => 'manage.roles', 'label' => 'Melihat dan mengelola Role & Akses', 'default' => ['super_admin', 'admin']],
        ['key' => 'manage.users', 'label' => 'Mengelola pengguna', 'default' => ['super_admin', 'admin']],
        ['key' => 'manage.branches', 'label' => 'Mengelola cabang', 'default' => ['super_admin', 'admin']],
        ['key' => 'manage.master_data', 'label' => 'Mengelola master barang dan upload data', 'default' => ['super_admin', 'admin']],
        ['key' => 'manage.backup', 'label' => 'Mengelola backup database', 'default' => ['super_admin', 'admin']],
        ['key' => 'manage.audit_logs', 'label' => 'Melihat Audit Log', 'default' => ['super_admin', 'admin']],
    ];

    public function definitions(): array
    {
        return self::PERMISSIONS;
    }

    public function allowed(string $role, string $permission): bool
    {
        if (! isset(self::ROLES[$role]) || ! collect(self::PERMISSIONS)->contains('key', $permission)) {
            return false;
        }
        if ($role === 'super_admin') {
            return true;
        }

        $stored = RolePermission::query()
            ->where('role', $role)
            ->where('permission_key', $permission)
            ->value('allowed');

        return $stored === null
            ? in_array($role, $this->defaultRoles($permission), true)
            : (bool) $stored;
    }

    public function allows(User $user, string $permission): bool
    {
        return $this->allowed($user->role, $permission);
    }

    public function update(array $submitted, int $updatedBy): void
    {
        DB::transaction(function () use ($submitted, $updatedBy): void {
            foreach (self::PERMISSIONS as $permission) {
                foreach (array_keys(self::ROLES) as $role) {
                    if ($role === 'super_admin') {
                        continue;
                    }
                    $allowed = in_array($role, $this->submittedRoles($submitted[$permission['key']] ?? []), true);
                    RolePermission::query()->updateOrCreate(
                        ['role' => $role, 'permission_key' => $permission['key']],
                        ['allowed' => $allowed, 'updated_by' => $updatedBy],
                    );
                }
            }
        });
    }

    public function matrix(): array
    {
        return array_map(function (array $permission): array {
            foreach (array_keys(self::ROLES) as $role) {
                $permission[$role] = $this->allowed($role, $permission['key']);
            }

            return $permission;
        }, self::PERMISSIONS);
    }

    public function roleList(): array
    {
        return array_map(fn (string $key, array $details): array => [
            'key' => $key,
            ...$details,
        ], array_keys(self::ROLES), self::ROLES);
    }

    private function defaultRoles(string $permission): array
    {
        return collect(self::PERMISSIONS)->firstWhere('key', $permission)['default'] ?? [];
    }

    private function submittedRoles(mixed $roles): array
    {
        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter(array_keys(self::ROLES), fn (string $role): bool => $role !== 'super_admin'
            && in_array($roles[$role] ?? null, ['1', 1, true], true)));
    }
}
