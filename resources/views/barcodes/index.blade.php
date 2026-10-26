@extends('layouts.app')

@section('title', 'Cetak Barcode')
@section('heading', 'Cetak Barcode')
@section('breadcrumb', 'Master Barang / Cetak Barcode')

@push('head')
    <style id="barcode-print-settings">
        @page {
            margin: 0 !important;

            @top-left { content: ""; }
            @top-center { content: ""; }
            @top-right { content: ""; }
            @bottom-left { content: ""; }
            @bottom-center { content: ""; }
            @bottom-right { content: ""; }
        }
    </style>
@endpush

@section('content')
    <div class="barcode-print-view">
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Label identifikasi produk</p><h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Cetak Barcode</h2><p class="mt-1 text-sm text-slate-500">Cari kode atau nama barang, tinjau label, lalu cetak Code 128.</p></div>
        <button id="print-barcodes" type="button" disabled class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white enabled:hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">Cetak Barcode</button>
    </div>

    <div class="barcode-print-layout grid min-w-0 gap-5 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.4fr)]">
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <h3 class="text-sm font-bold text-slate-900">1. Cari dan pilih barang</h3>
                <form method="GET" action="{{ route('barcodes.index') }}" class="mt-3 flex gap-2">
                    <input name="q" value="{{ $search }}" aria-label="Cari kode atau nama barang" placeholder="Ketik kode barang atau nama..." autofocus>
                    <button class="shrink-0 rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cari</button>
                </form>
                <p class="mt-2 text-[10px] text-slate-400">Maksimal 50 hasil. Cari kode barang untuk langsung menemukan kode unik.</p>
                @if ($products->isNotEmpty())
                    <div class="mt-4 max-h-[28rem] space-y-2 overflow-y-auto">
                        @foreach ($products as $product)
                            <article class="flex items-center gap-2 rounded-xl border border-slate-100 p-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-bold text-slate-800">{{ $product->name }}</p>
                                    <p class="mt-1 truncate font-mono text-[10px] text-emerald-700">{{ $product->code }}</p>
                                    <p class="mt-1 truncate text-[10px] text-slate-400">{{ $product->category_name }} · {{ $product->model_name }} · {{ $product->size_name }}/{{ $product->color_name }}</p>
                                </div>
                                <label class="mb-0 w-16 shrink-0 text-[9px]">Jumlah<input type="number" min="1" max="500" value="1" aria-label="Jumlah label {{ $product->code }}" class="!mt-1 !px-2 !py-1.5"></label>
                                <button type="button" data-add-barcode data-code="{{ $product->code }}" data-name="{{ $product->name }}" data-variant="{{ $product->model_name }} · {{ $product->size_name }} / {{ $product->color_name }}" class="shrink-0 rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100">Tambah</button>
                            </article>
                        @endforeach
                    </div>
                @elseif ($search !== '')
                    <p class="mt-5 rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-500">Barang aktif dengan kata kunci tersebut tidak ditemukan.</p>
                @else
                    <p class="mt-5 rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-500">Masukkan kode atau nama barang untuk menampilkan hasil.</p>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <h3 class="text-sm font-bold text-slate-900">2. Pengaturan kertas</h3>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div><label for="paper-width">Lebar X (mm)</label><input id="paper-width" type="number" min="50" max="500" step="1" value="210"></div>
                    <div><label for="paper-height">Tinggi Y (mm)</label><input id="paper-height" type="number" min="50" max="500" step="1" value="297"></div>
                    <div><label for="labels-per-row">Kotak / baris</label><input id="labels-per-row" type="number" min="1" max="10" step="1" value="3"></div>
                </div>
                <p class="mt-3 text-[10px] leading-4 text-slate-400">Preview menampilkan jumlah label, total baris, kotak per baris, dan perkiraan halaman. Ukuran cetak mengikuti kertas X × Y dengan area label berjarak 5 mm dari tepi; header/footer browser disembunyikan.</p>
            </section>
        </div>

        <section id="barcode-preview-panel" class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-5">
                <div><h3 class="text-sm font-bold text-slate-900">Preview barcode</h3><p id="barcode-count" class="mt-1 text-[10px] text-slate-400">Belum ada label dipilih</p></div>
                <button id="clear-barcodes" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-semibold text-slate-500 hover:bg-slate-50">Kosongkan</button>
            </div>
            <div class="overflow-auto bg-slate-100 p-3 sm:p-5">
                <div id="barcode-paper" class="barcode-paper mx-auto min-h-80 bg-white p-3 shadow-sm" aria-live="polite"></div>
            </div>
        </section>
    </div>
    </div>
@endsection
