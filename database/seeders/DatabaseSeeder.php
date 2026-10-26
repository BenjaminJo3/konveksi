<?php

namespace Database\Seeders;

use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkflowEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $this->call(SuperAdminAccountSeeder::class);
        $password = config('app.demo_password');
        $users = collect([
            ['Admin Workspace', 'admin@konveksi.test', 'admin'],
            ['Staf Toko Mydent', 'mydent@konveksi.test', 'mydent'],
            ['Petugas Warehouse', 'warehouse@konveksi.test', 'warehouse'],
            ['Mandor Gunung Karang', 'gunungkarang@konveksi.test', 'gunung_karang'],
            ['Mandor Cibentang', 'cibentang@konveksi.test', 'cibentang'],
        ])->mapWithKeys(function (array $account) use ($password): array {
            [$name, $email, $role] = $account;
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => $role, 'password' => Hash::make($password)],
            );

            return [$role => $user];
        });

        if (ProductionOrder::exists()) {
            return;
        }

        $active = ProductionOrder::create([
            'po_number' => 'MYD-2026-001',
            'product_name' => 'Kaos Raglan Premium',
            'color' => 'Navy / Putih',
            'fabric_name' => 'Cotton combed 24s',
            'order_quantity' => 1000,
            'fabric_estimate' => 250,
            'fabric_ratio' => 4,
            'fabric_delivered' => 249,
            'spk_quantity' => 1000,
            'status' => 'quality_ready',
            'delivery_due_at' => now()->addDays(8)->toDateString(),
            'created_by' => $users['mydent']->id,
        ]);

        $events = [
            ['po_created', null, null, null, null, 'Purchase Order dibuat.'],
            ['request_fabric', null, null, null, null, 'Permintaan kain sesuai estimasi PO.'],
            ['deliver_fabric', 250, null, 'DO-KAIN-014', null, 'Kain dikirim dari Gudang Kain.'],
            ['receive_fabric', 249, null, null, null, 'Selisih 1 yard; hasil pengukuran fisik dicatat.'],
            ['issue_spk', 1000, null, 'SPK-MYD-2026-001', null, 'Target kerja cutting Gunung Karang.'],
            ['request_material', 120, null, 'Benang jahit', ['unit' => 'cone'], 'Kebutuhan benang untuk proses jahit PO.'],
            ['issue_material', 120, null, 'Benang jahit', ['unit' => 'cone', 'batch' => 'LOT-BNG-042', 'received_by' => 'Mandor Gunung Karang'], 'Serah-terima material tercatat.'],
            ['cutting_report', 1010, null, null, ['fabric_used' => 249, 'yield_category' => 'over_yield'], 'Over-yield 10 pcs; sisa kain direkonsiliasi.'],
            ['vendor_send', 210, null, 'DO-VND-2026-001', ['vendor' => 'Mitra Sablon Cibadak', 'process' => 'Sablon', 'material_type' => 'Potongan Bahan'], 'Sablon rubber dada kiri.'],
            ['vendor_receive', 200, 10, 'DO-VND-2026-001', ['material_type' => 'Potongan Bahan'], 'Sepuluh potong diisolasi sebagai reject sablon.'],
            ['sewing', 500, null, null, null, 'Proses jahit lokal Gunung Karang.'],
            ['finishing', 450, null, null, null, 'Finishing awal selesai.'],
            ['quality_check', 410, 10, null, ['inspected_quantity' => 420], 'Sepuluh pcs reject dipisahkan dari stok lolos QC.'],
        ];

        foreach ($events as $index => [$action, $quantity, $secondary, $reference, $metadata, $notes]) {
            $role = match ($action) {
                'po_created', 'request_fabric' => 'mydent',
                'deliver_fabric', 'receive_fabric', 'issue_material' => 'warehouse',
                'issue_spk' => 'admin',
                default => 'gunung_karang',
            };

            WorkflowEntry::create([
                'production_order_id' => $active->id,
                'user_id' => $users[$role]->id,
                'action' => $action,
                'quantity' => $quantity,
                'secondary_quantity' => $secondary,
                'reference' => $reference,
                'metadata' => $metadata,
                'notes' => $notes,
                'created_at' => now()->subDays(5)->addHours($index * 3),
                'updated_at' => now()->subDays(5)->addHours($index * 3),
            ]);
        }

        ProductionOrder::create([
            'po_number' => 'MYD-2026-002',
            'product_name' => 'Kemeja Kerja Bordir',
            'color' => 'Abu-abu',
            'fabric_name' => 'Japan drill',
            'order_quantity' => 600,
            'fabric_estimate' => 210,
            'fabric_ratio' => 2.857,
            'status' => 'draft',
            'delivery_due_at' => now()->addDays(18)->toDateString(),
            'created_by' => $users['mydent']->id,
        ]);
    }
}
