@extends('layouts.app')

@section('title', 'Profil & Keamanan')
@section('heading', 'Profil & keamanan akun')
@section('breadcrumb', 'Profil')

@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Kelola informasi akun dan keamanan akses workspace.</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Pengaturan akun</h2>
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">Profil pengguna</h3>
                <p class="mt-1 text-xs text-slate-400">Perbarui nama dan alamat email yang digunakan untuk masuk.</p>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label for="name">Nama lengkap</label>
                    <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" autocomplete="name" required>
                    @error('name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email">Alamat email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email" required>
                    @error('email')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] text-slate-500">Peran akun</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ auth()->user()->roleLabel() }}</p>
                    <p class="mt-1 text-[10px] text-slate-400">Peran dikelola oleh Admin Workspace.</p>
                </div>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800">Simpan profil</button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">Ganti kata sandi</h3>
                <p class="mt-1 text-xs text-slate-400">Masukkan kata sandi saat ini untuk mengatur kata sandi baru.</p>
            </div>
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password">Kata sandi saat ini</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password">Kata sandi baru</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    @error('password')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-[10px] text-slate-400">Minimal 8 karakter.</p>
                </div>
                <div>
                    <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800">Ganti kata sandi</button>
            </form>
        </section>
    </div>
@endsection
