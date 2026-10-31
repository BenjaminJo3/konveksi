<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class MasterDataImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_csv_master_data_using_example_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = implode("\n", [
            'BAHAN / KATEGORI;SUB_KATEGORI;UKURAN;ARTIKEL;WARNA;NAMA BARANG',
            'RAYON;CIPIR PENDEK;;BUNGA;UNGU;CIPIR PENDEK BUNGA UNGU',
            'RAYON;CIPIR PENDEK;;PITA;NAVY;CIPIR PENDEK NAVY',
        ]);

        $this->actingAs($admin)
            ->post(route('master-data.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('master-data.csv', $csv),
            ])
            ->assertRedirect(route('master-data.products'))
            ->assertSessionHas('success', '2 barang berhasil diimpor; 0 duplikat dilewati.');

        $this->assertDatabaseCount('master_products', 2);
        $this->assertDatabaseHas('master_categories', ['name' => 'RAYON']);
        $this->assertDatabaseHas('master_sub_categories', ['name' => 'CIPIR PENDEK']);
        $this->assertDatabaseHas('master_sizes', ['name' => 'All Size']);
        $this->assertDatabaseHas('master_models', ['name' => 'BUNGA']);
        $this->assertDatabaseHas('master_colors', ['name' => 'UNGU']);
        $this->assertDatabaseHas('master_units', ['name' => 'Pcs', 'symbol' => 'pcs']);
        $this->assertDatabaseHas('master_products', [
            'name' => 'RAYON CIPIR PENDEK All Size UNGU',
        ]);
    }

    public function test_import_keeps_category_and_fabric_separate_and_suggests_existing_fabrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = implode("\n", [
            'KATEGORI;SUB_KATEGORI;UKURAN;ARTIKEL;WARNA;JENIS KAIN',
            'CELANA;JOGER;M;;ABU;CHINOS',
        ]);

        $this->actingAs($admin)
            ->post(route('master-data.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('categories-and-fabrics.csv', $csv),
            ])
            ->assertRedirect(route('master-data.products'))
            ->assertSessionHas('success', '1 barang berhasil diimpor; 0 duplikat dilewati.');

        $this->assertDatabaseHas('master_categories', ['name' => 'CELANA']);
        $this->assertDatabaseMissing('master_categories', ['name' => 'CHINOS']);
        $this->assertDatabaseCount('master_models', 0);
        $this->assertDatabaseHas('master_products', [
            'name' => 'CELANA JOGER M ABU CHINOS',
            'fabric_name' => 'CHINOS',
            'model_id' => null,
        ]);

        $this->get(route('master-data.products.create'))
            ->assertOk()
            ->assertSee('list="master-fabrics"', false)
            ->assertSee('value="CHINOS"', false);
    }

    public function test_admin_can_import_more_than_one_thousand_csv_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rows = ['BAHAN / KATEGORI;SUB_KATEGORI;UKURAN;ARTIKEL;WARNA'];
        for ($index = 1; $index <= 1001; $index++) {
            $rows[] = "RAYON;CIPIR PENDEK;M;ARTIKEL {$index};UNGU";
        }

        $this->actingAs($admin)
            ->post(route('master-data.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('master-data.csv', implode("\n", $rows)),
            ])
            ->assertRedirect(route('master-data.products'))
            ->assertSessionHas('success', '1001 barang berhasil diimpor; 0 duplikat dilewati.');

        $this->assertDatabaseCount('master_products', 1001);
    }

    public function test_invalid_upload_is_atomic_and_duplicate_products_are_skipped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $missingHeaders = "BAHAN,KATEGORI\nRAYON,CIPIR PENDEK";

        $this->actingAs($admin)
            ->post(route('master-data.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('invalid.csv', $missingHeaders),
            ])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('master_products', 0);

        $invalidRow = implode("\n", [
            'BAHAN,KATEGORI,SUB_KATEGORI,UKURAN,ARTIKEL,WARNA,NAMA BARANG',
            'RAYON,,,,BUNGA,UNGU,Barang gagal',
        ]);
        $this->post(route('master-data.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('invalid-row.csv', $invalidRow),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('master_products', 0);
        $this->assertDatabaseCount('master_categories', 0);

        $validRow = implode("\n", [
            'BAHAN/KATEGORI;SUB_KATEGORI;UKURAN;ARTIKEL;WARNA',
            'RAYON;CIPIR PENDEK;M;BUNGA;UNGU',
        ]);
        $upload = ['file' => UploadedFile::fake()->createWithContent('valid.csv', $validRow)];
        $this->post(route('master-data.import.store'), $upload)->assertSessionHas('success');
        $this->assertDatabaseHas('master_products', ['name' => 'RAYON CIPIR PENDEK M UNGU']);
        $duplicateUpload = ['file' => UploadedFile::fake()->createWithContent('duplicate.csv', $validRow)];
        $this->post(route('master-data.import.store'), $duplicateUpload)
            ->assertSessionHas('success', '0 barang berhasil diimpor; 1 duplikat dilewati.');
        $this->assertDatabaseCount('master_products', 1);
    }

    public function test_admin_can_import_xlsx_with_inline_string_cells(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'masterdata-');
        $archive = new ZipArchive;
        $archive->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Master Data" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/></Relationships>');
        $archive->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>BAHAN / KATEGORI</t></is></c><c r="B1" t="inlineStr"><is><t>SUB_KATEGORI</t></is></c><c r="C1" t="inlineStr"><is><t>UKURAN</t></is></c><c r="D1" t="inlineStr"><is><t>ARTIKEL</t></is></c><c r="E1" t="inlineStr"><is><t>WARNA</t></is></c><c r="F1" t="inlineStr"><is><t>NAMA BARANG</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>RAYON</t></is></c><c r="B2" t="inlineStr"><is><t>CIPIR PENDEK</t></is></c><c r="D2" t="inlineStr"><is><t>BUNGA</t></is></c><c r="E2" t="inlineStr"><is><t>UNGU</t></is></c><c r="F2" t="inlineStr"><is><t>CIPIR PENDEK BUNGA UNGU</t></is></c></row></sheetData></worksheet>');
        $archive->close();

        try {
            $file = new UploadedFile($temporaryFile, 'master-data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->actingAs($admin)
                ->post(route('master-data.import.store'), ['file' => $file])
                ->assertRedirect(route('master-data.products'))
                ->assertSessionHas('success', '1 barang berhasil diimpor; 0 duplikat dilewati.');
            $this->assertDatabaseHas('master_products', ['name' => 'RAYON CIPIR PENDEK All Size UNGU']);
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function test_only_admin_can_access_master_data_upload(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($warehouse)
            ->get(route('master-data.import'))
            ->assertForbidden();
    }
}
