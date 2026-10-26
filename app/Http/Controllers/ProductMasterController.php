<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\MasterDataCodeGenerator;
use App\Services\MasterProductName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductMasterController extends Controller
{
    private const MASTERS = [
        'units' => ['table' => 'master_units', 'label' => 'Unit', 'singular' => 'Unit', 'prefix' => 'UNT'],
        'conversions' => ['table' => 'master_unit_conversions', 'label' => 'Konversi Unit', 'singular' => 'Konversi', 'prefix' => 'KVS'],
        'categories' => ['table' => 'master_categories', 'label' => 'Kategori', 'singular' => 'Kategori', 'prefix' => 'KTG'],
        'sub-categories' => ['table' => 'master_sub_categories', 'label' => 'Sub-kategori', 'singular' => 'Sub-kategori', 'prefix' => 'SUB'],
        'models' => ['table' => 'master_models', 'label' => 'Model / Artikel', 'singular' => 'Model / Artikel', 'prefix' => 'MDL'],
        'sizes' => ['table' => 'master_sizes', 'label' => 'Ukuran', 'singular' => 'Ukuran', 'prefix' => 'UKR'],
        'colors' => ['table' => 'master_colors', 'label' => 'Warna', 'singular' => 'Warna', 'prefix' => 'WRN'],
    ];

    public function products(Request $request): View
    {
        $query = DB::table('master_products as products')
            ->join('master_categories as categories', 'categories.id', '=', 'products.category_id')
            ->join('master_sub_categories as sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->join('master_models as models', 'models.id', '=', 'products.model_id')
            ->join('master_sizes as sizes', 'sizes.id', '=', 'products.size_id')
            ->join('master_colors as colors', 'colors.id', '=', 'products.color_id')
            ->join('master_units as units', 'units.id', '=', 'products.unit_id')
            ->select([
                'products.*',
                'categories.name as category_name',
                'sub_categories.name as sub_category_name',
                'models.name as model_name',
                'sizes.name as size_name',
                'colors.name as color_name',
                'colors.hex_color',
                'units.symbol as unit_symbol',
            ])
            ->orderByDesc('products.id');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('products.code', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('models.name', 'like', "%{$search}%")
                    ->orWhere('colors.name', 'like', "%{$search}%");
            });
        }

        return view('master-data.products', [
            'products' => $query->paginate(24)->withQueryString(),
            'viewMode' => $request->string('view')->value() === 'kanban' ? 'kanban' : 'grid',
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function barcodes(Request $request): View
    {
        $query = DB::table('master_products as products')
            ->join('master_categories as categories', 'categories.id', '=', 'products.category_id')
            ->join('master_sub_categories as sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->join('master_models as models', 'models.id', '=', 'products.model_id')
            ->join('master_sizes as sizes', 'sizes.id', '=', 'products.size_id')
            ->join('master_colors as colors', 'colors.id', '=', 'products.color_id')
            ->where('products.is_active', true)
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'categories.name as category_name',
                'sub_categories.name as sub_category_name',
                'models.name as model_name',
                'sizes.name as size_name',
                'colors.name as color_name',
            ])
            ->orderBy('products.name');
        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('products.code', 'like', "%{$search}%")
                ->orWhere('products.name', 'like', "%{$search}%")
                ->orWhere('models.name', 'like', "%{$search}%")
                ->orWhere('colors.name', 'like', "%{$search}%"));
        }

        return view('barcodes.index', [
            'products' => $query->limit(50)->get(),
            'search' => $search,
        ]);
    }

    public function createProduct(): View
    {
        return view('master-data.product-form', [
            'masters' => [
                'categories' => DB::table('master_categories')->orderBy('name')->get(),
                'subCategories' => DB::table('master_sub_categories')->orderBy('name')->get(),
                'models' => DB::table('master_models')->orderBy('name')->get(),
                'sizes' => DB::table('master_sizes')->orderBy('name')->get(),
                'colors' => DB::table('master_colors')->orderBy('name')->get(),
                'units' => DB::table('master_units')->orderBy('name')->get(),
            ],
        ]);
    }

    public function storeProduct(Request $request, MasterProductName $productName): RedirectResponse
    {
        $values = $request->validate([
            'category_id' => ['required', 'integer', 'exists:master_categories,id'],
            'sub_category_id' => ['required', 'integer', 'exists:master_sub_categories,id'],
            'model_id' => ['required', 'integer', 'exists:master_models,id'],
            'size_id' => ['required', 'integer', 'exists:master_sizes,id'],
            'color_id' => ['required', 'integer', 'exists:master_colors,id'],
            'unit_id' => ['required', 'integer', 'exists:master_units,id'],
            'fabric_name' => ['nullable', 'string', 'max:120'],
        ]);

        $this->assertProductHierarchy($values);
        $values['name'] = $productName->make(
            DB::table('master_sub_categories')->where('id', $values['sub_category_id'])->value('name'),
            DB::table('master_colors')->where('id', $values['color_id'])->value('name'),
            DB::table('master_models')->where('id', $values['model_id'])->value('name'),
            DB::table('master_sizes')->where('id', $values['size_id'])->value('name'),
        );
        $values['code'] = app(MasterDataCodeGenerator::class)->generate('BRG', 'master_products');
        DB::table('master_products')->insert($values + [
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'master_product_created',
            'subject_type' => 'master_product',
            'subject_id' => $values['code'],
            'description' => 'Master barang '.$values['name'].' dibuat.',
            'metadata' => $values,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('master-data.products')->with('success', 'Master barang berhasil disimpan.');
    }

    public function index(string $type): View
    {
        $config = $this->masterConfig($type);
        $rows = DB::table($config['table'])->orderByDesc('id')->paginate(20);

        if ($type === 'conversions') {
            $rows = DB::table('master_unit_conversions as conversions')
                ->join('master_units as source', 'source.id', '=', 'conversions.from_unit_id')
                ->join('master_units as target', 'target.id', '=', 'conversions.to_unit_id')
                ->select('conversions.*', 'source.name as source_name', 'source.symbol as source_symbol', 'target.name as target_name', 'target.symbol as target_symbol')
                ->orderByDesc('conversions.id')
                ->paginate(20);
        } elseif ($type === 'sub-categories') {
            $rows = DB::table('master_sub_categories as children')
                ->join('master_categories as parents', 'parents.id', '=', 'children.category_id')
                ->select('children.*', 'parents.name as category_name')
                ->orderByDesc('children.id')->paginate(20);
        } elseif ($type === 'models') {
            $rows = DB::table('master_models as models')
                ->join('master_categories as categories', 'categories.id', '=', 'models.category_id')
                ->join('master_sub_categories as sub_categories', 'sub_categories.id', '=', 'models.sub_category_id')
                ->select('models.*', 'categories.name as category_name', 'sub_categories.name as sub_category_name')
                ->orderByDesc('models.id')->paginate(20);
        }

        return view('master-data.index', compact('config', 'rows', 'type'));
    }

    public function create(string $type): View
    {
        $config = $this->masterConfig($type);

        return view('master-data.form', [
            'config' => $config,
            'type' => $type,
            'masters' => [
                'categories' => DB::table('master_categories')->orderBy('name')->get(),
                'subCategories' => DB::table('master_sub_categories')->orderBy('name')->get(),
                'units' => DB::table('master_units')->orderBy('name')->get(),
            ],
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $config = $this->masterConfig($type);
        $values = match ($type) {
            'units' => $request->validate([
                'name' => ['required', 'string', 'max:80', 'unique:master_units,name'],
                'symbol' => ['required', 'string', 'max:20'],
            ]),
            'categories' => $request->validate([
                'name' => ['required', 'string', 'max:100', 'unique:master_categories,name'],
            ]),
            'sub-categories' => $request->validate([
                'category_id' => ['required', 'integer', 'exists:master_categories,id'],
                'name' => [
                    'required', 'string', 'max:100',
                    Rule::unique('master_sub_categories', 'name')->where('category_id', $request->integer('category_id')),
                ],
            ]),
            'models' => $request->validate([
                'category_id' => ['required', 'integer', 'exists:master_categories,id'],
                'sub_category_id' => ['required', 'integer', 'exists:master_sub_categories,id'],
                'name' => [
                    'required', 'string', 'max:120',
                    Rule::unique('master_models', 'name')->where('sub_category_id', $request->integer('sub_category_id')),
                ],
            ]),
            'sizes' => $request->validate([
                'name' => ['required', 'string', 'max:40', 'unique:master_sizes,name'],
            ]),
            'colors' => $request->validate([
                'name' => ['required', 'string', 'max:80', 'unique:master_colors,name'],
                'hex_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ]),
            'conversions' => $request->validate([
                'from_unit_id' => ['required', 'integer', 'exists:master_units,id', 'different:to_unit_id'],
                'to_unit_id' => [
                    'required', 'integer', 'exists:master_units,id',
                    Rule::unique('master_unit_conversions', 'to_unit_id')->where('from_unit_id', $request->integer('from_unit_id')),
                ],
                'factor' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
            ]),
        };

        if ($type === 'sub-categories' && ! DB::table('master_categories')->where('id', $values['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Kategori yang dipilih tidak tersedia.']);
        }
        if ($type === 'models') {
            $validPair = DB::table('master_sub_categories')
                ->where('id', $values['sub_category_id'])
                ->where('category_id', $values['category_id'])
                ->exists();
            if (! $validPair) {
                throw ValidationException::withMessages(['sub_category_id' => 'Sub-kategori harus berasal dari kategori yang dipilih.']);
            }
        }

        $code = app(MasterDataCodeGenerator::class)->generate($config['prefix'], $config['table']);
        DB::table($config['table'])->insert($values + [
            'code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'master_'.$type.'_created',
            'subject_type' => $config['table'],
            'subject_id' => $code,
            'description' => $config['singular'].' berhasil ditambahkan.',
            'metadata' => $values,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('master-data.index', $type)->with('success', $config['singular'].' berhasil ditambahkan.');
    }

    private function masterConfig(string $type): array
    {
        abort_unless(isset(self::MASTERS[$type]), 404);

        return self::MASTERS[$type];
    }

    private function assertProductHierarchy(array $values): void
    {
        $subCategoryMatches = DB::table('master_sub_categories')
            ->where('id', $values['sub_category_id'])
            ->where('category_id', $values['category_id'])
            ->exists();
        $modelMatches = DB::table('master_models')
            ->where('id', $values['model_id'])
            ->where('category_id', $values['category_id'])
            ->where('sub_category_id', $values['sub_category_id'])
            ->exists();

        if (! $subCategoryMatches || ! $modelMatches) {
            throw ValidationException::withMessages([
                'sub_category_id' => 'Kategori, sub-kategori, dan model harus saling sesuai.',
            ]);
        }
    }
}
