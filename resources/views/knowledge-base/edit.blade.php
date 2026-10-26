@extends('layouts.app')

@section('title', 'Edit Panduan')
@section('heading', 'Edit Knowledge Base')
@section('breadcrumb', 'Panduan operasional / Edit')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('knowledge-base.index', ['menu' => $article->key]) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Kembali ke Knowledge Base</a>
        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-700">Editor Super Admin</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Edit panduan menu</h2>
            <form method="POST" action="{{ route('knowledge-base.update', $article) }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="category">Kategori *</label>
                    <input id="category" name="category" value="{{ old('category', $article->category) }}" required maxlength="100">
                </div>
                <div>
                    <label for="title">Judul menu *</label>
                    <input id="title" name="title" value="{{ old('title', $article->title) }}" required maxlength="160">
                </div>
                <div>
                    <label for="summary">Ringkasan panduan *</label>
                    <textarea id="summary" name="summary" rows="3" required maxlength="2000">{{ old('summary', $article->summary) }}</textarea>
                </div>
                <div>
                    <label for="steps">Langkah SOP *</label>
                    <textarea id="steps" name="steps" rows="8" required maxlength="10000" aria-describedby="steps-help">{{ old('steps', implode("\n", $article->steps)) }}</textarea>
                    <p id="steps-help" class="mt-1 text-xs text-slate-500">Tulis satu langkah per baris; baris kosong akan diabaikan.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('knowledge-base.index', ['menu' => $article->key]) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Simpan panduan</button>
                </div>
            </form>
        </section>
    </div>
@endsection
