@extends('layouts.app')

@section('title', 'Reject')
@section('heading', 'Reject')
@section('breadcrumb', 'Produksi / Reject')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Kontrol kualitas</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Catatan Reject</h2>
        <p class="mt-1 text-sm text-slate-500">Rekap jumlah barang reject yang tercatat saat penerimaan vendor, retur Cibentang, dan pemeriksaan QC.</p>
    </div>

    <section class="mb-5 grid gap-3 sm:grid-cols-2">
        <article class="rounded-2xl border border-rose-100 bg-rose-50 p-4">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-rose-600">Total reject tercatat</p>
            <p class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($totalQuantity) }} <span class="text-xs font-medium">pcs</span></p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Catatan transaksi</p>
            <p class="mt-2 text-2xl font-bold text-slate-800">{{ number_format($rejects->total()) }} <span class="text-xs font-medium">kejadian</span></p>
        </article>
    </section>

    <form method="GET" action="{{ route('rejects.index') }}" class="mb-4 flex gap-2 rounded-2xl border border-slate-200 bg-white p-3">
        <input name="q" value="{{ $search }}" aria-label="Cari catatan reject" placeholder="Cari PO, produk, referensi, atau catatan...">
        <button class="shrink-0 rounded-xl border border-slate-200 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cari</button>
    </form>

    @if ($rejects->isEmpty())
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-lg text-emerald-700">✓</span>
            <h3 class="mt-4 text-sm font-bold text-slate-900">Belum ada catatan reject</h3>
            <p class="mt-1 text-xs text-slate-500">Catatan reject vendor, retur, atau QC akan muncul setelah transaksi dicatat.</p>
        </section>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">PO / Barang</th>
                            <th class="px-4 py-3">Sumber reject</th>
                            <th class="px-4 py-3">Referensi</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3">Dicatat oleh / Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rejects as $entry)
                            @php($source = ['vendor_receive' => 'Penerimaan Vendor', 'site_return' => 'Retur Cibentang', 'quality_check' => 'Pemeriksaan QC'][$entry->action] ?? 'Produksi')
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $entry->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('orders.show', $entry->order) }}" class="font-bold text-blue-800 hover:text-blue-950">{{ $entry->order->po_number }}</a>
                                    <p class="mt-1 max-w-56 truncate text-slate-500">{{ $entry->order->product_name }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-semibold text-rose-700">{{ $source }}</span>
                                    @if (data_get($entry->metadata, 'material_type'))
                                        <p class="mt-1 text-[10px] text-slate-400">{{ data_get($entry->metadata, 'material_type') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $entry->reference ?: '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-rose-700">{{ number_format($entry->secondary_quantity) }} pcs</td>
                                <td class="max-w-64 px-4 py-3 text-slate-600">
                                    <p class="font-medium">{{ $entry->user?->name ?? 'Sistem' }}</p>
                                    <p class="mt-1 whitespace-normal text-[10px] leading-4 text-slate-400">{{ $entry->notes ?: 'Tidak ada catatan tambahan.' }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div class="mt-4">{{ $rejects->links() }}</div>
    @endif
@endsection
