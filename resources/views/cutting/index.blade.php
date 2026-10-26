@extends('layouts.app')

@section('title', 'Input Hasil Cutting')
@section('heading', 'Input Hasil Cutting')
@section('breadcrumb', 'Produksi / Cutting')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Produksi Gunung Karang</p><h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Input Hasil Cutting</h2><p class="mt-1 text-sm text-slate-500">Pilih PO dengan SPK; laporan dicatat melalui form produksi yang sama dan seluruh cutting dibatasi ke Gunung Karang.</p></div>
        <form method="GET" class="flex gap-2"><input name="q" value="{{ $search }}" placeholder="Cari nomor PO / produk" class="!w-full sm:!w-64"><button class="rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-700">Cari</button></form>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @if ($orders->isEmpty())
            <div class="px-6 py-14 text-center"><p class="text-sm font-semibold text-slate-800">Tidak ada PO dengan SPK yang perlu dicutting</p><p class="mt-1 text-xs text-slate-500">SPK harus diterbitkan sebelum laporan cutting dapat dicatat.</p></div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($orders as $order)
                    <article class="flex flex-col justify-between gap-3 px-4 py-4 sm:flex-row sm:items-center sm:px-5">
                        <div class="min-w-0"><a href="{{ route('orders.show', $order) }}" class="text-xs font-bold text-slate-800 hover:text-emerald-700">{{ $order->po_number }}</a><p class="mt-1 truncate text-[11px] text-slate-500">{{ $order->product_name }}{{ $order->color ? ' · '.$order->color : '' }} · {{ number_format($order->order_quantity) }} pcs target</p><p class="mt-1 text-[10px] text-slate-400">SPK {{ number_format($order->spk_quantity) }} pcs · Cutting tercatat {{ number_format($order->quantityFor('cutting_report')) }} pcs · {{ $order->branch?->name ?? 'Cabang belum ditentukan' }}</p></div>
                        <a href="{{ route('orders.show', $order).'#workflow-actions' }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-amber-700">Input hasil potong</a>
                    </article>
                @endforeach
            </div>
            @if ($orders->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $orders->links() }}</div>@endif
        @endif
    </section>
@endsection
