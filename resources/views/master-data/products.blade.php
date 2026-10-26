@extends('layouts.app')

@section('title', 'Master Barang')
@section('heading', 'Master Barang')
@section('breadcrumb', 'Master Barang / Daftar')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Katalog produk & varian</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Daftar Master Barang</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola barang menurut kategori, model, ukuran, dan warna.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('master-data.import') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Upload Master Data</a>
            <a href="{{ route('master-data.products.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-emerald-800">+ Input barang baru</a>
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:flex-row">
        <input name="q" value="{{ $search }}" aria-label="Cari master barang" placeholder="Cari kode, nama barang, model, warna..." class="!min-w-0 flex-1">
        <input type="hidden" name="view" value="{{ $viewMode }}">
        <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cari</button>
    </form>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-xs text-slate-500">{{ $products->total() }} barang terdaftar</p>
        <div class="flex rounded-xl border border-slate-200 bg-white p-1">
            <a href="{{ route('master-data.products', ['view' => 'grid', 'q' => $search]) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $viewMode === 'grid' ? 'bg-emerald-700 text-white' : 'text-slate-500' }}">▦ Grid</a>
            <a href="{{ route('master-data.products', ['view' => 'kanban', 'q' => $search]) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $viewMode === 'kanban' ? 'bg-emerald-700 text-white' : 'text-slate-500' }}">▤ Kanban</a>
        </div>
    </div>

    @if ($products->isEmpty())
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-700">▦</div>
            <h3 class="mt-4 text-sm font-bold text-slate-900">Belum ada master barang</h3>
            <p class="mt-1 text-xs text-slate-500">Isi data atribut melalui submenu Master Barang, lalu tambahkan barang di sini.</p>
        </section>
    @elseif ($viewMode === 'kanban')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($products->getCollection()->groupBy('category_name') as $category => $categoryProducts)
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100/80">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                        <h3 class="text-sm font-bold text-slate-800">{{ $category }}</h3>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">{{ $categoryProducts->count() }}</span>
                    </div>
                    <div class="space-y-3 p-3">
                        @foreach ($categoryProducts as $product)
                            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><p class="truncate text-sm font-bold text-slate-900">{{ $product->name }}</p><p class="mt-1 font-mono text-[10px] text-emerald-700">{{ $product->code }}</p></div>
                                    <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5 text-[10px]">
                                    @foreach ([$product->sub_category_name, $product->model_name, 'Size '.$product->size_name, $product->color_name] as $tag)<span class="rounded-lg bg-slate-100 px-2 py-1 text-slate-600">{{ $tag }}</span>@endforeach
                                </div>
                                <p class="mt-3 text-[11px] text-slate-500">{{ $product->fabric_name ?: 'Jenis kain belum ditentukan' }} <span class="mx-1">·</span> Unit {{ $product->unit_symbol }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table id="master-products-grid" class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Barang</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Model</th><th class="px-4 py-3">Ukuran / Warna</th><th class="px-4 py-3">Kain / Unit</th><th class="px-4 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr class="hover:bg-slate-50/70">
                                <td data-label="Kode" class="px-4 py-3 font-mono text-xs font-semibold text-emerald-800">{{ $product->code }}</td>
                                <td data-label="Barang" class="px-4 py-3"><p class="font-semibold text-slate-800">{{ $product->name }}</p><p class="text-[11px] text-slate-400">{{ $product->sub_category_name }}</p></td>
                                <td data-label="Kategori" class="px-4 py-3 text-slate-600">{{ $product->category_name }}</td>
                                <td data-label="Model" class="px-4 py-3 text-slate-600">{{ $product->model_name }}</td>
                                <td data-label="Ukuran / Warna" class="px-4 py-3 text-slate-600">{{ $product->size_name }} / {{ $product->color_name }}</td>
                                <td data-label="Kain / Unit" class="px-4 py-3 text-slate-600">{{ $product->fabric_name ?: '—' }} / {{ $product->unit_symbol }}</td>
                                <td data-label="Status" class="px-4 py-3"><span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
