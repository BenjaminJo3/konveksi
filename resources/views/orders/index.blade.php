@extends('layouts.app')

@section('title', 'Purchase Order')
@section('heading', 'Purchase Order')
@section('breadcrumb', 'Purchase Order')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-sm text-slate-500">Kelola pesanan dan pantau progres produksi.</p><h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Daftar Purchase Order</h2></div>
        @if (in_array(auth()->user()->role, ['super_admin', 'admin', 'mydent'], true))
            <a href="{{ route('orders.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800"><span class="text-lg leading-none">+</span> Buat PO baru</a>
        @endif
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex flex-wrap gap-1 rounded-xl bg-slate-100/80 p-1">
                @foreach (['' => 'Semua pesanan', 'active' => 'Aktif', 'closed' => 'Closed'] as $value => $label)
                    <a href="{{ route('orders.index', array_filter(['status' => $value, 'q' => $search])) }}" class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ ($statusFilter === $value || ($value === 'active' && in_array($statusFilter, ['draft', 'processing', 'waiting', 'completed'], true))) ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="flex gap-2">
                <input name="q" value="{{ $search }}" placeholder="Cari nomor PO atau produk" class="!w-full !py-2 text-xs sm:!w-64">
                @if ($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                <button class="rounded-xl border border-slate-200 px-3.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Cari</button>
            </div>
        </form>
        @if ($orders->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-500">▤</span>
                <h3 class="mt-4 text-sm font-semibold text-slate-800">Belum ada PO yang cocok</h3>
                <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci atau buat Purchase Order baru.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-bold uppercase tracking-wider text-slate-400"><th class="px-6 py-3.5">Nomor PO / Produk</th><th class="px-4 py-3.5">Varian</th><th class="px-4 py-3.5">Target</th><th class="px-4 py-3.5">Cutting</th><th class="px-4 py-3.5">Diterima</th><th class="px-4 py-3.5">Tenggat</th><th class="px-6 py-3.5">Status</th>@if (auth()->user()->canPermission('orders.create'))<th class="px-5 py-3.5">Aksi</th>@endif</tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-6 py-4"><a href="{{ route('orders.show', $order) }}" class="text-xs font-bold text-slate-800 hover:text-emerald-700">{{ $order->po_number }}</a><p class="mt-1 max-w-[240px] truncate text-[11px] text-slate-400">{{ $order->product_name }}{{ $order->color ? ' · '.$order->color : '' }}</p></td>
                                <td class="px-4 py-4 text-xs text-slate-600">{{ $order->model ?: '—' }}{{ $order->size ? ' · '.$order->size : '' }}{{ $order->color ? ' · '.$order->color : '' }}</td>
                                <td class="px-4 py-4 text-xs font-semibold text-slate-700">{{ number_format($order->order_quantity) }} <span class="font-normal text-slate-400">pcs</span></td>
                                <td class="px-4 py-4 text-xs text-slate-600">{{ number_format($order->quantityFor('cutting_report')) }}</td>
                                <td class="px-4 py-4 text-xs text-slate-600">{{ number_format($order->quantityFor('goods_receipt')) }}</td>
                                <td class="px-4 py-4 text-xs text-slate-500">{{ $order->delivery_due_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-6 py-4"><x-order-status :order="$order" /></td>
                                @if (auth()->user()->canPermission('orders.create'))
                                    <td class="px-5 py-4">
                                        @if ($order->can_manage_po)
                                            <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Hapus seluruh PO {{ $order->po_number }} beserta semua variannya? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Hapus</button>
                                            </form>
                                        @else
                                            <div class="flex flex-col items-start gap-1">
                                                <button type="button" disabled title="Hanya PO Draft tanpa aktivitas yang dapat dihapus." class="cursor-not-allowed text-xs font-semibold text-slate-400">Hapus</button>
                                                <span class="text-[10px] text-slate-400">PO sudah diproses</span>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $orders->links() }}</div>@endif
        @endif
    </section>
@endsection
