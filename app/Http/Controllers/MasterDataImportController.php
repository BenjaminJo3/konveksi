<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\MasterDataCodeGenerator;
use App\Services\MasterDataSpreadsheetReader;
use App\Services\MasterProductName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataImportController extends Controller
{
    private const HEADER_ALIASES = [
        'category' => ['BAHANKATEGORI', 'KATEGORI', 'CATEGORY'],
        'sub_category' => ['SUBKATEGORI', 'SUBCATEGORY'],
        'size' => ['UKURAN', 'SIZE'],
        'model' => ['ARTIKEL', 'MODEL', 'ARTICLE'],
        'color' => ['WARNA', 'COLOR'],
        'fabric' => ['JENISKAIN', 'JENISBAHAN', 'BAHAN', 'FABRIC', 'MATERIAL'],
        'name' => ['NAMABARANG', 'NAMA', 'PRODUCTNAME', 'BARANG'],
    ];

    public function index(): View
    {
        return view('master-data.import');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['KATEGORI', 'SUB_KATEGORI', 'UKURAN', 'ARTIKEL', 'WARNA', 'JENIS KAIN', 'NAMA BARANG']);
            fputcsv($output, ['CELANA', 'JOGER', 'All Size', '', 'ABU', 'CHINOS', 'CELANA JOGER All Size ABU CHINOS']);
            fclose($output);
        }, 'template-master-data.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(
        Request $request,
        MasterDataSpreadsheetReader $reader,
        MasterDataCodeGenerator $codes,
    ): RedirectResponse {
        $values = $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt,xlsx', 'max:10240'],
        ]);
        $rows = $reader->rows($values['file']);
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi baris header dan data.']);
        }

        $headers = $this->mapHeaders(array_shift($rows));
        $requiredHeaders = ['category', 'sub_category', 'color'];
        $missing = array_diff($requiredHeaders, array_keys($headers));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Kolom wajib tidak ditemukan: '.$this->headerLabels($missing).'.',
            ]);
        }

        $dataRows = $rows;
        if (! collect($dataRows)->contains(fn (array $row): bool => count(array_filter(
            $row,
            fn (mixed $value): bool => trim((string) $value) !== '',
        )) > 0)) {
            throw ValidationException::withMessages(['file' => 'File belum berisi baris data barang.']);
        }
        $result = DB::transaction(function () use ($dataRows, $headers, $request, $codes): array {
            $imported = 0;
            $duplicates = 0;

            foreach ($dataRows as $index => $row) {
                $lineNumber = $index + 2;
                $record = ['fabric' => '', 'model' => '', 'size' => ''];
                foreach ($headers as $key => $column) {
                    $record[$key] = trim((string) ($row[$column] ?? ''));
                }
                if (count(array_filter($record, fn (string $value): bool => $value !== '')) === 0) {
                    continue;
                }

                foreach (['category', 'sub_category', 'color'] as $required) {
                    if (($record[$required] ?? '') === '') {
                        throw ValidationException::withMessages([
                            'file' => "Baris {$lineNumber}: kolom {$this->headerLabel($required)} wajib diisi.",
                        ]);
                    }
                }

                $record['category'] = $this->bounded($record['category'], 100, $lineNumber, 'KATEGORI');
                $record['sub_category'] = $this->bounded($record['sub_category'], 100, $lineNumber, 'SUB_KATEGORI');
                $record['model'] = $record['model'] === '' ? null : $this->bounded($record['model'], 120, $lineNumber, 'ARTIKEL');
                $record['size'] = $record['size'] === '' ? 'All Size' : $this->bounded($record['size'], 40, $lineNumber, 'UKURAN');
                $record['color'] = $this->bounded($record['color'], 80, $lineNumber, 'WARNA');
                $record['fabric'] = $record['fabric'] === '' ? null : $this->bounded($record['fabric'], 120, $lineNumber, 'JENIS KAIN');
                $categoryId = $this->findOrCreate('master_categories', ['name' => $record['category']], 'KTG', $codes);
                $subCategoryId = $this->findOrCreate('master_sub_categories', [
                    'category_id' => $categoryId,
                    'name' => $record['sub_category'],
                ], 'SUB', $codes);
                $modelId = $record['model'] === null ? null : $this->findOrCreate('master_models', [
                    'category_id' => $categoryId,
                    'sub_category_id' => $subCategoryId,
                    'name' => $record['model'],
                ], 'MDL', $codes);
                $sizeId = $this->findOrCreate('master_sizes', ['name' => $record['size']], 'UKR', $codes);
                $colorId = $this->findOrCreate('master_colors', ['name' => $record['color']], 'WRN', $codes);
                $unitId = $this->findOrCreate('master_units', ['name' => 'Pcs', 'symbol' => 'pcs'], 'UNT', $codes);
                $name = app(MasterProductName::class)->make(
                    $record['category'],
                    $record['sub_category'],
                    $record['size'],
                    $record['color'],
                    $record['fabric'],
                );

                $product = DB::table('master_products')
                    ->where('category_id', $categoryId)
                    ->where('sub_category_id', $subCategoryId)
                    ->where('model_id', $modelId)
                    ->where('size_id', $sizeId)
                    ->where('color_id', $colorId)
                    ->where('fabric_name', $record['fabric'])
                    ->first();
                if ($product) {
                    $duplicates++;

                    continue;
                }

                DB::table('master_products')->insert([
                    'code' => $codes->generate('BRG', 'master_products'),
                    'name' => $name,
                    'category_id' => $categoryId,
                    'sub_category_id' => $subCategoryId,
                    'model_id' => $modelId,
                    'size_id' => $sizeId,
                    'color_id' => $colorId,
                    'unit_id' => $unitId,
                    'fabric_name' => $record['fabric'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $imported++;
            }

            if ($imported > 0) {
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'event' => 'master_data_imported',
                    'subject_type' => 'master_products',
                    'description' => "{$imported} master barang diimpor; {$duplicates} duplikat dilewati.",
                    'metadata' => ['imported' => $imported, 'duplicates' => $duplicates],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            return compact('imported', 'duplicates');
        });

        return redirect()->route('master-data.products')->with(
            'success',
            "{$result['imported']} barang berhasil diimpor; {$result['duplicates']} duplikat dilewati.",
        );
    }

    private function mapHeaders(array $row): array
    {
        $normalizedHeaders = [];
        foreach ($row as $index => $header) {
            $normalizedHeaders[$index] = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', trim((string) $header)));
        }
        $mapping = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($normalizedHeaders as $index => $header) {
                if (in_array($header, $aliases, true)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }
        if (! isset($mapping['category'])) {
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === 'BAHAN') {
                    $mapping['category'] = $index;
                    unset($mapping['fabric']);
                    break;
                }
            }
        }

        return $mapping;
    }

    private function findOrCreate(string $table, array $attributes, string $prefix, MasterDataCodeGenerator $codes): int
    {
        $query = DB::table($table);
        foreach ($attributes as $column => $value) {
            if ($column === 'name') {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
            } else {
                $query->where($column, $value);
            }
        }
        $existing = $query->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $now = now();
        $id = DB::table($table)->insertGetId($attributes + [
            'code' => $codes->generate($prefix, $table),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $id;
    }

    private function bounded(string $value, int $max, int $line, string $label): string
    {
        if (mb_strlen($value) > $max) {
            throw ValidationException::withMessages([
                'file' => "Baris {$line}: nilai {$label} melebihi {$max} karakter.",
            ]);
        }

        return $value;
    }

    private function headerLabels(array $fields): string
    {
        return implode(', ', array_map(fn (string $field): string => $this->headerLabel($field), $fields));
    }

    private function headerLabel(string $field): string
    {
        return [
            'category' => 'KATEGORI',
            'sub_category' => 'SUB_KATEGORI',
            'size' => 'UKURAN',
            'model' => 'ARTIKEL',
            'color' => 'WARNA',
            'fabric' => 'JENIS KAIN',
            'name' => 'NAMA BARANG',
        ][$field] ?? $field;
    }
}
