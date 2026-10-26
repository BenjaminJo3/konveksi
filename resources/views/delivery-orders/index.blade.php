@extends('layouts.app')

@section('title', 'Delivery Order')
@section('heading', 'Delivery Order')
@section('breadcrumb', 'DO')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Catat pengiriman antar lokasi dan pantau status penerimaannya.</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Daftar Delivery Order</h2>
        </div>
        <a href="{{ route('delivery-orders.create') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">+ Buat DO</a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
        @if ($deliveryOrders->isEmpty())
            <div class="px-6 py-16 text-center">
                <h3 class="text-sm font-semibold text-slate-800">Belum ada Delivery Order</h3>
                <p class="mt-1 text-xs text-slate-400">DO yang Anda buat atau perlu dipantau akan tampil di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-bold uppercase tracking-wider text-slate-400"><th class="px-5 py-3">Nomor DO / PO</th><th class="px-4 py-3">Rute</th><th class="px-4 py-3">Barang</th><th class="px-4 py-3">Kirim</th><th class="px-4 py-3">Diterima</th><th class="px-4 py-3">Sisa</th><th class="px-5 py-3">Tanggal</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($deliveryOrders as $deliveryOrder)
                            <tr class="text-xs text-slate-600">
                                <td class="px-5 py-3.5"><p class="font-bold text-slate-800">{{ $deliveryOrder->number }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $deliveryOrder->po_number }} · {{ $deliveryOrder->items->count() }} item</p></td>
                                <td class="px-4 py-3.5"><p>{{ $deliveryOrder->sourceLabel() }}</p><p class="mt-1 text-[11px] text-slate-400">→ {{ $deliveryOrder->destinationLabel() }}</p></td>
                                <td class="px-4 py-3.5">{{ $deliveryOrder->goodsLabel() }}</td>
                                <td class="px-4 py-3.5 font-semibold">{{ number_format($deliveryOrder->totalQuantity(), 2) }} {{ $deliveryOrder->unit }}</td>
                                <td class="px-4 py-3.5">{{ number_format($deliveryOrder->receivedQuantity(), 2) }} {{ $deliveryOrder->unit }}</td>
                                <td class="px-4 py-3.5">{{ number_format($deliveryOrder->pendingQuantity(), 2) }} {{ $deliveryOrder->unit }}</td>
                                <td class="px-5 py-3.5">{{ $deliveryOrder->dispatched_at->format('d M Y') }}</td>
                            </tr>
                            <tr class="bg-slate-50/50">
                                <td colspan="7" class="px-5 pb-4 pt-1">
                                    <div class="flex flex-wrap gap-x-5 gap-y-1">
                                        @foreach ($deliveryOrder->items as $item)
                                            <span class="text-[10px] text-slate-500">{{ $item->order->product_name }} · {{ $item->order->model }} · {{ $item->order->size }}/{{ $item->order->color }}: <strong>{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</strong></span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($deliveryOrders->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $deliveryOrders->links() }}</div>@endif
        @endif
    </section>
@endsection
