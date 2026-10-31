@extends('layouts.app')

@section('title', 'Upload Master Data')
@section('heading', 'Upload Master Data')
@section('breadcrumb', 'Master Barang / Upload')

@section('content')
    <div class="mb-5">
        <a href="{{ route('master-data.products') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Kembali ke daftar barang</a>
    </div>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,0.8fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-700">Import katalog</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Upload file master data</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Unggah Excel (.xlsx) atau CSV sesuai tabel contoh. Kategori dan atribut yang belum ada akan dibuat otomatis sebelum barang disimpan; kategori seperti Celana terpisah dari jenis kain seperti Chinos.</p>

            <form method="POST" action="{{ route('master-data.import.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="master-data-file">File Excel atau CSV *</label>
                    <input id="master-data-file" type="file" name="file" required accept=".xlsx,.csv,text/csv" class="file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-blue-700">
                    <p class="mt-1 text-xs leading-5 text-slate-500">Format .xlsx atau .csv, maksimal 10 MB dan 5.000 baris data. Gunakan worksheet pertama pada Excel.</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                    <p class="font-bold">Perhatian sebelum upload</p>
                    <p class="mt-1">Setiap baris valid diproses dalam satu transaksi. Jika ada data wajib yang salah, seluruh upload dibatalkan. Data produk yang sudah terdaftar dilewati sebagai duplikat.</p>
                    <p class="mt-1">Kolom UKURAN boleh kosong; sistem akan menggunakan ukuran “All Size”. Kolom NAMA BARANG hanya untuk kompatibilitas dan akan diabaikan karena nama dibuat otomatis. Unit barang otomatis “Pcs”.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('master-data.import.template') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Unduh template CSV</a>
                    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Upload dan proses</button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <h3 class="text-sm font-bold text-slate-900">Susunan kolom</h3>
            <p class="mt-1 text-xs leading-5 text-slate-500">Header harus tersedia dalam baris pertama. Ejaan dan tanda baca header dikenali secara fleksibel.</p>
            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[440px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-2.5">Header</th><th class="px-3 py-2.5">Contoh / aturan</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr><td class="px-3 py-2.5 font-semibold">KATEGORI</td><td class="px-3 py-2.5">CELANA · wajib</td></tr>
                        <tr><td class="px-3 py-2.5 font-semibold">SUB_KATEGORI</td><td class="px-3 py-2.5">CIPIR PENDEK · wajib</td></tr>
                        <tr><td class="px-3 py-2.5 font-semibold">UKURAN</td><td class="px-3 py-2.5">S, M, L · boleh kosong</td></tr>
                        <tr><td class="px-3 py-2.5 font-semibold">ARTIKEL</td><td class="px-3 py-2.5">Model / artikel · boleh kosong</td></tr>
                        <tr><td class="px-3 py-2.5 font-semibold">WARNA</td><td class="px-3 py-2.5">UNGU · wajib</td></tr>
                        <tr><td class="px-3 py-2.5 font-semibold">JENIS KAIN</td><td class="px-3 py-2.5">CHINOS · opsional, disimpan pada jenis bahan</td></tr>
                        <tr><td class="px-3 py-2.5 font-semibold">NAMA BARANG</td><td class="px-3 py-2.5">Otomatis: SUB_KATEGORI + WARNA + ARTIKEL + JENIS KAIN + UKURAN · opsional</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Template lama dengan header BAHAN / KATEGORI tetap didukung. Kode barang serta kode kategori, sub-kategori, artikel (jika diisi), ukuran, warna, dan unit dibuat otomatis. Bila ukuran kosong, nilai katalog menjadi “All Size”.</p>
        </section>
    </div>
@endsection
