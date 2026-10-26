@extends('layouts.app')

@section('title', 'Papan Kanban Operasional')
@section('heading', 'Papan Kanban Operasional')
@section('breadcrumb', 'Purchase Order / Kanban')

@section('content')
    <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Tracking produksi langsung</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Alur PO sampai barang jadi</h2>
            <p class="mt-1 text-sm text-slate-500">Setiap kartu mewakili satu PO dan bergerak mengikuti transaksi yang tercatat.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"><span class="mr-1.5 inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span><span id="kanban-refresh-label">Update otomatis 60 detik</span></span>
            <button type="button" data-kanban-refresh class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Perbarui</button>
        </div>
    </div>

    <div class="mb-4 flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <span><span class="font-bold text-slate-900">{{ $totalOrders }}</span> PO ditampilkan</span>
            <span class="text-slate-300">·</span>
            <span class="text-slate-400">dikelompokkan per nomor PO</span>
            @if ($overdueOrders > 0)
                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold text-rose-700">{{ $overdueOrders }} PO terlambat</span>
            @endif
        </div>
        <form method="GET" action="{{ route('orders.kanban') }}" class="flex gap-2 sm:w-80">
            <input name="q" value="{{ $search }}" aria-label="Cari PO atau barang di papan Kanban" placeholder="Cari nomor PO atau nama barang...">
            <button class="shrink-0 rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cari</button>
        </form>
    </div>

    <div data-kanban-board class="kanban-board flex items-start gap-4 overflow-x-auto pb-4">
        @foreach ($columns as $column)
            <section class="kanban-column w-[min(86vw,19rem)] shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100/80 sm:w-72" aria-label="{{ $column['title'] }}: {{ $column['orders']->count() }} PO">
                <header class="sticky top-0 z-10 border-b border-slate-200 bg-white px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold leading-5 text-slate-800">{{ $column['title'] }}</h3>
                            <p class="mt-0.5 text-[10px] leading-4 text-slate-400">{{ $column['description'] }}</p>
                        </div>
                        <span class="{{ $column['color'] }} rounded-full px-2.5 py-1 text-[10px] font-bold text-white">{{ $column['orders']->count() }}</span>
                    </div>
                </header>
                <div class="kanban-column-cards min-h-36 space-y-3 p-3">
                    @forelse ($column['orders'] as $card)
                        @php($order = $card['order'])
                        <article class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('orders.show', $order) }}" class="text-xs font-bold text-blue-800 hover:text-blue-950">{{ $order->po_number }}</a>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[9px] font-semibold text-slate-600">{{ $order->statusLabel() }}</span>
                            </div>
                            @if ($card['isOverdue'])
                                <p class="mt-2 inline-flex rounded-lg bg-rose-50 px-2 py-1 text-[9px] font-bold text-rose-700" title="Target kirim {{ $order->delivery_due_at->format('d M Y') }}">Terlambat {{ $card['overdueDays'] }} hari</p>
                            @elseif ($order->delivery_due_at && $order->status !== 'closed')
                                <p class="mt-2 text-[9px] font-medium text-slate-500">Target kirim {{ $order->delivery_due_at->format('d M Y') }}</p>
                            @endif
                            <p class="mt-2 line-clamp-2 text-xs font-semibold leading-5 text-slate-800">{{ $card['items']->pluck('product_name')->unique()->take(2)->join(', ') }}{{ $card['items']->pluck('product_name')->unique()->count() > 2 ? ' +' : '' }}</p>
                            <p class="mt-1 text-[10px] text-slate-500">{{ $card['items']->count() }} varian <span class="mx-1 text-slate-300">·</span> Target {{ number_format($card['quantity']) }} pcs</p>
                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[9px] text-slate-400">
                                <span>Umur PO <strong class="text-slate-600">{{ $card['poAgeDays'] }} hari</strong></span>
                                <span>Di tahap ini <strong class="text-slate-600">{{ $card['stageAgeDays'] }} hari</strong></span>
                            </div>
                            <div class="mt-3">
                                <div class="mb-1 flex justify-between text-[9px] text-slate-500"><span>Cutting {{ number_format($card['cutting']) }} pcs</span><span>Terima {{ $card['progress'] }}%</span></div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $card['progress'] }}%"></div></div>
                            </div>
                            <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50/70 px-2.5 py-2">
                                <p class="text-[9px] font-bold uppercase tracking-wide text-blue-700">Aksi berikutnya · {{ $card['nextAction'][0] }}</p>
                                <p class="mt-0.5 text-[10px] leading-4 text-blue-900">{{ $card['nextAction'][1] }}</p>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5">
                                <p class="min-w-0 truncate text-[9px] text-slate-400">
                                    @if ($card['latestEntry'])
                                        {{ str($card['latestEntry']->action)->replace('_', ' ')->title() }} · {{ $card['latestEntry']->user?->name ?? 'Sistem' }}
                                    @else
                                        Belum ada aktivitas produksi
                                    @endif
                                </p>
                                <a href="{{ route('orders.show', $order) }}" class="ml-2 shrink-0 text-[10px] font-semibold text-blue-700 hover:text-blue-900">Detail →</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white/70 px-3 py-7 text-center">
                            <p class="text-[10px] text-slate-400">Belum ada PO pada tahap ini</p>
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
    <p class="mt-1 text-[10px] text-slate-400">Papan diperbarui otomatis setiap 60 detik selama halaman aktif. Perubahan tahap berasal dari transaksi PO, bukan pemindahan kartu manual.</p>
@endsection
