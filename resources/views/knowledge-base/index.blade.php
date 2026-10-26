@extends('layouts.app')

@section('title', 'Knowledge Base')
@section('heading', 'Knowledge Base')
@section('breadcrumb', 'Panduan operasional')

@section('content')
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-700 p-6 text-white sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-100">Konveksi Gallery Anak Rantau · SOP</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight">Panduan kerja setiap modul</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-100">Temukan langkah penggunaan, kontrol serah-terima, dan panduan penyelesaian transaksi dari PO sampai penutupan.</p>
    </div>

    <form method="GET" action="{{ route('knowledge-base.index') }}" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,0.7fr)_auto]">
        <label class="sr-only" for="knowledge-search">Cari panduan</label>
        <input id="knowledge-search" type="search" name="q" value="{{ $search }}" placeholder="Cari menu atau langkah SOP..." class="min-w-0 rounded-xl border-slate-200 text-sm">
        <label class="sr-only" for="knowledge-menu">Pilih menu</label>
        <select id="knowledge-menu" name="menu" class="min-w-0 rounded-xl border-slate-200 text-sm">
            <option value="">Semua menu</option>
            @foreach ($menuOptions as $key => $title)
                <option value="{{ $key }}" @selected($selectedMenu === $key)>{{ $title }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Cari panduan</button>
    </form>

    @if ($articles->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-sm font-semibold text-slate-700">Panduan tidak ditemukan</p>
            <a href="{{ route('knowledge-base.index') }}" class="mt-2 inline-block text-sm font-semibold text-blue-700 hover:underline">Tampilkan semua panduan</a>
        </div>
    @else
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($articles as $article)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700">{{ $article['category'] }}</p>
                            <h3 class="mt-1 text-base font-bold text-slate-900">{{ $article['title'] }}</h3>
                        </div>
                        @if ($canEdit)
                            <a href="{{ route('knowledge-base.edit', $article) }}" aria-label="Edit panduan {{ $article['title'] }}" class="rounded-xl border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">Edit panduan</a>
                        @else
                            <a href="{{ route('knowledge-base.index', ['menu' => $article['key']]) }}" aria-label="Buka panduan {{ $article['title'] }}" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700 hover:bg-blue-100">?</a>
                        @endif
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $article['summary'] }}</p>
                    <ol class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                        @foreach ($article['steps'] as $step)
                            <li class="flex gap-3 text-xs leading-5 text-slate-600">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">{{ $loop->iteration }}</span>
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </article>
            @endforeach
        </div>
    @endif
@endsection
