@extends('layouts.app')

@section('title', $config['label'])
@section('heading', $config['label'])
@section('breadcrumb', 'Master Barang / '.$config['label'])

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Pengaturan katalog</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $config['label'] }}</h2>
            <p class="mt-1 text-sm text-slate-500">Kode master dibuat otomatis dan unik saat data disimpan.</p>
        </div>
        <a href="{{ route('master-data.create', $type) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-emerald-800">+ Input {{ $config['singular'] }}</a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        @if ($rows->isEmpty())
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-700">▦</div>
                <h3 class="mt-4 text-sm font-bold text-slate-900">Belum ada data {{ strtolower($config['label']) }}</h3>
                <p class="mt-1 text-xs text-slate-500">Tambahkan data terlebih dahulu untuk dipakai pada master barang.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[580px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Kode</th>
                            @if ($type === 'conversions')
                                <th class="px-5 py-3">Dari</th><th class="px-5 py-3">Menjadi</th><th class="px-5 py-3 text-right">Faktor</th>
                            @else
                                <th class="px-5 py-3">Nama</th>
                                @if ($type === 'units')<th class="px-5 py-3">Simbol</th>@endif
                                @if ($type === 'sub-categories')<th class="px-5 py-3">Kategori</th>@endif
                                @if ($type === 'models')<th class="px-5 py-3">Kategori / Sub-kategori</th>@endif
                                @if ($type === 'colors')<th class="px-5 py-3">Warna</th>@endif
                            @endif
                            <th class="px-5 py-3 text-right">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-emerald-800">{{ $row->code }}</td>
                                @if ($type === 'conversions')
                                    <td class="px-5 py-3 text-slate-700">{{ $row->source_name }} ({{ $row->source_symbol }})</td>
                                    <td class="px-5 py-3 text-slate-700">{{ $row->target_name }} ({{ $row->target_symbol }})</td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-800">{{ number_format((float) $row->factor, 6, ',', '.') }}</td>
                                @else
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $row->name }}</td>
                                    @if ($type === 'units')<td class="px-5 py-3 text-slate-600">{{ $row->symbol }}</td>@endif
                                    @if ($type === 'sub-categories')<td class="px-5 py-3 text-slate-600">{{ $row->category_name }}</td>@endif
                                    @if ($type === 'models')<td class="px-5 py-3 text-slate-600">{{ $row->category_name }} / {{ $row->sub_category_name }}</td>@endif
                                    @if ($type === 'colors')
                                        <td class="px-5 py-3"><span class="inline-flex items-center gap-2 text-slate-600"><span class="h-5 w-5 rounded-full border border-slate-200" style="background-color: {{ $row->hex_color ?: '#e2e8f0' }}"></span>{{ $row->hex_color ?: '—' }}</span></td>
                                    @endif
                                @endif
                                <td class="px-5 py-3 text-right text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-3">{{ $rows->links() }}</div>
        @endif
    </section>
@endsection
