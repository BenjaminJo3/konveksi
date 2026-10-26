@extends('layouts.app')

@section('title', 'Kartu Stok')
@section('heading', 'Kartu Stok')
@section('breadcrumb', 'Gudang / Kartu Stok')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Persediaan barang</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Kartu Stok</h2>
        <p class="mt-1 text-sm text-slate-500">Saldo dihitung dari semua mutasi masuk dan keluar. Catat saldo awal sebagai stok masuk.</p>
    </div>

    <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <form method="GET" action="{{ route('stock-cards.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
                <input name="q" value="{{ $search }}" placeholder="Cari kode atau nama barang" aria-label="Cari barang" class="min-w-0 flex-1">
                <button class="rounded-xl bg-slate-800 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-900">Cari barang</button>
            </form>
            <div class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <a href="{{ route('stock-cards.index', ['q' => $search, 'product_id' => $product->id]) }}" class="flex min-w-0 items-center justify-between gap-3 px-4 py-4 transition hover:bg-slate-50 sm:px-5 {{ $selectedProduct?->id === $product->id ? 'bg-emerald-50/70' : '' }}">
                        <span class="min-w-0">
                            <span class="block truncate text-[10px] font-semibold text-slate-400">{{ $product->code }}</span>
                            <span class="mt-1 block truncate text-xs font-bold text-slate-800">{{ $product->name }}</span>
                        </span>
                        <span class="shrink-0 text-right"><span class="block text-[9px] font-semibold uppercase text-slate-400">Saldo</span><span class="mt-1 block text-xs font-bold text-slate-800">{{ number_format((float) $product->stock_balance, 3) }} {{ $product->unit_symbol }}</span></span>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-xs text-slate-500">Master barang tidak ditemukan. Tambahkan barang dan unit di menu Master Barang dahulu.</div>
                @endforelse
            </div>
            @if ($products->hasPages())<div class="border-t border-slate-100 px-4 py-3">{{ $products->links() }}</div>@endif
        </section>

        <section class="min-w-0 space-y-4">
            @if ($selectedProduct)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0"><p class="font-mono text-[10px] font-semibold text-slate-400">{{ $selectedProduct->code }}</p><h3 class="mt-1 break-words text-base font-bold text-slate-900">{{ $selectedProduct->name }}</h3></div>
                        <div class="rounded-xl bg-emerald-50 px-4 py-2 text-right"><p class="text-[9px] font-semibold uppercase text-emerald-700">Saldo saat ini</p><p class="mt-1 text-lg font-bold text-emerald-800">{{ number_format($balance, 3) }} <span class="text-xs">{{ $selectedProduct->unit_symbol }}</span></p></div>
                    </div>
                    <form method="POST" action="{{ route('stock-cards.store') }}" class="mt-5 grid min-w-0 gap-3 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="master_product_id" value="{{ $selectedProduct->id }}">
                        <div><label for="movement-type">Jenis mutasi</label><select id="movement-type" name="movement_type" required><option value="in">Stok masuk / saldo awal</option><option value="out">Stok keluar</option></select></div>
                        <div><label for="quantity">Jumlah ({{ $selectedProduct->unit_symbol }})</label><input id="quantity" name="quantity" type="number" min="0.001" max="999999999" step="0.001" required inputmode="decimal" value="{{ old('quantity') }}"></div>
                        <div class="sm:col-span-2"><label for="reference">Referensi (DO, nota, atau dokumen)</label><input id="reference" name="reference" maxlength="120" value="{{ old('reference') }}" placeholder="Contoh: DO-GDG-2026-001"></div>
                        <div class="sm:col-span-2"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="2" maxlength="1000" placeholder="Keterangan mutasi (opsional)">{{ old('notes') }}</textarea></div>
                        <button class="min-h-11 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-emerald-800 sm:col-span-2">Simpan mutasi stok</button>
                    </form>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5"><h3 class="text-sm font-bold text-slate-900">Riwayat kartu stok</h3><p class="mt-1 text-[10px] text-slate-500">Mutasi terbaru ditampilkan lebih dahulu.</p></div>
                    @forelse ($movements as $movement)
                        <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 border-b border-slate-100 px-4 py-3 last:border-0 sm:px-5">
                            <span class="min-w-0 truncate text-[10px] font-semibold text-slate-800">{{ $movement->reference ?: 'Tanpa referensi' }}</span>
                            <span class="text-right text-[10px] font-bold {{ $movement->movement_type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $movement->movement_type === 'in' ? '+' : '−' }}{{ number_format((float) $movement->quantity, 3) }} {{ $selectedProduct->unit_symbol }}</span>
                            <span class="min-w-0 truncate text-[9px] text-slate-500">{{ $movement->user?->name ?? 'Akun dihapus' }}{{ $movement->notes ? ' · '.$movement->notes : '' }}</span>
                            <span class="text-right text-[9px] font-semibold text-slate-600">Saldo {{ number_format($movement->balance_after, 3) }} {{ $selectedProduct->unit_symbol }}</span>
                            <time class="col-span-2 text-[9px] text-slate-400">{{ $movement->created_at->format('d/m/Y H:i') }}</time>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-xs text-slate-500">Belum ada mutasi. Catat saldo awal sebagai stok masuk.</p>
                    @endforelse
                    @if ($movements->hasPages())<div class="border-t border-slate-100 px-4 py-3">{{ $movements->links() }}</div>@endif
                </article>
            @else
                <div class="flex min-h-56 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center"><div><p class="text-sm font-semibold text-slate-800">Pilih barang untuk membuka kartu stok</p><p class="mt-1 text-xs leading-5 text-slate-500">Saldo awal dan mutasi persediaan akan tercatat pada riwayat barang.</p></div></div>
            @endif
        </section>
    </div>
@endsection
