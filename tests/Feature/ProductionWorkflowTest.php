<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeliveryOrder;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkflowEntry;
use Database\Seeders\SuperAdminAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_profile_and_authenticated_user_can_log_out(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_can_update_profile_without_changing_role(): void
    {
        $user = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Kepala Gudang',
                'email' => 'kepala.gudang@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Kepala Gudang',
            'email' => 'kepala.gudang@example.test',
            'role' => 'warehouse',
        ]);
    }

    public function test_user_must_provide_current_password_to_change_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        $this->put(route('profile.password'), [
            'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_authenticated_layout_shows_account_menu_and_recent_activity_notifications(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $order = $this->makeOrder('draft', 10);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'user_id' => $user->id,
            'action' => 'request_fabric',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Buat PO baru')
            ->assertSee('aria-label="Notifikasi"', false)
            ->assertSee('Aktivitas terbaru')
            ->assertSee('Profil & ganti password', false)
            ->assertSee('Keluar dari aplikasi')
            ->assertSee('Request Fabric')
            ->assertSee('Pengadaan & Gudang', false)
            ->assertSee('Produksi & Utilitas', false)
            ->assertSee('Administrasi')
            ->assertSee('Master Barang')
            ->assertSee('Dashboard')
            ->assertSee('ratio-cutting-chart')
            ->assertSee('production-vendor-chart')
            ->assertSee('Diagram donat PO gantung per lokasi')
            ->assertSee('Papan Kanban Operasional')
            ->assertSee('PO Gantung (belum closing per Lokasi)')
            ->assertSee('Total PO')
            ->assertSee('PO Vs H. Cutting');
    }

    public function test_opening_notification_bell_marks_current_notifications_read_for_that_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'admin']);
        $order = $this->makeOrder('draft', 10);
        $notification = WorkflowEntry::create([
            'production_order_id' => $order->id,
            'user_id' => $user->id,
            'action' => 'request_fabric',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="notification-unread-count"', false)
            ->assertSee('data-notification-trigger', false);

        $this->postJson(route('notifications.read'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertDatabaseHas('user_notification_reads', [
            'user_id' => $user->id,
            'last_read_entry_id' => $notification->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="notification-unread-count"', false)
            ->assertSee('Request Fabric');

        $this->actingAs($otherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="notification-unread-count"', false);

        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'user_id' => $user->id,
            'action' => 'deliver_fabric',
        ]);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="notification-unread-count"', false);
    }

    public function test_application_stays_available_when_notification_read_migration_is_pending(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('user_notification_reads')
            ->twice()
            ->andReturn(false);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk();

        $this->postJson(route('notifications.read'))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Fitur notifikasi belum tersedia. Jalankan php artisan migrate --force pada server.');
    }

    public function test_dashboard_period_tabs_filter_purchase_orders_by_po_date(): void
    {
        $todayOrder = $this->makeOrder('draft', 10);
        $todayOrder->update(['po_date' => now()->toDateString()]);
        $this->assertSame(now()->toDateString(), $todayOrder->fresh()->po_date->toDateString());

        $monthOrder = $this->makeOrder('draft', 20);
        $monthOrder->update(['po_date' => now()->startOfMonth()->toDateString()]);

        $lastYearOrder = $this->makeOrder('draft', 30);
        $lastYearOrder->update(['po_date' => now()->subYear()->toDateString()]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('dashboard', ['period' => 'day']))
            ->assertOk()
            ->assertSee('Per Hari')
            ->assertSee('Per Bulan')
            ->assertSee('Per Tahun')
            ->assertSee('data-stat="total-po" data-stat-count="1"', false);

        $this->get(route('dashboard', ['period' => 'month']))
            ->assertOk()
            ->assertSee('data-stat="total-po" data-stat-count="2"', false);

        $this->get(route('dashboard', ['period' => 'year']))
            ->assertOk()
            ->assertSee('data-stat="total-po" data-stat-count="2"', false);
    }

    public function test_dashboard_analytics_counts_purchase_orders_and_displays_production_charts(): void
    {
        $draftOrder = $this->makeOrder('draft', 10);
        $inProductionOrder = $this->makeOrder('in_production', 20);
        $waitingOrder = $this->makeOrder('quality_ready', 30);
        $completedOrder = $this->makeOrder('delivered', 40);
        $closedOrder = $this->makeOrder('closed', 50);
        $secondDraftItem = $this->makeOrder('in_production', 12);
        $secondDraftItem->update(['po_number' => $draftOrder->po_number]);

        WorkflowEntry::create([
            'production_order_id' => $inProductionOrder->id,
            'action' => 'cutting_report',
            'quantity' => 20,
        ]);
        $inProductionOrder->update(['ratio_result_qty' => 18]);
        WorkflowEntry::create([
            'production_order_id' => $inProductionOrder->id,
            'action' => 'sewing',
            'quantity' => 10,
        ]);
        WorkflowEntry::create([
            'production_order_id' => $inProductionOrder->id,
            'action' => 'site_transfer',
            'quantity' => 5,
        ]);
        WorkflowEntry::create([
            'production_order_id' => $inProductionOrder->id,
            'action' => 'vendor_receive',
            'quantity' => 6,
            'metadata' => ['material_type' => 'Baju Jahit'],
        ]);
        DeliveryOrder::query()->create([
            'number' => 'DO-ANALYTICS-0001',
            'production_order_id' => $inProductionOrder->id,
            'source_role' => 'gunung_karang',
            'destination_role' => 'cibentang',
            'goods_type' => 'cut_pieces',
            'quantity' => 12,
            'unit' => 'pcs',
            'dispatched_at' => now()->toDateString(),
            'created_by' => User::factory()->create(['role' => 'gunung_karang'])->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total PO')
            ->assertSee('1', false)
            ->assertSee('PO Vs H. Cutting')
            ->assertSee('Target rasio PO dibanding hasil cutting')
            ->assertSee('Konveksi Vs Sablon dan Bordir')
            ->assertSee('PO Gantung (belum closing per Lokasi)')
            ->assertSee('id="ratio-cutting-chart"', false)
            ->assertSee('id="production-vendor-chart"', false)
            ->assertSee('data-dashboard-donut', false)
            ->assertSee('dashboard-chart-bar', false)
            ->assertSee('dashboard-donut-segment', false)
            ->assertSee('--donut-segment-duration:', false)
            ->assertSee('<title>MYD-', false)
            ->assertSee('output konveksi 10 pcs · hasil baik vendor sablon/bordir 6 pcs')
            ->assertSee('Konveksi Gunung Karang')
            ->assertSee('Konveksi Cibentang')
            ->assertSee('Grosir Mydent')
            ->assertSee('Gudang')
            ->assertSee('20')
            ->assertSee('10')
            ->assertSee('data-stat="total-po" data-stat-count="5"', false)
            ->assertSee('data-stat="baru" data-stat-count="0"', false)
            ->assertSee('data-stat="proses" data-stat-count="2"', false)
            ->assertSee('data-stat="menunggu" data-stat-count="1"', false)
            ->assertSee('data-stat="selesai" data-stat-count="1"', false)
            ->assertSee('data-stat="ditutup" data-stat-count="1"', false)
            ->assertSee('id="ratio-cutting-chart"', false)
            ->assertSee('role="img" aria-label="Diagram donat PO gantung per lokasi, total 4 PO"', false)
            ->assertSee('href="'.route('orders.index').'"', false)
            ->assertSee('href="'.route('orders.index', ['status' => 'draft']).'"', false)
            ->assertSee('href="'.route('orders.index', ['status' => 'processing']).'"', false)
            ->assertSee('href="'.route('orders.index', ['status' => 'waiting']).'"', false)
            ->assertSee('href="'.route('orders.index', ['status' => 'completed']).'"', false)
            ->assertSee('href="'.route('orders.index', ['status' => 'closed']).'"', false);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'cutting_report',
            'subject_type' => 'production_order',
            'subject_id' => (string) $inProductionOrder->id,
        ]);

        $this->get(route('orders.index', ['status' => 'processing']))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->total() === 2);
    }

    public function test_operational_kanban_tracks_purchase_orders_by_live_workflow_stage(): void
    {
        $draft = $this->makeOrder('draft', 10);
        $fabric = $this->makeOrder('fabric_in_transit', 20);
        $cutting = $this->makeOrder('cutting_complete', 30);
        $production = $this->makeOrder('in_production', 40);
        $qc = $this->makeOrder('quality_ready', 50);
        $received = $this->makeOrder('delivered', 60);
        $closed = $this->makeOrder('closed', 70);
        DB::table('production_orders')->where('id', $draft->id)->update([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
            'delivery_due_at' => today()->subDays(2)->toDateString(),
        ]);
        WorkflowEntry::query()->create([
            'production_order_id' => $production->id,
            'user_id' => User::factory()->create(['role' => 'gunung_karang'])->id,
            'action' => 'sewing',
            'quantity' => 12,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('orders.kanban'))
            ->assertOk()
            ->assertSee('Papan Kanban Operasional')
            ->assertSee('PO Baru')
            ->assertSee('Permintaan & Kain')
            ->assertSee('SPK & Cutting')
            ->assertSee('Vendor / Cibentang / Produksi')
            ->assertSee('QC & Pengiriman')
            ->assertSee('Diterima Mydent')
            ->assertSee('Close PO')
            ->assertSee('Update otomatis 60 detik')
            ->assertSee('5 hari')
            ->assertSee('Terlambat 2 hari')
            ->assertSee('Aksi berikutnya · Mydent')
            ->assertSee('Ajukan permintaan kain')
            ->assertSee('Umur PO')
            ->assertSee('Di tahap ini')
            ->assertSee($draft->po_number)
            ->assertSee($fabric->po_number)
            ->assertSee($cutting->po_number)
            ->assertSee($production->po_number)
            ->assertSee($qc->po_number)
            ->assertSee($received->po_number)
            ->assertSee($closed->po_number);

        $this->actingAs(User::factory()->create(['role' => 'warehouse']))
            ->get(route('orders.kanban'))
            ->assertOk();
    }

    public function test_reject_menu_lists_vendor_return_and_qc_rejections_with_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->makeOrder('in_production', 20);
        WorkflowEntry::query()->create([
            'production_order_id' => $order->id,
            'user_id' => $admin->id,
            'action' => 'quality_check',
            'reference' => 'QC-REF-01',
            'quantity' => 17,
            'secondary_quantity' => 3,
            'notes' => 'Jahitan lepas pada bagian samping.',
        ]);
        WorkflowEntry::query()->create([
            'production_order_id' => $order->id,
            'user_id' => $admin->id,
            'action' => 'vendor_receive',
            'reference' => 'VND-REF-01',
            'quantity' => 18,
            'secondary_quantity' => 2,
            'metadata' => ['material_type' => 'Baju Jahit'],
        ]);

        $this->actingAs($admin)
            ->get(route('rejects.index'))
            ->assertOk()
            ->assertSee('Catatan Reject')
            ->assertSee('Total reject tercatat')
            ->assertSee('5')
            ->assertSee('3 pcs')
            ->assertSee('2 pcs')
            ->assertSee('Pemeriksaan QC')
            ->assertSee('Penerimaan Vendor')
            ->assertSee('Jahitan lepas pada bagian samping.')
            ->assertSee($order->po_number);

        $this->get(route('rejects.index', ['q' => 'VND-REF-01']))
            ->assertOk()
            ->assertSee('VND-REF-01')
            ->assertDontSee('QC-REF-01');
    }

    public function test_admin_can_select_grn_location_and_location_is_checked_against_delivery_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sender = User::factory()->create(['role' => 'gunung_karang']);
        $mydentOrder = $this->makeOrder('in_production', 100);
        $cibentangOrder = $this->makeOrder('in_production', 100);
        $cibentangOrder->update(['po_number' => 'PO-CIBENTANG']);
        $mydentDo = DeliveryOrder::query()->create([
            'number' => 'DO-LOCATION-0001',
            'production_order_id' => $mydentOrder->id,
            'source_role' => 'gunung_karang',
            'destination_role' => 'mydent',
            'goods_type' => 'finished_goods',
            'quantity' => 10,
            'unit' => 'pcs',
            'dispatched_at' => now()->toDateString(),
            'created_by' => $sender->id,
        ]);
        DeliveryOrder::query()->create([
            'number' => 'DO-LOCATION-0002',
            'production_order_id' => $cibentangOrder->id,
            'source_role' => 'gunung_karang',
            'destination_role' => 'cibentang',
            'goods_type' => 'cut_pieces',
            'quantity' => 10,
            'unit' => 'pcs',
            'dispatched_at' => now()->toDateString(),
            'created_by' => $sender->id,
        ]);

        $this->actingAs($admin)
            ->get(route('goods-receipts.index'))
            ->assertOk()
            ->assertSee('name="location"', false)
            ->assertSee('Mydent (Grosir)')
            ->assertSee('Konveksi Gunung Karang')
            ->assertSee('Konveksi Cibentang')
            ->assertSee('DO-LOCATION-0001')
            ->assertDontSee('DO-LOCATION-0002');

        $this->get(route('goods-receipts.index', ['location' => 'cibentang']))
            ->assertOk()
            ->assertSee('DO-LOCATION-0002')
            ->assertDontSee('DO-LOCATION-0001')
            ->assertSee('value="cibentang" selected', false);

        $this->post(route('goods-receipts.store'), [
            'delivery_order_id' => $mydentDo->id,
            'destination_role' => 'cibentang',
            'quantity_received' => 10,
        ])->assertForbidden();
    }

    public function test_admin_can_manage_product_masters_with_generated_codes_and_grid_kanban_views(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Master Barang')
            ->assertSee('sidebar-toggle')
            ->assertSee('Daftar Barang');

        $this->post(route('master-data.store', 'units'), [
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ])->assertRedirect(route('master-data.index', 'units'));
        $unit = DB::table('master_units')->where('name', 'Pieces')->first();
        $this->assertMatchesRegularExpression('/^UNT-[A-F0-9]{6}$/', $unit->code);

        $this->post(route('master-data.store', 'conversions'), [
            'from_unit_id' => $unit->id,
            'to_unit_id' => $unit->id,
            'factor' => 12,
        ])->assertSessionHasErrors('from_unit_id');
        $this->post(route('master-data.store', 'units'), [
            'name' => 'Lusin',
            'symbol' => 'dz',
        ]);
        $dozen = DB::table('master_units')->where('name', 'Lusin')->first();
        $this->post(route('master-data.store', 'conversions'), [
            'from_unit_id' => $dozen->id,
            'to_unit_id' => $unit->id,
            'factor' => 12,
        ])->assertRedirect(route('master-data.index', 'conversions'));

        $this->post(route('master-data.store', 'categories'), ['name' => 'Pakaian'])
            ->assertRedirect(route('master-data.index', 'categories'));
        $category = DB::table('master_categories')->where('name', 'Pakaian')->first();
        $this->post(route('master-data.store', 'sub-categories'), [
            'category_id' => $category->id,
            'name' => 'Kaos',
        ])->assertRedirect(route('master-data.index', 'sub-categories'));
        $subCategory = DB::table('master_sub_categories')->where('name', 'Kaos')->first();
        $this->get(route('master-data.products.create'))
            ->assertOk()
            ->assertSee('Model / Artikel (opsional)')
            ->assertSee('id="model_id" name="model_id"', false)
            ->assertDontSee('id="model_id" name="model_id" required', false)
            ->assertSee('id="category_id" name="category_id" data-searchable-select data-large-dropdown data-dispatch-change', false)
            ->assertSee('id="color_id" name="color_id" data-searchable-select data-large-dropdown data-dispatch-change', false);
        $this->get(route('master-data.create', 'models'))
            ->assertOk()
            ->assertSee('id="category_id" name="category_id" data-searchable-select', false)
            ->assertSee('id="sub_category_id" name="sub_category_id" data-searchable-select data-category-filter="#category_id"', false);
        $this->post(route('master-data.store', 'models'), [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => 'Raglan Premium',
        ])->assertRedirect(route('master-data.index', 'models'));
        $model = DB::table('master_models')->where('name', 'Raglan Premium')->first();

        $this->post(route('master-data.store', 'sizes'), ['name' => 'M']);
        $size = DB::table('master_sizes')->where('name', 'M')->first();
        $this->post(route('master-data.store', 'colors'), ['name' => 'Navy', 'hex_color' => '#1e3a8a']);
        $color = DB::table('master_colors')->where('name', 'Navy')->first();

        $this->post(route('master-data.products.store'), [
            'name' => 'Kaos Raglan Premium',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'model_id' => $model->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'unit_id' => $unit->id,
            'fabric_name' => 'Cotton combed 24s',
        ])->assertRedirect(route('master-data.products'));

        $product = DB::table('master_products')->where('model_id', $model->id)->first();
        $this->assertMatchesRegularExpression('/^BRG-[A-F0-9]{6}$/', $product->code);
        $this->assertSame('Pakaian Kaos M Navy Cotton combed 24s', $product->name);
        $this->assertSame($model->id, $product->model_id);

        $this->post(route('master-data.products.store'), [
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'unit_id' => $unit->id,
            'fabric_name' => 'CHINOS',
        ])->assertRedirect(route('master-data.products'));
        $modelOptionalProduct = DB::table('master_products')->whereNull('model_id')->first();
        $this->assertNotNull($modelOptionalProduct);
        $this->assertSame('Pakaian Kaos M Navy CHINOS', $modelOptionalProduct->name);
        $this->get(route('master-data.ratios'))
            ->assertOk()
            ->assertSee('Rasio kain per pcs')
            ->assertSee('Tambah rasio')
            ->assertSee('Sub-kategori')
            ->assertSee('data-searchable-select', false)
            ->assertSee('Ukuran');
        $this->post(route('master-data.ratios.store'), [
            'sub_category_id' => $subCategory->id,
            'size_id' => $size->id,
            'ratio' => '0.3750',
        ])->assertRedirect(route('master-data.ratios'))
            ->assertSessionHas('success');
        $ratio = DB::table('master_ratios')->first();
        $this->assertNotNull($ratio);
        $this->assertDatabaseHas('master_ratios', [
            'sub_category_id' => $subCategory->id,
            'size_id' => $size->id,
            'ratio' => '0.3750',
        ]);
        $this->post(route('master-data.ratios.update', $ratio->id), [
            'ratio' => '0.5000',
        ])->assertRedirect(route('master-data.ratios'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('master_ratios', [
            'id' => $ratio->id,
            'ratio' => '0.5000',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'master_ratio_updated',
            'subject_id' => (string) $ratio->id,
        ]);
        $this->actingAs($admin)
            ->get(route('master-data.products', ['view' => 'grid']))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($modelOptionalProduct->name)
            ->assertSee($product->code)
            ->assertSee('master-products-grid');
        $this->get(route('master-data.products', ['view' => 'kanban']))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($modelOptionalProduct->name)
            ->assertSee('Raglan Premium');
        $this->get(route('barcodes.index', ['q' => $product->code]))
            ->assertOk()
            ->assertSee('Cetak Barcode')
            ->assertSee($product->code)
            ->assertSee('Ketik kode barang atau nama')
            ->assertSee('paper-width');
        $this->get(route('barcodes.index', ['q' => $modelOptionalProduct->code]))
            ->assertOk()
            ->assertSee($modelOptionalProduct->code)
            ->assertSee('Tanpa model');

        $this->actingAs(User::factory()->create(['role' => 'warehouse']))
            ->get(route('master-data.products'))
            ->assertForbidden();
    }

    public function test_admin_audit_log_shows_workflow_and_master_events_and_is_protected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->makeOrder('draft', 10);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'user_id' => $admin->id,
            'action' => 'po_created',
            'reference' => $order->po_number,
            'notes' => 'PO dibuat untuk pengujian audit.',
        ]);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'event' => 'master_product_created',
            'subject_type' => 'master_product',
            'subject_id' => 'BRG-123ABC',
            'description' => 'Master barang Kaos dibuat.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['q' => 'Kaos']))
            ->assertOk()
            ->assertSee('Audit Log')
            ->assertSee('BRG-123ABC')
            ->assertSee('Master barang Kaos dibuat.');

        $this->get(route('admin.audit-logs.index', ['q' => $order->po_number]))
            ->assertOk()
            ->assertSee('PO dibuat untuk pengujian audit.');

        $this->actingAs(User::factory()->create(['role' => 'warehouse']))
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_backup_menu_and_database_snapshot_rejects_unsupported_memory_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.backup.index'))
            ->assertOk()
            ->assertSee('Backup Database')
            ->assertSee('Buat & Unduh Backup', false);

        $this->post(route('admin.backup.create'))
            ->assertStatus(422);

        $this->actingAs(User::factory()->create(['role' => 'mydent']))
            ->get(route('admin.backup.index'))
            ->assertForbidden();
    }

    public function test_delivery_order_can_be_sent_to_another_location_and_received_as_grn(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $gunungKarang = User::factory()->create(['role' => 'gunung_karang']);
        $order = $this->makeOrder('in_production', 100);

        $this->actingAs($warehouse)
            ->get(route('delivery-orders.create'))
            ->assertOk()
            ->assertSee('name="destination_role"', false)
            ->assertSee('name="po_number"', false)
            ->assertSee('id="po-item-grid"', false)
            ->assertSee('name="goods_type"', false)
            ->assertSee('Mydent (Grosir)')
            ->assertSee('Konveksi Gunung Karang')
            ->assertSee('Konveksi Cibentang')
            ->assertSee('data-destination-role="mydent"', false);

        $secondOrder = $this->makeOrder('in_production', 80);
        $secondOrder->update(['po_number' => $order->po_number]);
        $this->actingAs($warehouse)
            ->get(route('delivery-orders.purchase-orders', $order->po_number))
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.id', $order->id)
            ->assertJsonPath('items.1.id', $secondOrder->id);

        $this->post(route('delivery-orders.store'), [
            'destination_role' => 'gunung_karang',
            'po_number' => $order->po_number,
            'goods_type' => 'fabric',
            'items' => [
                ['production_order_id' => $order->id, 'quantity' => 25],
                ['production_order_id' => $secondOrder->id, 'quantity' => 12],
            ],
            'dispatched_at' => now()->toDateString(),
        ])
            ->assertRedirect(route('delivery-orders.index'))
            ->assertSessionHas('success');

        $deliveryOrder = DeliveryOrder::query()->firstOrFail();
        $this->assertSame('warehouse', $deliveryOrder->source_role);
        $this->assertSame('yard/kg', $deliveryOrder->unit);
        $this->assertStringStartsWith('DO-', $deliveryOrder->number);
        $this->assertSame(2, $deliveryOrder->group->items()->count());
        $this->assertSame(37.0, $deliveryOrder->group->totalQuantity());
        $this->assertMatchesRegularExpression('/^DO-\d{4}-\d{5}$/', $deliveryOrder->group->number);
        $this->assertDatabaseHas('delivery_orders', [
            'delivery_order_group_id' => $deliveryOrder->delivery_order_group_id,
            'production_order_id' => $secondOrder->id,
            'quantity' => 12,
        ]);
        $groupNumber = $deliveryOrder->group->number;

        $this->actingAs($warehouse)
            ->get(route('delivery-orders.index'))
            ->assertOk()
            ->assertSee($groupNumber)
            ->assertSee('2 item')
            ->assertSee('37.00 yard/kg');

        $this->actingAs($gunungKarang)
            ->get(route('goods-receipts.index'))
            ->assertOk()
            ->assertSee('id="receipt-location"', false)
            ->assertSee('value="gunung_karang" selected', false)
            ->assertSee($groupNumber)
            ->assertSee($secondOrder->product_name)
            ->assertSee('Catat GRN');

        $this->post(route('goods-receipts.store'), [
            'delivery_order_id' => $deliveryOrder->id,
            'destination_role' => 'gunung_karang',
            'quantity_received' => 25,
        ])
            ->assertRedirect(route('goods-receipts.index', ['location' => 'gunung_karang']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('goods_receipts', [
            'delivery_order_id' => $deliveryOrder->id,
            'quantity_received' => 25,
            'received_by' => $gunungKarang->id,
        ]);
        $this->assertSame(0.0, $deliveryOrder->fresh()->pendingQuantity());
    }

    public function test_goods_receipt_requires_a_note_for_partial_or_mismatched_quantity(): void
    {
        $order = $this->makeOrder('in_production', 100);
        $deliveryOrder = DeliveryOrder::query()->create([
            'number' => 'DO-TEST-00001',
            'production_order_id' => $order->id,
            'source_role' => 'warehouse',
            'destination_role' => 'gunung_karang',
            'goods_type' => 'fabric',
            'quantity' => 25,
            'unit' => 'yard/kg',
            'dispatched_at' => now()->toDateString(),
            'created_by' => User::factory()->create(['role' => 'warehouse'])->id,
        ]);
        $gunungKarang = User::factory()->create(['role' => 'gunung_karang']);

        $this->actingAs($gunungKarang)
            ->post(route('goods-receipts.store'), [
                'delivery_order_id' => $deliveryOrder->id,
                'quantity_received' => 20,
            ])
            ->assertSessionHasErrors('discrepancy_note');
    }

    public function test_grn_cannot_be_recorded_by_another_destination_or_above_do_quantity(): void
    {
        $order = $this->makeOrder('in_production', 100);
        $deliveryOrder = DeliveryOrder::query()->create([
            'number' => 'DO-TEST-00002',
            'production_order_id' => $order->id,
            'source_role' => 'gunung_karang',
            'destination_role' => 'cibentang',
            'goods_type' => 'cut_pieces',
            'quantity' => 20,
            'unit' => 'pcs',
            'dispatched_at' => now()->toDateString(),
            'created_by' => User::factory()->create(['role' => 'gunung_karang'])->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'mydent']))
            ->post(route('goods-receipts.store'), [
                'delivery_order_id' => $deliveryOrder->id,
                'quantity_received' => 20,
            ])
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'cibentang']))
            ->post(route('goods-receipts.store'), [
                'delivery_order_id' => $deliveryOrder->id,
                'quantity_received' => 21,
            ])
            ->assertSessionHasErrors('quantity_received');
    }

    public function test_local_login_shows_demo_accounts_with_quick_fill(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        config(['app.demo_password' => 'password']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('We Know How IT Works ...')
            ->assertSee('Akun Pengujian Bawaan (Klik untuk Isi Cepat)')
            ->assertSee('data-demo-email="admin@konveksi.test"', false)
            ->assertSee('data-demo-email="superadmin@konveksi.test"', false)
            ->assertSee('data-demo-password="password"', false)
            ->assertSee('Kata sandi semua akun:');
    }

    public function test_non_local_login_does_not_show_demo_credentials(): void
    {
        config(['app.env' => 'production']);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Akun Pengujian Bawaan')
            ->assertDontSee('admin@konveksi.test');
    }

    public function test_only_mydent_or_admin_can_create_a_purchase_order(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($warehouse)
            ->get(route('orders.create'))
            ->assertForbidden();

        $mydent = User::factory()->create(['role' => 'mydent']);

        $this->actingAs($mydent)
            ->post(route('orders.store'), [
                'po_number' => 'MYD-TEST-001',
                'product_name' => 'Kaos uji',
                'order_quantity' => 100,
                'fabric_estimate' => 25,
                'fabric_ratio' => 4,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('production_orders', [
            'po_number' => 'MYD-TEST-001',
            'created_by' => $mydent->id,
        ]);
    }

    public function test_mydent_can_create_one_purchase_order_with_multiple_product_variants(): void
    {
        $mydent = User::factory()->create(['role' => 'mydent']);

        $this->actingAs($mydent)
            ->post(route('orders.store'), [
                'po_number_mode' => 'automatic',
                'po_date' => '2026-10-25',
                'items' => [
                    [
                        'category' => 'Pakaian',
                        'sub_category' => 'Kaos',
                        'product_name' => 'Kaos Raglan',
                        'model' => 'Raglan',
                        'color' => 'Navy / Putih',
                        'size' => 'S',
                        'fabric_name' => 'Cotton combed 24s',
                        'yard_kg' => 250,
                        'rib_qty' => 12,
                        'ratio' => 0.25,
                    ],
                    [
                        'category' => 'Pakaian',
                        'sub_category' => 'Kaos',
                        'product_name' => 'Kaos Raglan',
                        'model' => 'Raglan',
                        'color' => 'Navy / Putih',
                        'size' => 'M',
                        'fabric_name' => 'Cotton combed 24s',
                        'yard_kg' => 300,
                        'rib_qty' => 14,
                        'ratio' => 0.25,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('production_orders', 2);
        $this->assertDatabaseCount('production_order_groups', 1);
        $this->assertDatabaseHas('production_orders', [
            'po_number' => 'MYD-2026-001',
            'size' => 'S',
            'order_quantity' => 1000,
            'ratio_result_qty' => '1000.0000',
            'created_by' => $mydent->id,
        ]);
        $this->assertDatabaseHas('production_orders', [
            'po_number' => 'MYD-2026-001',
            'size' => 'M',
            'order_quantity' => 1200,
            'ratio_result_qty' => '1200.0000',
            'created_by' => $mydent->id,
        ]);
    }

    public function test_mydent_can_edit_and_delete_a_whole_draft_purchase_order(): void
    {
        $mydent = User::factory()->create(['role' => 'mydent']);
        $this->actingAs($mydent)->post(route('orders.store'), [
            'po_number_mode' => 'manual',
            'po_number' => 'MYD-EDIT-001',
            'po_date' => '2026-10-25',
            'delivery_due_at' => '2026-11-25',
            'items' => [
                [
                    'category' => 'Pakaian',
                    'sub_category' => 'Kaos',
                    'product_name' => 'Kaos Raglan',
                    'model' => 'Raglan',
                    'color' => 'Navy',
                    'size' => 'S',
                    'fabric_name' => 'Cotton',
                    'yard_kg' => 25,
                    'ratio' => 0.25,
                ],
                [
                    'category' => 'Pakaian',
                    'sub_category' => 'Kaos',
                    'product_name' => 'Kaos Raglan',
                    'model' => '',
                    'color' => 'Putih',
                    'size' => 'M',
                    'fabric_name' => 'Cotton',
                    'yard_kg' => 30,
                    'ratio' => 0.25,
                ],
            ],
        ])->assertRedirect();
        $order = ProductionOrder::query()->where('po_number', 'MYD-EDIT-001')->orderBy('id')->firstOrFail();

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee(route('orders.edit', $order), false)
            ->assertSee(route('orders.destroy', $order), false)
            ->assertSee('Hapus');
        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Edit PO')
            ->assertSee('Hapus PO');
        $this->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('MYD-EDIT-001')
            ->assertSee('Kaos Raglan');

        $this->put(route('orders.update', $order), [
            'po_number_mode' => 'manual',
            'po_number' => 'MYD-EDIT-001',
            'po_date' => '2026-10-26 00:00:00',
            'delivery_due_at' => '2026-11-30 00:00:00',
            'items' => [[
                'category' => 'Celana',
                'sub_category' => 'Chinos',
                'product_name' => 'Celana Chinos',
                'model' => '',
                'color' => 'Hitam',
                'size' => 'L',
                'fabric_name' => 'Chinos',
                'yard_kg' => 50,
                'ratio' => 0.5,
            ]],
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseCount('production_orders', 1);
        $this->assertDatabaseHas('production_orders', [
            'id' => $order->id,
            'po_number' => 'MYD-EDIT-001',
            'po_date' => '2026-10-26 00:00:00',
            'product_name' => 'Celana Chinos',
            'size' => 'L',
            'order_quantity' => 100,
            'delivery_due_at' => '2026-11-30 00:00:00',
        ]);
        $this->assertDatabaseHas('production_order_groups', [
            'po_number' => 'MYD-EDIT-001',
            'po_date' => '2026-10-26 00:00:00',
        ]);

        $this->delete(route('orders.destroy', $order))
            ->assertRedirect(route('orders.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('production_orders', ['po_number' => 'MYD-EDIT-001']);
        $this->assertDatabaseMissing('production_order_groups', ['po_number' => 'MYD-EDIT-001']);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'purchase_order_deleted',
            'subject_type' => 'production_order_group',
            'subject_id' => 'MYD-EDIT-001',
        ]);
    }

    public function test_purchase_order_with_production_activity_cannot_be_edited_or_deleted(): void
    {
        $mydent = User::factory()->create(['role' => 'mydent']);
        $this->actingAs($mydent)->post(route('orders.store'), [
            'po_number_mode' => 'manual',
            'po_number' => 'MYD-ACTIVE-001',
            'po_date' => '2026-10-25',
            'items' => [[
                'category' => 'Pakaian',
                'sub_category' => 'Kaos',
                'product_name' => 'Kaos Raglan',
                'color' => 'Navy',
                'size' => 'S',
                'yard_kg' => 25,
                'ratio' => 0.25,
            ]],
        ])->assertRedirect();
        $order = ProductionOrder::query()->where('po_number', 'MYD-ACTIVE-001')->firstOrFail();
        WorkflowEntry::query()->create([
            'production_order_id' => $order->id,
            'user_id' => $mydent->id,
            'action' => 'request_fabric',
        ]);

        $this->get(route('orders.edit', $order))->assertStatus(409);
        $this->delete(route('orders.destroy', $order))->assertStatus(409);
        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Edit PO')
            ->assertDontSee('Hapus PO');
        $this->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee(route('orders.edit', $order), false)
            ->assertSee('Hapus')
            ->assertSee('PO sudah diproses')
            ->assertSee('disabled title="Hanya PO Draft tanpa aktivitas yang dapat dihapus."', false);
        $this->assertDatabaseHas('production_orders', ['id' => $order->id, 'status' => 'draft']);
    }

    public function test_purchase_order_can_be_saved_without_model_or_motif(): void
    {
        $mydent = User::factory()->create(['role' => 'mydent']);

        $this->actingAs($mydent)
            ->post(route('orders.store'), [
                'po_number_mode' => 'automatic',
                'po_date' => '2026-10-25',
                'items' => [[
                    'category' => 'Celana',
                    'sub_category' => 'Joger',
                    'product_name' => 'Celana Joger SML Abu Chinos',
                    'model' => '',
                    'size' => 'SML',
                    'color' => 'Abu',
                    'fabric_name' => 'Chinos',
                    'yard_kg' => 45,
                    'ratio' => 0.55,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('production_orders', [
            'po_number' => 'MYD-2026-001',
            'model' => null,
            'product_name' => 'Celana Joger SML Abu Chinos',
        ]);
    }

    public function test_purchase_order_form_supports_multiple_variant_rows_and_auto_numbering(): void
    {
        $mydent = User::factory()->create(['role' => 'mydent']);
        $categoryId = DB::table('master_categories')->insertGetId([
            'code' => 'KTG-PO-CELANA',
            'name' => 'Celana',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($mydent)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('Tambah varian')
            ->assertSee('<select aria-label="Kategori" data-po-category data-searchable-select', false)
            ->assertSee('po-grid-searchable po-category-select', false)
            ->assertSee('value="Celana" data-category-id="'.$categoryId.'"', false)
            ->assertSee('items[0][category]', false)
            ->assertSee('Rasio yard/pcs')
            ->assertSee('table-fixed')
            ->assertSee('data-item', false)
            ->assertSee('data-draft-key="po-create-draft-'.$mydent->id.'"', false)
            ->assertSee('Isian tersimpan otomatis di browser', false)
            ->assertSee('sessionStorage.setItem(draftKey', false)
            ->assertSee('while (items.querySelectorAll', false)
            ->assertSee('max-h-[56vh]', false)
            ->assertSee('data-label="Kategori"', false)
            ->assertSee('name="items[0][sub_category]" value="" required disabled placeholder="Pilih kategori dulu"', false)
            ->assertSee('name="items[0][product_name]" value="" required disabled placeholder="Pilih kategori dulu"', false)
            ->assertSee('name="items[0][model]" value="" disabled placeholder="Opsional"', false)
            ->assertDontSee('name="items[0][model]" value="" required', false)
            ->assertSee('<select aria-label="Ukuran" data-po-size data-searchable-select', false)
            ->assertSee('po-grid-searchable po-size-select', false)
            ->assertSee('name="items[0][size]" required disabled', false)
            ->assertSee('<option value="">Pilih ukuran</option>', false)
            ->assertSee('name="items[0][size]"', false)
            ->assertSee('<select aria-label="Warna" data-po-color data-searchable-select data-placeholder="Pilih warna"', false)
            ->assertSee('po-grid-searchable po-color-select', false)
            ->assertSee('name="items[0][color]" required disabled', false)
            ->assertSee('<option value="">Pilih warna</option>', false)
            ->assertSee("listFor(item, 'sub-categories', subCategoryInput, subCategories)", false)
            ->assertSee('option.dataset.categoryId === category.dataset.categoryId', false)
            ->assertSee('input.disabled = !category', false)
            ->assertSee("items?.addEventListener('change'", false)
            ->assertSee('clearDependentFields(item, input)', false)
            ->assertSee('Otomatis');
    }

    public function test_master_category_list_can_be_searched_by_name_or_code(): void
    {
        DB::table('master_categories')->insert([
            [
                'code' => 'KTG-CARI-01',
                'name' => 'POLO LINEN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'KTG-CARI-02',
                'name' => 'RAYON',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('master-data.index', ['type' => 'categories', 'q' => 'POLO']))
            ->assertOk()
            ->assertSee('Cari kode atau nama kategori')
            ->assertSee('POLO LINEN')
            ->assertDontSee('RAYON');

        $this->get(route('master-data.index', ['type' => 'categories', 'q' => 'KTG-CARI-02']))
            ->assertOk()
            ->assertSee('RAYON')
            ->assertDontSee('POLO LINEN');
    }

    public function test_purchase_order_form_suggests_master_data_and_includes_product_attributes(): void
    {
        $now = now();
        $categoryId = DB::table('master_categories')->insertGetId([
            'code' => 'KTG-PO-001',
            'name' => 'POLO LINEN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subCategoryId = DB::table('master_sub_categories')->insertGetId([
            'category_id' => $categoryId,
            'code' => 'SUB-PO-001',
            'name' => 'Polo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherCategoryId = DB::table('master_categories')->insertGetId([
            'code' => 'KTG-PO-002',
            'name' => 'Bahan',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherSubCategoryId = DB::table('master_sub_categories')->insertGetId([
            'category_id' => $otherCategoryId,
            'code' => 'SUB-PO-002',
            'name' => 'Kain',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $modelId = DB::table('master_models')->insertGetId([
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'code' => 'MDL-PO-001',
            'name' => 'Raglan',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sizeId = DB::table('master_sizes')->insertGetId([
            'code' => 'UKR-PO-001',
            'name' => 'M',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $colorId = DB::table('master_colors')->insertGetId([
            'code' => 'WRN-PO-001',
            'name' => 'Navy',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $unitId = DB::table('master_units')->insertGetId([
            'code' => 'UNT-PO-001',
            'name' => 'Pcs',
            'symbol' => 'pcs',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('master_products')->insert([
            'code' => 'BRG-PO-001',
            'name' => 'Polo Navy Raglan M',
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'model_id' => $modelId,
            'size_id' => $sizeId,
            'color_id' => $colorId,
            'unit_id' => $unitId,
            'fabric_name' => 'Cotton combed',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('master_ratios')->insert([
            'sub_category_id' => $subCategoryId,
            'size_id' => $sizeId,
            'ratio' => '0.3750',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'mydent']))
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('<datalist id="po-categories">', false)
            ->assertSee('<option value="POLO LINEN" data-category-id="'.$categoryId.'">', false)
            ->assertSee('<datalist id="po-products">', false)
            ->assertSee('value="Polo Navy Raglan M"', false)
            ->assertSee('value="Polo" data-sub-category-id="'.$subCategoryId.'" data-category-id="'.$categoryId.'"', false)
            ->assertSee('value="Kain" data-sub-category-id="'.$otherSubCategoryId.'" data-category-id="'.$otherCategoryId.'"', false)
            ->assertSee('data-po-category', false)
            ->assertSee('data-po-sub-category', false)
            ->assertSee('data-category="POLO LINEN"', false)
            ->assertSee('data-category-id="'.$categoryId.'"', false)
            ->assertSee('data-sub-category="Polo"', false)
            ->assertSee('data-model="Raglan"', false)
            ->assertSee('data-size="M"', false)
            ->assertSee('data-color="Navy"', false)
            ->assertSee('data-fabric="Cotton combed"', false)
            ->assertSee('<datalist id="po-ratios">', false)
            ->assertSee('data-sub-category-id="'.$subCategoryId.'" data-size="M"', false)
            ->assertSee('value="0.375"', false)
            ->assertSee('matchingRatio?.value ?? \'0.25\'', false);
    }

    public function test_purchase_order_grid_can_download_one_pdf_for_all_variants(): void
    {
        $first = $this->makeOrder('draft', 100);
        $first->update([
            'po_date' => '2026-10-25',
            'category' => 'Pakaian',
            'sub_category' => 'Kaos',
            'model' => 'Raglan',
            'size' => 'S',
            'color' => 'Navy',
            'yard_kg' => 25,
            'ratio' => 0.25,
            'ratio_result_qty' => 100,
        ]);
        $second = $this->makeOrder('draft', 200);
        $second->update([
            'po_number' => $first->po_number,
            'po_date' => '2026-10-25',
            'category' => 'Pakaian',
            'sub_category' => 'Kaos',
            'model' => 'Raglan',
            'size' => 'M',
            'color' => 'Navy',
            'yard_kg' => 50,
            'ratio' => 0.25,
            'ratio_result_qty' => 200,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('orders.pdf', $first))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=PO-'.str($first->po_number)->slug('-').'.pdf')
            ->assertSee('%PDF-', false);
    }

    public function test_manual_purchase_order_number_cannot_be_reused_for_a_new_order(): void
    {
        $existing = $this->makeOrder('draft', 10);
        $mydent = User::factory()->create(['role' => 'mydent']);

        $this->actingAs($mydent)
            ->post(route('orders.store'), [
                'po_number_mode' => 'manual',
                'po_number' => $existing->po_number,
                'po_date' => '2026-10-25',
                'items' => [[
                    'category' => 'Pakaian',
                    'sub_category' => 'Kaos',
                    'product_name' => 'Kaos',
                    'model' => 'Reguler',
                    'color' => 'Hitam',
                    'size' => 'L',
                    'yard_kg' => 10,
                    'ratio' => 0.25,
                ]],
            ])
            ->assertSessionHasErrors('po_number');

        $this->assertDatabaseCount('production_orders', 1);
    }

    public function test_closing_a_purchase_order_closes_all_its_variants(): void
    {
        $first = $this->makeOrder('delivered', 10);
        $second = $this->makeOrder('delivered', 20);
        $second->update(['po_number' => $first->po_number]);
        foreach ([[$first, 10], [$second, 20]] as [$item, $quantity]) {
            WorkflowEntry::create([
                'production_order_id' => $item->id,
                'action' => 'goods_receipt',
                'reference' => 'SJ-'.$item->id,
                'quantity' => $quantity,
            ]);
        }

        $mydent = User::factory()->create(['role' => 'mydent']);
        $this->actingAs($mydent)
            ->post(route('orders.actions.store', $first), [
                'action' => 'close_order',
                'notes' => 'Seluruh varian sudah direkonsiliasi.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('production_orders', ['id' => $first->id, 'status' => 'closed']);
        $this->assertDatabaseHas('production_orders', ['id' => $second->id, 'status' => 'closed']);
        $this->assertDatabaseCount('workflow_entries', 4);
    }

    public function test_only_gunung_karang_can_report_cutting(): void
    {
        $order = $this->makeOrder('spk_issued', 500);
        $order->update(['spk_quantity' => 100]);
        $mydent = User::factory()->create(['role' => 'mydent']);

        $this->actingAs($mydent)
            ->post(route('orders.actions.store', $order), [
                'action' => 'cutting_report',
                'quantity' => 100,
                'fabric_used' => 25,
                'yield_category' => 'target',
            ])
            ->assertForbidden();

        $operator = User::factory()->create(['role' => 'gunung_karang']);
        $this->actingAs($operator)
            ->post(route('orders.actions.store', $order), [
                'action' => 'cutting_report',
                'quantity' => 100,
                'fabric_used' => 25,
                'yield_category' => 'target',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('workflow_entries', [
            'production_order_id' => $order->id,
            'action' => 'cutting_report',
            'quantity' => 100,
        ]);
    }

    public function test_spk_target_cannot_exceed_the_purchase_order_quantity(): void
    {
        $order = $this->makeOrder('fabric_received', 100);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('orders.actions.store', $order), [
                'action' => 'issue_spk',
                'quantity' => 101,
            ])
            ->assertSessionHasErrors('action');

        $this->assertNull($order->fresh()->spk_quantity);
    }

    public function test_fabric_variance_requires_a_note(): void
    {
        $order = $this->makeOrder('fabric_in_transit', 100);
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'action' => 'deliver_fabric',
            'reference' => 'DO-TEST-001',
            'quantity' => 25,
        ]);

        $this->actingAs($warehouse)
            ->post(route('orders.actions.store', $order), [
                'action' => 'receive_fabric',
                'quantity' => 24,
            ])
            ->assertSessionHasErrors('notes');

        $this->post(route('orders.actions.store', $order), [
            'action' => 'receive_fabric',
            'quantity' => 24,
            'notes' => 'Selisih 1 yard setelah pengukuran ulang.',
        ])->assertRedirect();

        $this->assertDatabaseHas('production_orders', [
            'id' => $order->id,
            'fabric_delivered' => 24,
            'status' => 'fabric_received',
        ]);
    }

    public function test_fabric_delivery_and_receipt_preserve_fractional_yards(): void
    {
        $order = $this->makeOrder('fabric_requested', 100);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'action' => 'request_fabric',
        ]);
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($warehouse)
            ->post(route('orders.actions.store', $order), [
                'action' => 'deliver_fabric',
                'reference' => 'DO-FRAC-001',
                'quantity' => 25.75,
            ])
            ->assertRedirect();

        $this->post(route('orders.actions.store', $order), [
            'action' => 'receive_fabric',
            'quantity' => 25.5,
            'notes' => 'Susut 0.25 yard setelah pengukuran ulang.',
        ])->assertRedirect();

        $this->assertDatabaseHas('workflow_entries', [
            'production_order_id' => $order->id,
            'action' => 'receive_fabric',
            'quantity' => '25.50',
        ]);
    }

    public function test_closed_purchase_order_cannot_accept_new_transactions(): void
    {
        $order = $this->makeOrder('closed', 100);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('orders.actions.store', $order), [
                'action' => 'cutting_report',
                'quantity' => 20,
            ])
            ->assertStatus(409);

        $this->assertDatabaseMissing('workflow_entries', [
            'production_order_id' => $order->id,
            'action' => 'cutting_report',
        ]);
    }

    public function test_short_goods_receipt_requires_a_discrepancy_note(): void
    {
        $order = $this->makeOrder('shipped', 100);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'action' => 'shipment',
            'quantity' => 40,
            'reference' => 'SJ-TEST-001',
        ]);
        $mydent = User::factory()->create(['role' => 'mydent']);

        $this->actingAs($mydent)
            ->post(route('orders.actions.store', $order), [
                'action' => 'goods_receipt',
                'reference' => 'SJ-TEST-001',
                'quantity' => 38,
            ])
            ->assertSessionHasErrors('notes');

        $this->post(route('orders.actions.store', $order), [
            'action' => 'goods_receipt',
            'reference' => 'SJ-TEST-001',
            'quantity' => 38,
            'notes' => 'Kurang 2 pcs saat pemeriksaan fisik.',
        ])->assertRedirect();
    }

    public function test_sewn_garments_sent_to_a_vendor_do_not_count_as_cutting_pieces(): void
    {
        $order = $this->makeOrder('in_production', 100);
        $order->update(['spk_quantity' => 100]);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'action' => 'cutting_report',
            'quantity' => 100,
        ]);
        WorkflowEntry::create([
            'production_order_id' => $order->id,
            'action' => 'sewing',
            'quantity' => 30,
        ]);
        $operator = User::factory()->create(['role' => 'gunung_karang']);

        $this->actingAs($operator)
            ->post(route('orders.actions.store', $order), [
                'action' => 'vendor_send',
                'quantity' => 31,
                'reference' => 'DO-VND-001',
                'vendor' => 'Vendor Bordir',
                'process' => 'Bordir',
                'material_type' => 'Baju Jahit',
            ])
            ->assertSessionHasErrors('action');

        $this->post(route('orders.actions.store', $order), [
            'action' => 'vendor_send',
            'quantity' => 20,
            'reference' => 'DO-VND-001',
            'vendor' => 'Vendor Bordir',
            'process' => 'Bordir',
            'material_type' => 'Baju Jahit',
        ])->assertRedirect();

        $this->post(route('orders.actions.store', $order), [
            'action' => 'vendor_receive',
            'reference' => 'DO-VND-001',
            'material_type' => 'Baju Jahit',
            'accepted_quantity' => 18,
            'rejected_quantity' => 2,
        ])->assertRedirect();

        $this->assertSame(28, $order->fresh()->finishedQuantity());
        $this->assertSame(0, $order->fresh()->pendingVendorQuantity());
    }

    public function test_warehouse_can_issue_only_requested_material_quantity(): void
    {
        $order = $this->makeOrder('spk_issued', 100);
        $order->update(['spk_quantity' => 100]);
        $operator = User::factory()->create(['role' => 'gunung_karang']);

        $this->actingAs($operator)
            ->post(route('orders.actions.store', $order), [
                'action' => 'request_material',
                'reference' => 'Benang jahit',
                'quantity' => 10,
                'unit' => 'cone',
            ])
            ->assertRedirect();

        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $this->actingAs($warehouse)
            ->post(route('orders.actions.store', $order), [
                'action' => 'issue_material',
                'reference' => 'Benang jahit',
                'quantity' => 11,
                'unit' => 'cone',
                'batch' => 'LOT-001',
                'received_by' => 'Mandor GK',
            ])
            ->assertSessionHasErrors('action');

        $this->post(route('orders.actions.store', $order), [
            'action' => 'issue_material',
            'reference' => 'Benang jahit',
            'quantity' => 8,
            'unit' => 'cone',
            'batch' => 'LOT-001',
            'received_by' => 'Mandor GK',
        ])->assertRedirect();
    }

    public function test_purchase_order_completes_the_full_production_workflow(): void
    {
        $mydent = User::factory()->create(['role' => 'mydent']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $admin = User::factory()->create(['role' => 'admin']);
        $gunungKarang = User::factory()->create(['role' => 'gunung_karang']);
        $cibentang = User::factory()->create(['role' => 'cibentang']);

        $this->actingAs($mydent)->post(route('orders.store'), [
            'po_number' => 'MYD-FULL-001',
            'product_name' => 'Kaos workflow',
            'order_quantity' => 10,
            'fabric_estimate' => 2,
            'fabric_ratio' => 5,
        ])->assertRedirect();

        $order = ProductionOrder::query()->where('po_number', 'MYD-FULL-001')->firstOrFail();

        $this->postWorkflowAction($mydent, $order, 'request_fabric');
        $this->postWorkflowAction($warehouse, $order, 'deliver_fabric', [
            'reference' => 'DO-FAB-001',
            'quantity' => 2,
        ]);
        $this->postWorkflowAction($warehouse, $order, 'receive_fabric', ['quantity' => 2]);
        $this->postWorkflowAction($admin, $order, 'issue_spk', ['quantity' => 10]);
        $this->postWorkflowAction($gunungKarang, $order, 'cutting_report', [
            'quantity' => 10,
            'fabric_used' => 2,
            'yield_category' => 'target',
        ]);
        $this->postWorkflowAction($gunungKarang, $order, 'vendor_send', [
            'quantity' => 2,
            'reference' => 'DO-VND-001',
            'vendor' => 'Vendor Sablon',
            'process' => 'Sablon',
            'material_type' => 'Potongan Bahan',
        ]);
        $this->postWorkflowAction($gunungKarang, $order, 'vendor_receive', [
            'reference' => 'DO-VND-001',
            'material_type' => 'Potongan Bahan',
            'accepted_quantity' => 1,
            'rejected_quantity' => 1,
        ]);
        $this->postWorkflowAction($gunungKarang, $order, 'site_transfer', [
            'quantity' => 2,
            'reference' => 'TR-CIB-001',
        ]);
        $this->postWorkflowAction($cibentang, $order, 'site_receive', [
            'quantity' => 2,
            'reference' => 'TR-CIB-001',
        ]);
        $this->postWorkflowAction($gunungKarang, $order, 'site_return', [
            'quantity' => 2,
            'rejected_quantity' => 0,
            'reference' => 'TR-CIB-001',
        ]);
        $this->postWorkflowAction($gunungKarang, $order, 'sewing', ['quantity' => 7]);
        $this->postWorkflowAction($gunungKarang, $order, 'finishing', ['quantity' => 9]);
        $this->postWorkflowAction($gunungKarang, $order, 'quality_check', [
            'inspected_quantity' => 9,
            'passed_quantity' => 8,
            'rejected_quantity' => 1,
        ]);
        $this->postWorkflowAction($gunungKarang, $order, 'prepare_shipment', [
            'quantity' => 8,
            'reference' => 'SJ-001',
            'receiver' => 'Petugas Mydent',
        ]);
        $this->actingAs($mydent)
            ->post(route('orders.actions.store', $order), [
                'action' => 'goods_receipt',
                'reference' => 'SJ-001',
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('action');
        $this->postWorkflowAction($gunungKarang, $order, 'shipment', [
            'reference' => 'SJ-001',
        ]);
        $this->postWorkflowAction($mydent, $order, 'goods_receipt', [
            'quantity' => 8,
            'reference' => 'SJ-001',
        ]);
        $this->postWorkflowAction($mydent, $order, 'close_order', [
            'notes' => 'Disetujui ditutup dengan selisih 2 pcs terhadap target PO.',
        ]);

        $this->assertSame('closed', $order->fresh()->status);
        $this->assertDatabaseCount('workflow_entries', 18);
    }

    public function test_admin_can_manage_users_and_branches_and_protects_the_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.role-access.index'))
            ->assertOk()
            ->assertSee('Matriks Peran')
            ->assertSee('Cibentang');

        $this->get(route('admin.users.index'))->assertOk()->assertSee('Tambah pengguna');
        $this->post(route('admin.branches.store'), [
            'name' => 'Cabang Uji',
            'address' => 'Alamat uji',
            'phone' => '08123456789',
        ])->assertRedirect(route('admin.branches.index'));

        $branch = Branch::query()->where('name', 'Cabang Uji')->firstOrFail();
        $this->assertStringStartsWith('CBG-', $branch->code);
        $this->get(route('admin.branches.index'))->assertOk()->assertSee('Cabang Uji');

        $this->post(route('admin.users.store'), [
            'name' => 'Operator Cabang',
            'email' => 'operator.cabang@example.test',
            'role' => 'gunung_karang',
            'branch_id' => $branch->id,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('admin.users.index'));

        $operator = User::query()->where('email', 'operator.cabang@example.test')->firstOrFail();
        $this->assertSame($branch->id, $operator->branch_id);
        $this->assertTrue(Hash::check('secure-password', $operator->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'user_created', 'subject_id' => (string) $operator->id]);

        $this->put(route('admin.users.update', $operator), [
            'name' => 'Operator GK',
            'email' => $operator->email,
            'role' => 'gunung_karang',
            'branch_id' => '',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertNull($operator->fresh()->branch_id);

        $this->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'mydent',
            'branch_id' => '',
            'password' => '',
            'password_confirmation' => '',
        ])->assertStatus(422);

        $this->actingAs($operator)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_reports_filter_by_branch_and_export_pdf_and_excel_compatible_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $branch = Branch::query()->create([
            'code' => 'CBG-REPORT',
            'name' => 'Cabang Laporan',
        ]);
        $order = $this->makeOrder('in_production', 100);
        $order->update(['branch_id' => $branch->id, 'po_date' => now()->toDateString()]);
        WorkflowEntry::create(['production_order_id' => $order->id, 'action' => 'cutting_report', 'quantity' => 95]);
        WorkflowEntry::create(['production_order_id' => $order->id, 'action' => 'sewing', 'quantity' => 90]);
        WorkflowEntry::create(['production_order_id' => $order->id, 'action' => 'quality_check', 'quantity' => 88, 'secondary_quantity' => 2]);
        WorkflowEntry::create(['production_order_id' => $order->id, 'action' => 'goods_receipt', 'quantity' => 87]);

        $query = ['from' => '2000-01-01', 'to' => '2099-12-31', 'branch_id' => $branch->id];
        $this->actingAs($admin)
            ->get(route('reports.index', $query))
            ->assertOk()
            ->assertSee($order->po_number)
            ->assertSee('Cabang Laporan')
            ->assertSee('Diterima Mydent')
            ->assertSee('87 pcs');

        $this->get(route('reports.pdf', $query))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF-', false);
        $excelResponse = $this->get(route('reports.excel', $query))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($order->po_number, $excelResponse->streamedContent());
        $this->assertStringContainsString('Hasil Cutting (pcs)', $excelResponse->streamedContent());
    }

    public function test_cutting_menu_is_limited_to_production_roles_and_links_to_cutting_form(): void
    {
        $order = $this->makeOrder('spk_issued', 100);
        $order->update(['spk_quantity' => 100]);

        $this->actingAs(User::factory()->create(['role' => 'gunung_karang']))
            ->get(route('cutting.index'))
            ->assertOk()
            ->assertSee($order->po_number)
            ->assertSee(route('orders.show', $order).'#workflow-actions');

        $this->actingAs(User::factory()->create(['role' => 'warehouse']))
            ->get(route('cutting.index'))
            ->assertForbidden();
    }

    public function test_warehouse_can_manage_stock_cards_without_allowing_negative_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $productId = $this->createStockCardProduct();

        $this->actingAs($warehouse)
            ->get(route('stock-cards.index', ['product_id' => $productId]))
            ->assertOk()
            ->assertSee('Kartu Stok')
            ->assertSee('Kain Uji')
            ->assertSee('Stok masuk / saldo awal');
        $this->post(route('stock-cards.store'), [
            'master_product_id' => $productId,
            'movement_type' => 'in',
            'quantity' => '15.500',
            'reference' => 'DO-STOCK-001',
            'notes' => 'Saldo awal gudang',
        ])->assertRedirect(route('stock-cards.index', ['product_id' => $productId]));

        $this->post(route('stock-cards.store'), [
            'master_product_id' => $productId,
            'movement_type' => 'out',
            'quantity' => '4.250',
            'reference' => 'ISSUE-001',
        ])->assertRedirect();

        $this->get(route('stock-cards.index', ['product_id' => $productId]))
            ->assertOk()
            ->assertSee('Saldo 11.250 m')
            ->assertSee('Saldo 15.500 m');
        $this->post(route('stock-cards.store'), [
            'master_product_id' => $productId,
            'movement_type' => 'out',
            'quantity' => '11.251',
        ])->assertStatus(422);

        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('audit_logs', ['event' => 'stock_in']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'stock_out']);
        $this->actingAs(User::factory()->create(['role' => 'mydent']))
            ->get(route('stock-cards.index'))
            ->assertForbidden();
        $this->actingAs($admin)->get(route('stock-cards.index'))->assertOk();
    }

    public function test_super_admin_is_a_distinct_local_account_with_full_access_and_protected_assignment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        config(['app.demo_password' => 'super-admin-test-password']);
        (new SuperAdminAccountSeeder)->run();
        $this->app->detectEnvironment(fn (): string => 'testing');
        $superAdmin = User::query()->where('email', 'superadmin@konveksi.test')->firstOrFail();
        $this->assertSame('super_admin', $superAdmin->role);
        $this->assertTrue(Hash::check('super-admin-test-password', $superAdmin->password));

        $this->actingAs($superAdmin)
            ->get(route('admin.role-access.index'))
            ->assertOk()
            ->assertSee('Super Admin')
            ->assertSee('Kartu Stok');
        $this->get(route('master-data.products'))->assertOk();
        $this->get(route('orders.create'))->assertOk();
        $this->get(route('delivery-orders.create'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk()->assertSee('superadmin@konveksi.test');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('superadmin@konveksi.test');
        $this->put(route('admin.users.update', $superAdmin), [
            'name' => 'Super Admin',
            'email' => 'superadmin@konveksi.test',
            'role' => 'admin',
            'password' => '',
            'password_confirmation' => '',
        ])->assertForbidden();
        $this->post(route('admin.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'new.super.admin@example.test',
            'role' => 'super_admin',
            'password' => 'new-admin-password',
            'password_confirmation' => 'new-admin-password',
        ])->assertSessionHasErrors('role');
    }

    private function createStockCardProduct(): int
    {
        $now = now();
        $unitId = DB::table('master_units')->insertGetId([
            'code' => 'UNT-STOCK',
            'name' => 'Meter',
            'symbol' => 'm',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $categoryId = DB::table('master_categories')->insertGetId([
            'code' => 'KTG-STOCK',
            'name' => 'Kain',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subCategoryId = DB::table('master_sub_categories')->insertGetId([
            'category_id' => $categoryId,
            'code' => 'SUB-STOCK',
            'name' => 'Bahan Utama',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $modelId = DB::table('master_models')->insertGetId([
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'code' => 'MDL-STOCK',
            'name' => 'Kain Polos',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sizeId = DB::table('master_sizes')->insertGetId([
            'code' => 'UKR-STOCK',
            'name' => 'All Size',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $colorId = DB::table('master_colors')->insertGetId([
            'code' => 'WRN-STOCK',
            'name' => 'Putih',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('master_products')->insertGetId([
            'code' => 'BRG-STOCK',
            'name' => 'Kain Uji',
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'model_id' => $modelId,
            'size_id' => $sizeId,
            'color_id' => $colorId,
            'unit_id' => $unitId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function postWorkflowAction(User $user, ProductionOrder $order, string $action, array $data = []): void
    {
        $this->actingAs($user)
            ->post(route('orders.actions.store', $order), ['action' => $action, ...$data])
            ->assertRedirect(route('orders.show', $order));
    }

    private function makeOrder(string $status, int $quantity): ProductionOrder
    {
        return ProductionOrder::create([
            'po_number' => 'MYD-'.strtoupper(fake()->unique()->bothify('???-#####')),
            'product_name' => 'Produk uji',
            'order_quantity' => $quantity,
            'fabric_estimate' => 25,
            'fabric_ratio' => 4,
            'status' => $status,
            'created_by' => User::factory()->create(['role' => 'mydent'])->id,
        ]);
    }
}
