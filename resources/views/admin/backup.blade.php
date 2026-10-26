@extends('layouts.app')

@section('title', 'Backup Database')
@section('heading', 'Backup Database')
@section('breadcrumb', 'Administrasi / Backup')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Keamanan data</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Backup Database</h2>
        <p class="mt-1 text-sm text-slate-500">Buat backup database dan simpan file secara privat.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-lg text-blue-700">⬇</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Database aktif: {{ $driver }}</h3>
                    <p class="mt-1 break-all text-xs text-slate-500">{{ $databaseName }}</p>
                    <p class="mt-2 text-[11px] leading-5 text-slate-400">File backup disimpan di penyimpanan privat aplikasi dan tidak dapat diakses melalui URL publik.</p>
                </div>
            </div>
            @if (in_array($driver, ['sqlite', 'mysql'], true))
                <form method="POST" action="{{ route('admin.backup.create') }}">
                    @csrf
                    <button class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 sm:w-auto">Buat & Unduh Backup</button>
                </form>
            @else
                <div class="max-w-sm rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800" role="status">Backup otomatis tersedia untuk SQLite dan MySQL. Driver database saat ini belum didukung.</div>
            @endif
        </div>
    </section>

    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Backup tersimpan</h3>
            <p class="mt-1 text-xs text-slate-500">Backup SQLite (.sqlite) dan MySQL (.sql) disimpan agar dapat diunduh ulang.</p>
        </div>
        @if ($backups->isEmpty())
            <p class="px-5 py-10 text-center text-xs text-slate-400">Belum ada file backup.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($backups as $backup)
                    <div class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div class="min-w-0">
                            <p class="break-all text-xs font-semibold text-slate-800">{{ $backup['name'] }}</p>
                            <p class="mt-1 text-[10px] text-slate-400">{{ \Illuminate\Support\Carbon::createFromTimestamp($backup['modified'])->format('d M Y H:i') }} · {{ number_format($backup['size'] / 1024, 1) }} KB</p>
                        </div>
                        <a href="{{ route('admin.backup.download', $backup['name']) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-semibold text-slate-600 hover:bg-slate-50">Unduh</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
