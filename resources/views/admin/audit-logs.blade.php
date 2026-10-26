@extends('layouts.app')

@section('title', 'Audit Log')
@section('heading', 'Audit Log')
@section('breadcrumb', 'Administrasi / Audit Log')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Jejak aktivitas</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Audit Log</h2>
        <p class="mt-1 text-sm text-slate-500">Riwayat transaksi produksi dan aktivitas administrasi, pengguna, waktu, serta referensinya.</p>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[2fr_1.4fr_1fr_1fr_auto] xl:items-end">
        <div><label for="audit-q">Cari aktivitas, PO, kode, atau pengguna</label><input id="audit-q" name="q" value="{{ $filters['search'] }}" placeholder="Kata kunci..."></div>
        <div><label for="audit-event">Jenis aktivitas</label><select id="audit-event" name="event"><option value="">Semua aktivitas</option>@foreach ($events as $option)<option value="{{ $option }}" @selected($filters['event'] === $option)>{{ str($option)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
        <div><label for="audit-from">Dari tanggal</label><input id="audit-from" name="from" type="date" value="{{ $filters['from'] }}"></div>
        <div><label for="audit-to">Sampai tanggal</label><input id="audit-to" name="to" type="date" value="{{ $filters['to'] }}"></div>
        <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Filter</button>
    </form>

    <section class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="text-sm font-bold text-slate-900">Aktivitas administrasi</h3><p class="mt-1 text-[10px] text-slate-400">Backup dan perubahan master data</p></div><span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700">{{ $systemLogs->total() }} log</span></div>
        @if ($systemLogs->isEmpty())
            <p class="px-5 py-8 text-center text-xs text-slate-400">Belum ada aktivitas administrasi yang cocok dengan filter.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-4 py-3">Pengguna</th><th class="px-4 py-3">Aktivitas</th><th class="px-4 py-3">Referensi</th><th class="px-5 py-3">Detail</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($systemLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                <td class="px-4 py-3"><p class="font-semibold text-slate-700">{{ $log->user?->name ?? 'Pengguna dihapus' }}</p><p class="text-[10px] text-slate-400">{{ $log->user?->roleLabel() ?? '—' }}</p></td>
                                <td class="px-4 py-3"><span class="rounded-lg bg-blue-50 px-2 py-1 font-semibold text-blue-700">{{ str($log->event)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-4 py-3 font-mono text-[10px] text-slate-500">{{ $log->subject_id ?? '—' }}</td>
                                <td class="max-w-sm px-5 py-3 text-slate-600">{{ $log->description }}@if ($log->ip_address)<p class="mt-1 text-[10px] text-slate-400">IP {{ $log->ip_address }}</p>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $systemLogs->links() }}</div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="text-sm font-bold text-slate-900">Transaksi workflow produksi</h3><p class="mt-1 text-[10px] text-slate-400">Riwayat immutable pada PO dan perpindahan barang</p></div><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">{{ $workflowLogs->total() }} transaksi</span></div>
        @if ($workflowLogs->isEmpty())
            <p class="px-5 py-8 text-center text-xs text-slate-400">Belum ada transaksi yang cocok dengan filter.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($workflowLogs as $log)
                    <article class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-start sm:gap-4">
                        <time class="shrink-0 text-[10px] font-medium text-slate-400 sm:w-36">{{ $log->created_at->format('d M Y H:i:s') }}</time>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><span class="rounded-lg bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700">{{ str($log->action)->replace('_', ' ')->title() }}</span><a href="{{ route('orders.show', $log->production_order_id) }}" class="text-xs font-bold text-slate-800 hover:text-emerald-700">{{ $log->order?->po_number ?? 'PO dihapus' }}</a></div>
                            <p class="mt-1 text-[11px] text-slate-600">{{ $log->order?->product_name ?? 'Purchase Order' }}{{ $log->reference ? ' · '.$log->reference : '' }}{{ $log->quantity !== null ? ' · QTY '.number_format((float) $log->quantity, 2) : '' }}</p>
                            @if ($log->notes)<p class="mt-1 text-[11px] text-slate-500">{{ $log->notes }}</p>@endif
                        </div>
                        <span class="shrink-0 text-[10px] text-slate-500">{{ $log->user?->name ?? 'Pengguna dihapus' }}</span>
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $workflowLogs->links() }}</div>
        @endif
    </section>
@endsection
