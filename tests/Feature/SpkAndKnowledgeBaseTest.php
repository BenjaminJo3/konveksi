<?php

namespace Tests\Feature;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpkAndKnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_base_can_show_all_modules_and_filter_by_menu(): void
    {
        $user = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($user)
            ->get(route('knowledge-base.index'))
            ->assertOk()
            ->assertSee('Purchase Order')
            ->assertSee('Kartu stok');

        $this->get(route('knowledge-base.index', ['menu' => 'spk']))
            ->assertOk()
            ->assertSee('Surat Perintah Kerja (SPK)')
            ->assertDontSee('Tinjau saldo berjalan dan catat penerimaan atau pengeluaran barang.');
    }

    public function test_super_admin_can_edit_knowledge_base_and_regular_admin_cannot(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('knowledge-base.index'))
            ->assertOk()
            ->assertSee('Edit panduan');

        $article = DB::table('knowledge_base_articles')->where('key', 'spk')->first();
        $this->get(route('knowledge-base.edit', $article->id))->assertOk();
        $this->put(route('knowledge-base.update', $article->id), [
            'category' => 'Produksi',
            'title' => 'Panduan SPK terbaru',
            'summary' => 'Instruksi SPK diperbarui.',
            'steps' => "Langkah satu\nLangkah dua",
        ])->assertRedirect(route('knowledge-base.index', ['menu' => 'spk']));

        $this->assertDatabaseHas('knowledge_base_articles', [
            'id' => $article->id,
            'title' => 'Panduan SPK terbaru',
        ]);
        $this->assertSame(['Langkah satu', 'Langkah dua'], json_decode(
            DB::table('knowledge_base_articles')->where('id', $article->id)->value('steps'),
            true,
        ));
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'knowledge_base_article_updated',
            'subject_id' => (string) $article->id,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('knowledge-base.edit', $article->id))
            ->assertForbidden();
    }

    public function test_spk_menu_is_available_to_gunung_karang_and_lists_issued_orders(): void
    {
        $user = User::factory()->create(['role' => 'gunung_karang']);
        $group = $this->makeIssuedSpk('SPK-LIST-001');

        $this->actingAs($user)
            ->get(route('spk.index'))
            ->assertOk()
            ->assertSee('SPK-SPK-LIST-001')
            ->assertSee('Menunggu tanda tangan');

        $this->get(route('spk.show', $group))
            ->assertOk()
            ->assertSee('SURAT PERINTAH KERJA')
            ->assertSee('Kaos produksi');
    }

    public function test_spk_can_be_signed_from_canvas_and_locked_afterwards(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $group = $this->makeIssuedSpk('SPK-SIGN-001');
        $signature = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AT//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/AT//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IT//2Q==';

        $this->actingAs($admin)
            ->post(route('spk.sign', $group), ['signature_data' => $signature])
            ->assertRedirect(route('spk.show', $group))
            ->assertSessionHas('success');

        $this->assertNotNull($group->fresh()->spk_signed_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'spk_signed',
            'subject_type' => 'production_order_group',
            'subject_id' => (string) $group->id,
        ]);
        Storage::disk('local')->assertExists($group->fresh()->spk_signature_path);

        $this->post(route('spk.sign', $group), ['signature_data' => $signature])
            ->assertStatus(409);

        $this->get(route('spk.pdf', $group))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_only_authorized_production_users_can_access_or_sign_spk(): void
    {
        $group = $this->makeIssuedSpk('SPK-RBAC-001');
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($warehouse)
            ->get(route('spk.index'))
            ->assertForbidden();

        $this->post(route('spk.sign', $group), [])
            ->assertForbidden();
    }

    public function test_spk_can_be_signed_with_uploaded_jpeg(): void
    {
        Storage::fake('local');
        $manager = User::factory()->create(['role' => 'gunung_karang']);
        $group = $this->makeIssuedSpk('SPK-UPLOAD-001');
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AT//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/AT//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Aqf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IT//2Q==', true);

        $this->actingAs($manager)
            ->post(route('spk.sign', $group), [
                'signature_image' => UploadedFile::fake()->createWithContent('signature.jpg', $jpeg),
            ])
            ->assertRedirect(route('spk.show', $group))
            ->assertSessionHas('success');

        $this->assertSame('image/jpeg', Storage::disk('local')->mimeType($group->fresh()->spk_signature_path));
    }

    private function makeIssuedSpk(string $poNumber): ProductionOrderGroup
    {
        $creator = User::factory()->create(['role' => 'mydent']);
        $group = ProductionOrderGroup::query()->create([
            'po_number' => $poNumber,
            'po_date' => now()->toDateString(),
            'created_by' => $creator->id,
        ]);
        ProductionOrder::query()->create([
            'po_number' => $poNumber,
            'po_date' => now()->toDateString(),
            'product_name' => 'Kaos produksi',
            'order_quantity' => 30,
            'spk_quantity' => 28,
            'status' => 'spk_issued',
            'created_by' => $creator->id,
        ]);

        return $group;
    }
}
