@extends('layouts.app')

@section('title', 'Master Cabang')
@section('heading', 'Master Cabang')
@section('breadcrumb', 'Administrasi / Master Cabang')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Lokasi operasional</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Master Cabang</h2>
        <p class="mt-1 text-sm text-slate-500">Cabang digunakan untuk penugasan pengguna dan filter laporan PO.</p>
    </div>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h3 class="text-sm font-bold text-slate-900">Tambah cabang</h3>
        <form method="POST" action="{{ route('admin.branches.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @csrf
            <div><label for="branch-name">Nama cabang</label><input id="branch-name" name="name" value="{{ old('name') }}" required maxlength="120"></div>
            <div><label for="branch-phone">Telepon (opsional)</label><input id="branch-phone" name="phone" value="{{ old('phone') }}" maxlength="40"></div>
            <div class="sm:col-span-2"><label for="branch-address">Alamat (opsional)</label><input id="branch-address" name="address" value="{{ old('address') }}" maxlength="500"></div>
            <div class="sm:col-span-2 xl:col-span-4"><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan cabang</button></div>
        </form>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        @forelse ($branches as $branch)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 flex items-start justify-between gap-3"><div><p class="font-mono text-[10px] font-bold text-emerald-700">{{ $branch->code }}</p><h3 class="mt-1 text-sm font-bold text-slate-900">{{ $branch->name }}</h3><p class="mt-1 text-[10px] text-slate-500">{{ $branch->users_count }} pengguna · {{ $branch->orders_count }} PO</p></div></div>
                <form method="POST" action="{{ route('admin.branches.update', $branch) }}" class="grid gap-3 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div><label for="name-{{ $branch->id }}">Nama cabang</label><input id="name-{{ $branch->id }}" name="name" value="{{ $branch->name }}" required></div>
                    <div><label for="phone-{{ $branch->id }}">Telepon</label><input id="phone-{{ $branch->id }}" name="phone" value="{{ $branch->phone }}"></div>
                    <div class="sm:col-span-2"><label for="address-{{ $branch->id }}">Alamat</label><input id="address-{{ $branch->id }}" name="address" value="{{ $branch->address }}"></div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2"><button class="rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">Simpan perubahan</button></div>
                </form>
                <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" class="mt-2" onsubmit="return confirm('Hapus cabang {{ $branch->name }}?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" @disabled($branch->users_count > 0 || $branch->orders_count > 0)>Hapus cabang</button>
                </form>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-xs text-slate-500 lg:col-span-2">Belum ada cabang. Tambahkan cabang operasional pertama.</div>
        @endforelse
    </section>
@endsection
