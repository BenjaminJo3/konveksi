@extends('layouts.app')

@section('title', 'Penerimaan (GRN)')
@section('heading', 'Penerimaan barang')
@section('breadcrumb', 'Penerimaan / GRN')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Konfirmasi barang yang tiba berdasarkan Delivery Order untuk lokasi terpilih.</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Penerimaan Barang (GRN)</h2>
        </div>
        @if ($canReceive)
            <form method="GET" action="{{ route('goods-receipts.index') }}" class="flex w-full items-end gap-2 sm:w-auto">
                <div class="min-w-0 flex-1 sm:w-72">
                    <label for="receipt-location">Lokasi penerimaan</label>
                    <select id="receipt-location" name="location" @disabled(! $canSelectLocation)>
                        @foreach ($locations as $role => $label)
                            @if ($canSelectLocation || $selectedLocation === $role)
                                <option value="{{ $role }}" @selected($selectedLocation === $role)>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                @if ($canSelectLocation)
                    <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Pilih lokasi</button>
                @else
                    <span class="mb-2 whitespace-nowrap text-[10px] font-semibold text-emerald-700">Sesuai akun aktif</span>
                @endif
            </form>
        @endif
    </div>

    @if ($deliveryOrders->isEmpty())
        <section class="rounded-2xl border border-slate-200/80 bg-white px-6 py-16 text-center">
            <h3 class="text-sm font-semibold text-slate-800">Tidak ada DO yang menunggu penerimaan</h3>
            <p class="mt-1 text-xs text-slate-400">DO yang ditujukan ke lokasi aktif akan tampil di sini.</p>
        </section>
    @else
        <div class="space-y-4">
            @foreach ($deliveryOrders as $deliveryOrder)
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
                    <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-start">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $deliveryOrder->group?->number ?? $deliveryOrder->number }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $deliveryOrder->order->po_number }} · {{ $deliveryOrder->order->product_name }} · {{ $deliveryOrder->order->model }} · {{ $deliveryOrder->order->size }}/{{ $deliveryOrder->order->color }} · {{ $deliveryOrder->goodsLabel() }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $deliveryOrder->sourceLabel() }} → {{ $deliveryOrder->destinationLabel() }} · dikirim {{ $deliveryOrder->dispatched_at->format('d M Y') }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-800">
                            Sisa diterima: {{ number_format($deliveryOrder->pendingQuantity(), 2) }} {{ $deliveryOrder->unit }}
                        </div>
                    </div>

                    @if ($canReceive && $deliveryOrder->pendingQuantity() > 0)
                        <form method="POST" action="{{ route('goods-receipts.store') }}" class="mt-4 grid gap-4 sm:grid-cols-[1fr_2fr_auto] sm:items-end">
                            @csrf
                            <input type="hidden" name="delivery_order_id" value="{{ $deliveryOrder->id }}">
                            <input type="hidden" name="destination_role" value="{{ $selectedLocation }}">
                            <div>
                                <label for="quantity_received_{{ $deliveryOrder->id }}">Kuantitas diterima ({{ $deliveryOrder->unit }})</label>
                                <input id="quantity_received_{{ $deliveryOrder->id }}" name="quantity_received" type="number" min="0.01" max="{{ $deliveryOrder->pendingQuantity() }}" step="0.01" required placeholder="{{ number_format($deliveryOrder->pendingQuantity(), 2, '.', '') }}">
                                @error('quantity_received')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="discrepancy_note_{{ $deliveryOrder->id }}">Catatan selisih (wajib jika tidak sesuai)</label>
                                <input id="discrepancy_note_{{ $deliveryOrder->id }}" name="discrepancy_note" placeholder="Alasan jika barang kurang/selisih">
                                @error('discrepancy_note')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-800">Catat GRN</button>
                        </form>
                    @endif

                    @if ($deliveryOrder->receipts->isNotEmpty())
                        <div class="mt-4 border-t border-slate-100 pt-3">
                            <p class="mb-2 text-[10px] font-bold uppercase tracking-wide text-slate-400">Riwayat penerimaan</p>
                            <div class="space-y-1.5">
                                @foreach ($deliveryOrder->receipts as $receipt)
                                    <p class="text-[11px] text-slate-500">{{ number_format((float) $receipt->quantity_received, 2) }} {{ $deliveryOrder->unit }} · {{ $receipt->receiver?->name ?? 'Pengguna' }} · {{ $receipt->received_at->format('d M Y H:i') }}{{ $receipt->discrepancy_note ? ' · '.$receipt->discrepancy_note : '' }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
        @if ($deliveryOrders->hasPages())<div class="mt-5">{{ $deliveryOrders->links() }}</div>@endif
    @endif
@endsection
