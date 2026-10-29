@extends('layouts.app')

@section('title', 'Buat PO Baru')
@section('heading', 'Buat Purchase Order')
@section('breadcrumb', 'Purchase Order / Baru')

@section('content')
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-emerald-700">← Kembali ke daftar PO</a>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Buat Purchase Order baru</h2>
        <p class="mt-1 text-sm text-slate-500">Satu nomor PO dapat berisi beberapa item atau varian ukuran dan warna.</p>
    </div>

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-5">
        @csrf
        <datalist id="po-categories">
            @foreach ($masters['categories'] as $category)<option value="{{ $category->name }}" data-category-id="{{ $category->id }}">@endforeach
        </datalist>
        <datalist id="po-sub-categories">
            @foreach ($masters['subCategories'] as $subCategory)<option value="{{ $subCategory->name }}" data-sub-category-id="{{ $subCategory->id }}" data-category-id="{{ $subCategory->category_id }}">@endforeach
        </datalist>
        <datalist id="po-models">
            @foreach ($masters['models'] as $model)<option value="{{ $model->name }}" data-model-id="{{ $model->id }}" data-category-id="{{ $model->category_id }}" data-sub-category-id="{{ $model->sub_category_id }}">@endforeach
        </datalist>
        <datalist id="po-sizes">
            @foreach ($masters['sizes'] as $name)<option value="{{ $name }}">@endforeach
        </datalist>
        <datalist id="po-colors">
            @foreach ($masters['colors'] as $name)<option value="{{ $name }}">@endforeach
        </datalist>
        <datalist id="po-fabrics">
            @foreach ($masters['fabrics'] as $name)<option value="{{ $name }}">@endforeach
        </datalist>
        <datalist id="po-products">
            @foreach ($masters['products'] as $product)
                <option value="{{ $product->name }}" label="{{ $product->code }} · {{ $product->category_name }} / {{ $product->sub_category_name }} / {{ $product->model_name }} / {{ $product->size_name }} / {{ $product->color_name }}" data-category="{{ $product->category_name }}" data-category-id="{{ $product->category_id }}" data-sub-category="{{ $product->sub_category_name }}" data-sub-category-id="{{ $product->sub_category_id }}" data-model="{{ $product->model_name }}" data-model-id="{{ $product->model_id }}" data-size="{{ $product->size_name }}" data-color="{{ $product->color_name }}" data-fabric="{{ $product->fabric_name }}"></option>
            @endforeach
        </datalist>
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">Informasi Purchase Order</h3>
                <p class="mt-1 text-xs text-slate-400">Nomor PO akan dipakai bersama oleh semua varian dalam pesanan.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="po_number_mode">Penomoran PO</label>
                    <select id="po_number_mode" name="po_number_mode">
                        <option value="automatic" @selected(old('po_number_mode', 'automatic') === 'automatic')>Otomatis</option>
                        <option value="manual" @selected(old('po_number_mode') === 'manual')>Masukkan nomor manual</option>
                    </select>
                </div>
                <div>
                    <label for="po_number">Nomor PO</label>
                    <input id="po_number" name="po_number" value="{{ old('po_number') }}" placeholder="MYD-{{ now()->format('Y') }}-001">
                    <p id="po-number-hint" class="mt-1 text-[11px] text-slate-400">Nomor dibuat otomatis saat PO disimpan.</p>
                    @error('po_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="po_date">Tanggal PO <span class="text-rose-500">*</span></label>
                    <input id="po_date" name="po_date" type="date" value="{{ old('po_date', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label for="delivery_due_at">Tanggal target selesai</label>
                    <input id="delivery_due_at" name="delivery_due_at" type="date" value="{{ old('delivery_due_at') }}">
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:px-7">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Rincian item PO</h3>
                    <p class="mt-1 text-xs text-slate-400"><span class="hidden md:inline">Grid ringkas untuk input cepat. </span><span class="md:hidden">Form menyesuaikan layar ponsel. </span>Ketik untuk mencari data master; pilih produk katalog untuk mengisi atribut otomatis. Rasio kain per pcs menjadi dasar perhitungan estimasi target.</p>
                </div>
                <button type="button" id="add-po-item" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">+ Tambah varian</button>
            </div>

            <div id="po-items-scroll" class="max-h-[56vh] overflow-x-auto overflow-y-scroll">
                <table class="w-[1080px] table-fixed border-separate border-spacing-0 text-left">
                    <colgroup>
                        @foreach (['w-[38px]', 'w-[84px]', 'w-[88px]', 'w-[107px]', 'w-[95px]', 'w-[60px]', 'w-[75px]', 'w-[106px]', 'w-[95px]', 'w-[60px]', 'w-[95px]', 'w-[95px]', 'w-[80px]'] as $columnWidth)
                            <col class="{{ $columnWidth }}">
                        @endforeach
                    </colgroup>
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-slate-50 text-[9px] font-bold uppercase tracking-wide text-slate-500">
                            @foreach (['#', 'Kategori', 'Sub-kategori', 'Produk / katalog', 'Model / motif', 'Ukuran', 'Warna', 'Jenis kain', 'Kain yard/kg', 'Rib', 'Rasio yard/pcs', 'Estimasi hasil', ''] as $column)
                                <th class="border-b border-slate-200 px-1.5 py-2.5">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="po-items" class="divide-y divide-slate-100">
                        @foreach (old('items', [[]]) as $index => $item)
                            <tr class="bg-white" data-item>
                                @include('orders.partials.item-fields', ['index' => $index, 'item' => $item])
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">Grid tetap; daftar item dapat digulir tanpa menggeser susunan kolom.</p>
        </section>

        <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
            <a href="{{ route('orders.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
            <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan Purchase Order</button>
        </div>
    </form>

    <template id="po-item-template">
        <tr class="bg-white" data-item>
            @include('orders.partials.item-fields', ['index' => '__INDEX__', 'item' => []])
        </tr>
    </template>
    <script>
        (() => {
            const items = document.querySelector('#po-items');
            const sources = {
                categories: [...document.querySelectorAll('#po-categories option')],
                subCategories: [...document.querySelectorAll('#po-sub-categories option')],
                models: [...document.querySelectorAll('#po-models option')],
                products: [...document.querySelectorAll('#po-products option')],
                sizes: [...document.querySelectorAll('#po-sizes option')],
                colors: [...document.querySelectorAll('#po-colors option')],
            };
            const normalize = (value) => value.trim().toLocaleLowerCase();
            let rowNumber = 0;

            const listFor = (item, key, input, options) => {
                const listId = `po-${item.dataset.masterFilterId}-${key}`;
                let list = document.getElementById(listId);
                if (!list) {
                    list = document.createElement('datalist');
                    list.id = listId;
                    document.body.append(list);
                }

                list.replaceChildren(...options.map((option) => option.cloneNode(true)));
                input.setAttribute('list', listId);
            };

            const updateSuggestions = (item) => {
                if (!item.dataset.masterFilterId) {
                    item.dataset.masterFilterId = String(++rowNumber);
                }

                const field = (name) => item.querySelector(`[name$="[${name}]"]`);
                const categoryInput = item.querySelector('[data-po-category]');
                const subCategoryInput = item.querySelector('[data-po-sub-category]');
                const productInput = item.querySelector('[data-po-product]');
                const modelInput = field('model');
                const sizeInput = field('size');
                const colorInput = field('color');
                const category = sources.categories.find((option) =>
                    normalize(option.value) === normalize(categoryInput.value));
                const subCategories = category
                    ? sources.subCategories.filter((option) => option.dataset.categoryId === category.dataset.categoryId)
                    : [];
                const subCategory = subCategories.find((option) =>
                    normalize(option.value) === normalize(subCategoryInput.value));
                const models = category
                    ? sources.models.filter((option) => option.dataset.categoryId === category.dataset.categoryId
                        && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId))
                    : [];
                const model = models.find((option) => normalize(option.value) === normalize(modelInput.value));
                let products = category
                    ? sources.products.filter((option) => option.dataset.categoryId === category.dataset.categoryId
                        && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId)
                        && (!model || option.dataset.modelId === model.dataset.modelId))
                    : [];

                const selectedSize = sizeInput.value.trim();
                const selectedColor = colorInput.value.trim();
                if (selectedSize) {
                    products = products.filter((option) => normalize(option.dataset.size) === normalize(selectedSize));
                }
                if (selectedColor) {
                    products = products.filter((option) => normalize(option.dataset.color) === normalize(selectedColor));
                }

                const distinct = (options, key) => [...new Map(options.map((option) =>
                    [normalize(option.dataset[key]), option.dataset[key]])).values()]
                    .filter(Boolean)
                    .map((value) => {
                        const option = document.createElement('option');
                        option.value = value;
                        return option;
                    });

                listFor(item, 'sub-categories', subCategoryInput, subCategories);
                listFor(item, 'models', modelInput, models);
                listFor(item, 'products', productInput, products);
                listFor(item, 'sizes', sizeInput, distinct(products, 'size'));
                listFor(item, 'colors', colorInput, distinct(products, 'color'));
            };

            const clearDependentFields = (item, input) => {
                const downstreamFields = input.matches('[data-po-category]')
                    ? ['sub_category', 'product_name', 'model', 'size', 'color', 'fabric_name']
                    : input.matches('[data-po-sub-category]')
                        ? ['product_name', 'model', 'size', 'color', 'fabric_name']
                        : input.matches('[name$="[model]"]')
                            ? ['product_name', 'size', 'color', 'fabric_name']
                            : input.matches('[name$="[size]"]')
                                ? ['product_name', 'color', 'fabric_name']
                                : input.matches('[name$="[color]"]')
                                    ? ['product_name', 'fabric_name']
                                    : [];

                downstreamFields.forEach((name) => {
                    const input = item.querySelector(`[name$="[${name}]"]`);
                    if (input instanceof HTMLInputElement) {
                        input.value = '';
                    }
                });
            };

            items?.querySelectorAll('[data-item]').forEach(updateSuggestions);
            items?.addEventListener('input', (event) => {
                if (!(event.target instanceof HTMLInputElement)) {
                    return;
                }

                const item = event.target.closest('[data-item]');
                if (!item) {
                    return;
                }

                if (event.target.matches('[data-po-category], [data-po-sub-category], [data-po-product], [name$="[model]"], [name$="[size]"], [name$="[color]"]')) {
                    clearDependentFields(item, event.target);
                    updateSuggestions(item);
                }
            });
            items?.addEventListener('change', (event) => {
                const input = event.target;
                if (!(input instanceof HTMLInputElement)) {
                    return;
                }

                const item = input.closest('[data-item]');
                if (!item) {
                    return;
                }

                if (input.matches('[data-po-category]')) {
                    clearDependentFields(item, input);
                    updateSuggestions(item);
                    return;
                }

                if (!input.matches('[data-po-product]')) {
                    if (input.matches('[data-po-sub-category], [name$="[model]"], [name$="[size]"], [name$="[color]"]')) {
                        clearDependentFields(item, input);
                    }
                    updateSuggestions(item);
                    return;
                }

                const field = (name) => item?.querySelector(`[name$="[${name}]"]`);
                const category = sources.categories.find((option) =>
                    normalize(option.value) === normalize(field('category')?.value ?? ''));
                const subCategory = sources.subCategories.find((option) =>
                    normalize(option.value) === normalize(field('sub_category')?.value ?? '')
                    && (!category || option.dataset.categoryId === category.dataset.categoryId));
                const model = sources.models.find((option) =>
                    normalize(option.value) === normalize(field('model')?.value ?? '')
                    && (!category || option.dataset.categoryId === category.dataset.categoryId)
                    && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId));
                const product = sources.products.find((option) => option.value === input.value
                    && (!category || option.dataset.categoryId === category.dataset.categoryId)
                    && (!subCategory || option.dataset.subCategoryId === subCategory.dataset.subCategoryId)
                    && (!model || option.dataset.modelId === model.dataset.modelId));
                if (!product) {
                    return;
                }

                const fields = {
                    category: product.dataset.category,
                    sub_category: product.dataset.subCategory,
                    model: product.dataset.model,
                    size: product.dataset.size,
                    color: product.dataset.color,
                    fabric_name: product.dataset.fabric,
                };
                Object.entries(fields).forEach(([name, value]) => {
                    const target = item.querySelector(`[name$="[${name}]"]`);
                    if (target instanceof HTMLInputElement && value) {
                        target.value = value;
                    }
                });
                updateSuggestions(item);
            });
        })();
    </script>
@endsection
