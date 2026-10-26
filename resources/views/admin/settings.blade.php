@extends('layouts.app')

@section('title', 'Setting')
@section('heading', 'Setting')
@section('breadcrumb', 'Administrasi / Setting')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-600">Pengaturan sistem</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Tools database</h2>
        <p class="mt-1 text-sm text-slate-500">Database: {{ $databaseName }} · Driver {{ strtoupper($driver) }}</p>
    </div>

    @if (session('success'))
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @if (session('deleted_counts'))
            <section class="mb-5 rounded-xl border border-emerald-200 bg-white p-4">
                <h3 class="text-sm font-bold text-slate-800">Rekap data yang dihapus</h3>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs sm:grid-cols-3 lg:grid-cols-4">
                    @foreach (session('deleted_counts') as $table => $count)
                        <p class="rounded-lg bg-slate-50 px-3 py-2"><span class="text-slate-500">{{ str($table)->replace('_', ' ')->title() }}</span> <strong class="ml-1 text-slate-800">{{ number_format($count) }}</strong></p>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    <section class="overflow-hidden rounded-2xl border border-rose-200 bg-white">
        <div class="border-b border-rose-100 bg-rose-50 px-5 py-4 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-lg font-bold text-rose-700" aria-hidden="true">!</span>
                <div>
                    <h3 class="text-sm font-bold text-rose-900">Truncate seluruh data aplikasi</h3>
                    <p class="mt-1 text-xs leading-5 text-rose-800">Tindakan ini menghapus permanen transaksi, PO, DO/GRN, kartu stok, master data, cabang, pengguna lain, konfigurasi akses, Knowledge Base, dan Audit Log lama. Akun Super Admin yang sedang login dipertahankan.</p>
                </div>
            </div>
        </div>
        <div class="p-5 sm:p-6">
            <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Perkiraan isi yang akan dihapus</h4>
            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($counts as $table => $count)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                        <p class="truncate text-[10px] text-slate-500">{{ str($table)->replace('_', ' ')->title() }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ number_format($count) }} <span class="text-[10px] font-medium text-slate-400">baris</span></p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                <p class="font-bold">Wajib sebelum melanjutkan</p>
                <ul class="mt-1 list-inside list-disc space-y-1">
                    <li>Buat dan unduh backup dari menu Backup, lalu simpan di lokasi terpisah.</li>
                    <li>Data yang dihapus tidak dapat dipulihkan dari aplikasi tanpa restore backup.</li>
                    <li>Tabel infrastruktur Laravel dan migrasi aplikasi tidak dihapus.</li>
                    <li>Setelah reset, hanya akun Super Admin ini yang tersisa; buat ulang pengguna, master data, dan role sesuai kebutuhan.</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.settings.truncate') }}" class="mt-5 space-y-4" onsubmit="return confirm('Konfirmasi terakhir: seluruh data aplikasi akan dihapus permanen. Lanjutkan?')">
                @csrf
                <div>
                    <label for="current_password">Kata sandi Super Admin</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="confirmation">Ketik <strong class="text-rose-700">HAPUS SEMUA DATA</strong> untuk mengonfirmasi</label>
                    <input id="confirmation" name="confirmation" autocomplete="off" required placeholder="HAPUS SEMUA DATA">
                    @error('confirmation')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-start gap-2 !mb-0 rounded-xl border border-slate-200 p-3 text-xs leading-5 text-slate-700">
                    <input name="acknowledge_backup" type="checkbox" value="1" required class="mt-0.5 !h-4 !w-4 !shrink-0 !p-0">
                    <span>Saya sudah membuat backup terpisah dan memahami bahwa penghapusan ini permanen.</span>
                </label>
                @error('acknowledge_backup')<p class="-mt-3 text-xs text-rose-600">{{ $message }}</p>@enderror
                <button class="w-full rounded-xl bg-rose-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-rose-800 focus:outline-none focus:ring-4 focus:ring-rose-200 sm:w-auto">Hapus seluruh data aplikasi</button>
            </form>
        </div>
    </section>
@endsection
