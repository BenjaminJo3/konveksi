@extends('layouts.app')

@section('title', 'Rasio Barang')
@section('heading', 'Rasio Barang')
@section('breadcrumb', 'Master Barang / Rasio')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Pengaturan katalog</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Rasio kain per pcs</h2>
        <p class="mt-1 text-sm text-slate-500">Atur satu rasio untuk setiap kombinasi sub-kategori dan ukuran. Rasio yang sama berlaku untuk semua model dan warna, lalu terisi otomatis pada PO.</p>
    </div>

    <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <h3 class="text-sm font-bold text-slate-900">Tambah rasio</h3>
        <form method="POST" action="{{ route('master-data.ratios.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div>
                <label for="sub_category_id">Sub-kategori</label>
                <select id="sub_category_id" name="sub_category_id" data-searchable-select data-placeholder="Cari dan pilih sub-kategori" required>
                    <option value="">Pilih sub-kategori</option>
                    @foreach ($subCategories as $subCategory)
                        <option value="{{ $subCategory->id }}" @selected(old('sub_category_id') == $subCategory->id)>{{ $subCategory->category_name }} / {{ $subCategory->name }}</option>
                    @endforeach
                </select>
                @error('sub_category_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="size_id">Ukuran</label>
                <select id="size_id" name="size_id" required>
                    <option value="">Pilih ukuran</option>
                    @foreach ($sizes as $size)
                        <option value="{{ $size->id }}" @selected(old('size_id') == $size->id)>{{ $size->name }}</option>
                    @endforeach
                </select>
                @error('size_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="ratio">Rasio yard/pcs</label>
                <input id="ratio" name="ratio" type="number" min="0.0001" max="1000000" step="0.0001" value="{{ old('ratio') }}" required placeholder="0.2500">
                @error('ratio')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Tambah rasio</button>
            </div>
        </form>
    </section>

    <form method="GET" action="{{ route('master-data.ratios') }}" class="mb-4 flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:flex-row">
        <input type="search" name="q" value="{{ $search }}" aria-label="Cari rasio barang" placeholder="Cari kategori, sub-kategori, atau ukuran..." class="!min-w-0 flex-1">
        <div class="flex gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cari</button>
            @if ($search !== '')
                <a href="{{ route('master-data.ratios') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @if ($ratios->isEmpty())
            <div class="px-6 py-16 text-center">
                <h3 class="text-sm font-bold text-slate-900">Belum ada rasio</h3>
                <p class="mt-1 text-xs text-slate-500">Pilih sub-kategori dan ukuran di atas untuk menambahkan rasio.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Kategori</th>
                            <th class="px-5 py-3">Sub-kategori</th>
                            <th class="px-5 py-3">Ukuran</th>
                            <th class="px-5 py-3">Rasio yard/pcs</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($ratios as $ratioRow)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 text-slate-600">{{ $ratioRow->category_name }}</td>
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $ratioRow->sub_category_name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $ratioRow->size_name }}</td>
                                <td colspan="2" class="px-5 py-3">
                                    <form method="POST" action="{{ route('master-data.ratios.update', $ratioRow->id) }}" class="flex items-center gap-2">
                                        @csrf
                                        <input name="ratio" type="number" min="0.0001" max="1000000" step="0.0001" value="{{ $ratioRow->ratio }}" required aria-label="Rasio yard per pcs untuk {{ $ratioRow->sub_category_name }} ukuran {{ $ratioRow->size_name }}" class="!w-36 !min-w-0">
                                        <button class="shrink-0 rounded-xl bg-emerald-700 px-3 py-2.5 text-xs font-semibold text-white hover:bg-emerald-800">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $ratios->links() }}</div>
        @endif
    </section>
@endsection
