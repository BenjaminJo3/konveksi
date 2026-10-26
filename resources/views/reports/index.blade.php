@extends('layouts.app')

@section('title', 'Laporan Operasional')
@section('heading', 'Laporan Operasional')
@section('breadcrumb', 'Laporan')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Rekap produksi</p><h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Laporan Operasional</h2><p class="mt-1 text-sm text-slate-500">Rekap PO, cutting, hasil jahit, QC, dan penerimaan berdasarkan periode dan cabang.</p></div>
        <div class="flex gap-2">
            <a href="{{ route('reports.pdf', request()->query()) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Export PDF</a>
            <a href="{{ route('reports.excel', request()->query()) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-800">Export Excel (CSV)</a>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1.4fr_auto] xl:items-end">
        <div><label for="from">Tanggal PO dari</label><input id="from" type="date" name="from" value="{{ $filters['from'] }}"></div>
        <div><label for="to">Tanggal PO sampai</label><input id="to" type="date" name="to" value="{{ $filters['to'] }}"></div>
        <div><label for="branch-id">Cabang</label><select id="branch-id" name="branch_id"><option value="">Semua cabang</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected($filters['branch_id'] == $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <button class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">Terapkan filter</button>
    </form>
    <p class="-mt-3 mb-5 text-[10px] text-slate-500">File Excel diunduh dalam format CSV UTF-8 yang kompatibel dengan Microsoft Excel.</p>

    <section class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        @foreach ([['Jumlah PO', $summary['po_count']], ['Target PO', $summary['target']], ['Hasil cutting', $summary['cutting']], ['Hasil jahit', $summary['sewing']], ['Diterima Mydent', $summary['received']]] as [$label, $value])
            <article class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($value) }}{{ $label === 'Jumlah PO' ? '' : ' pcs' }}</p></article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @if ($orders->isEmpty())
            <p class="px-5 py-12 text-center text-xs text-slate-500">Tidak ada data PO pada rentang tanggal dan cabang ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Tanggal / PO</th><th class="px-4 py-3">Cabang</th><th class="px-4 py-3">Produk</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Cutting</th><th class="px-4 py-3">Jahit</th><th class="px-4 py-3">Lolos QC</th><th class="px-4 py-3">Diterima</th><th class="px-5 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="font-bold text-slate-800 hover:text-emerald-700">{{ $order->po_number }}</a><p class="mt-1 text-[10px] text-slate-400">{{ $order->po_date?->format('d M Y') }}</p></td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->branch?->name ?? 'Belum ditentukan' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->product_name }}{{ $order->color ? ' · '.$order->color : '' }}</td>
                                <td class="px-4 py-3">{{ number_format($order->order_quantity) }}</td>
                                <td class="px-4 py-3">{{ number_format($order->quantityFor('cutting_report')) }}</td>
                                <td class="px-4 py-3">{{ number_format($order->finishedQuantity()) }}</td>
                                <td class="px-4 py-3">{{ number_format($order->quantityFor('quality_check')) }}</td>
                                <td class="px-4 py-3">{{ number_format($order->quantityFor('goods_receipt')) }}</td>
                                <td class="px-5 py-3">{{ $order->statusLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
