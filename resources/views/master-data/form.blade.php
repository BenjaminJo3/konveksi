@extends('layouts.app')

@section('title', 'Input '.$config['singular'])
@section('heading', 'Input '.$config['singular'])
@section('breadcrumb', 'Master Barang / '.$config['label'].' / Input')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('master-data.index', $type) }}" class="text-xs font-semibold text-slate-500 hover:text-emerald-700">← Kembali ke {{ $config['label'] }}</a>
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Master Barang</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">Input {{ $config['singular'] }}</h2>
                <p class="mt-1 text-xs text-slate-500">Kode acak unik akan dibuat otomatis saat data disimpan.</p>
            </div>
            <form method="POST" action="{{ route('master-data.store', $type) }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                @if ($type === 'units')
                    <div><label for="name">Nama unit</label><input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Pieces"></div>
                    <div><label for="symbol">Simbol</label><input id="symbol" name="symbol" value="{{ old('symbol') }}" required placeholder="Contoh: pcs"></div>
                @elseif ($type === 'categories')
                    <div class="sm:col-span-2"><label for="name">Nama kategori</label><input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Pakaian"></div>
                @elseif ($type === 'sub-categories')
                    <div class="sm:col-span-2"><label for="category_id">Kategori</label><select id="category_id" name="category_id" required><option value="">Pilih kategori</option>@foreach ($masters['categories'] as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label for="name">Nama sub-kategori</label><input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Kaos"></div>
                @elseif ($type === 'models')
                    <div><label for="category_id">Kategori</label><select id="category_id" name="category_id" required><option value="">Pilih kategori</option>@foreach ($masters['categories'] as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                    <div><label for="sub_category_id">Sub-kategori</label><select id="sub_category_id" name="sub_category_id" required><option value="">Pilih sub-kategori</option>@foreach ($masters['subCategories'] as $subCategory)<option value="{{ $subCategory->id }}" data-category="{{ $subCategory->category_id }}" @selected(old('sub_category_id') == $subCategory->id)>{{ $subCategory->name }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label for="name">Nama model / artikel</label><input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Raglan Premium"></div>
                @elseif ($type === 'sizes')
                    <div class="sm:col-span-2"><label for="name">Ukuran</label><input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: M, L, XL atau 32"></div>
                @elseif ($type === 'colors')
                    <div><label for="name">Nama warna</label><input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Navy"></div>
                    <div><label for="hex_color">Kode warna (opsional)</label><input id="hex_color" name="hex_color" type="color" value="{{ old('hex_color', '#1e3a8a') }}" class="!h-11 !p-1"></div>
                @elseif ($type === 'conversions')
                    <div><label for="from_unit_id">Dari unit</label><select id="from_unit_id" name="from_unit_id" required><option value="">Pilih unit asal</option>@foreach ($masters['units'] as $unit)<option value="{{ $unit->id }}" @selected(old('from_unit_id') == $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>@endforeach</select></div>
                    <div><label for="to_unit_id">Menjadi unit</label><select id="to_unit_id" name="to_unit_id" required><option value="">Pilih unit tujuan</option>@foreach ($masters['units'] as $unit)<option value="{{ $unit->id }}" @selected(old('to_unit_id') == $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>@endforeach</select></div>
                    <div><label for="factor">Faktor konversi</label><input id="factor" name="factor" type="number" min="0.000001" step="0.000001" value="{{ old('factor') }}" required placeholder="Contoh: 1000"></div>
                    <p class="self-end pb-2 text-xs text-slate-500">1 unit asal = faktor × unit tujuan</p>
                @endif
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:col-span-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('master-data.index', $type) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
                    <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan {{ $config['singular'] }}</button>
                </div>
            </form>
        </section>
    </div>
@endsection
