@extends('layouts.app')

@section('title', 'Surat Perintah Kerja')
@section('heading', 'Surat Perintah Kerja')
@section('breadcrumb', 'Produksi / SPK')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:p-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-700">Dokumen produksi</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900">Daftar SPK terbit</h2>
            <p class="mt-1 text-sm text-slate-500">Tinjau target kerja per PO, tanda tangan penanggung jawab, dan cetak dokumen PDF.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="inline-flex justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Buka daftar PO</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Nomor SPK / PO</th><th class="px-5 py-3">Tanggal PO</th><th class="px-5 py-3">Item / target</th><th class="px-5 py-3">Tanda tangan</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($groups as $group)
                        @php($spkOrders = $group->orders->filter(fn ($order) => $order->spk_quantity !== null))
                        <tr>
                            <td class="px-5 py-4"><p class="font-bold text-slate-900">SPK-{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($group->po_number, '-')) }}</p><p class="mt-1 text-xs text-slate-500">{{ $group->po_number }}</p></td>
                            <td class="px-5 py-4 text-slate-600">{{ $group->po_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $spkOrders->count() }} item</p><p class="mt-1 text-xs text-slate-500">{{ number_format($spkOrders->sum('spk_quantity')) }} pcs</p></td>
                            <td class="px-5 py-4">
                                @if ($group->spk_signed_at)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ditandatangani</span>
                                    <p class="mt-1 text-xs text-slate-500">{{ $group->signer?->name ?? 'Pengguna' }}</p>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Menunggu tanda tangan</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('spk.show', $group) }}" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Buka SPK</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada SPK terbit. Terbitkan SPK dari detail PO terlebih dahulu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="space-y-3 p-3 md:hidden">
            @forelse ($groups as $group)
                @php($spkOrders = $group->orders->filter(fn ($order) => $order->spk_quantity !== null))
                <article class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div><p class="text-sm font-bold text-slate-900">SPK-{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($group->po_number, '-')) }}</p><p class="mt-1 text-xs text-slate-500">{{ $group->po_number }} · {{ $group->po_date?->format('d/m/Y') ?? '—' }}</p></div>
                        @if ($group->spk_signed_at)<span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700">Ditandatangani</span>@else<span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700">Belum tanda tangan</span>@endif
                    </div>
                    <p class="mt-3 text-xs text-slate-600">{{ $spkOrders->count() }} item · {{ number_format($spkOrders->sum('spk_quantity')) }} pcs</p>
                    <a href="{{ route('spk.show', $group) }}" class="mt-3 block rounded-lg bg-blue-700 px-3 py-2.5 text-center text-xs font-semibold text-white">Buka SPK</a>
                </article>
            @empty
                <p class="px-3 py-10 text-center text-sm text-slate-500">Belum ada SPK terbit.</p>
            @endforelse
        </div>
        @if ($groups->hasPages())<div class="border-t border-slate-100 p-4">{{ $groups->links() }}</div>@endif
    </div>
@endsection
