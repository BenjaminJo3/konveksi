@extends('layouts.app')

@section('title', 'Master Barang Baru')
@section('heading', 'Master Barang Baru')
@section('breadcrumb', 'Master Barang / Barang Baru')

@section('content')
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('master-data.products') }}" class="text-xs font-semibold text-slate-500 hover:text-emerald-700">← Kembali ke daftar barang</a>
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Katalog produk</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">Input master barang baru</h2>
                <p class="mt-1 text-xs text-slate-500">Nama barang otomatis mengikuti Sub-kategori, Warna, Artikel, lalu Ukuran. Kode barang dibuat otomatis setelah disimpan.</p>
            </div>
            <form method="POST" action="{{ route('master-data.products.store') }}" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <div class="sm:col-span-2 lg:col-span-3"><label for="name">Nama barang otomatis</label><input id="name" name="name" value="{{ old('name') }}" readonly placeholder="Pilih sub-kategori, warna, artikel, dan ukuran" class="bg-slate-50"></div>
                <div><label for="category_id">Kategori</label><select id="category_id" name="category_id" data-searchable-select data-large-dropdown data-dispatch-change data-placeholder="Cari kategori" required><option value="">Pilih kategori</option>@foreach ($masters['categories'] as $row)<option value="{{ $row->id }}" @selected(old('category_id') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
                <div><label for="sub_category_id">Sub-kategori</label><select id="sub_category_id" name="sub_category_id" required><option value="">Pilih sub-kategori</option>@foreach ($masters['subCategories'] as $row)<option value="{{ $row->id }}" data-category="{{ $row->category_id }}" @selected(old('sub_category_id') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
                <div><label for="model_id">Model / Artikel <span class="text-rose-600">(wajib diisi)</span></label><select id="model_id" name="model_id" required @if($errors->has('model_id')) aria-describedby="model_id_error" @endif><option value="">Pilih model</option>@foreach ($masters['models'] as $row)<option value="{{ $row->id }}" data-category="{{ $row->category_id }}" data-sub-category="{{ $row->sub_category_id }}" @selected(old('model_id') == $row->id)>{{ $row->name }}</option>@endforeach</select>@error('model_id')<p id="model_id_error" class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="size_id">Ukuran</label><select id="size_id" name="size_id" required><option value="">Pilih ukuran</option>@foreach ($masters['sizes'] as $row)<option value="{{ $row->id }}" @selected(old('size_id') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
                <div><label for="color_id">Warna</label><select id="color_id" name="color_id" data-searchable-select data-large-dropdown data-dispatch-change data-placeholder="Cari warna" required><option value="">Pilih warna</option>@foreach ($masters['colors'] as $row)<option value="{{ $row->id }}" @selected(old('color_id') == $row->id)>{{ $row->name }}</option>@endforeach</select></div>
                <div><label for="unit_id">Unit stok</label><select id="unit_id" name="unit_id" required><option value="">Pilih unit</option>@foreach ($masters['units'] as $row)<option value="{{ $row->id }}" @selected(old('unit_id') == $row->id)>{{ $row->name }} ({{ $row->symbol }})</option>@endforeach</select></div>
                <div class="sm:col-span-2 lg:col-span-3"><label for="fabric_name">Jenis kain (opsional)</label><input id="fabric_name" name="fabric_name" value="{{ old('fabric_name') }}" placeholder="Contoh: Cotton combed 24s"></div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:col-span-2 lg:col-span-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('master-data.products') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
                    <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan barang</button>
                </div>
            </form>
        </section>
    </div>
@endsection
