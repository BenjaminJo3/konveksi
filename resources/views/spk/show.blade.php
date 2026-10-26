@extends('layouts.app')

@section('title', $spkNumber)
@section('heading', 'Detail SPK')
@section('breadcrumb', 'Produksi / SPK / '.$group->po_number)

@section('content')
    <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <a href="{{ route('spk.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Kembali ke daftar SPK</a>
        <a href="{{ route('spk.pdf', $group) }}" class="inline-flex justify-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Unduh PDF</a>
    </div>

    <article class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <header class="border-b-2 border-slate-900 pb-5 text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Konveksi Gallery Anak Rantau · Gunung Karang</p>
            <h2 class="mt-2 text-xl font-black tracking-wide text-slate-900 sm:text-2xl">SURAT PERINTAH KERJA</h2>
            <p class="mt-2 text-sm font-semibold text-blue-700">{{ $spkNumber }}</p>
        </header>
        <div class="grid gap-x-8 gap-y-3 border-b border-slate-200 py-5 text-sm sm:grid-cols-2">
            <div><span class="text-slate-500">Nomor PO</span><p class="mt-1 font-bold text-slate-900">{{ $group->po_number }}</p></div>
            <div><span class="text-slate-500">Tanggal PO</span><p class="mt-1 font-semibold text-slate-900">{{ $group->po_date?->format('d F Y') ?? '—' }}</p></div>
            <div><span class="text-slate-500">Diterbitkan oleh</span><p class="mt-1 font-semibold text-slate-900">{{ $group->creator?->name ?? '—' }}</p></div>
            <div><span class="text-slate-500">Lokasi proses</span><p class="mt-1 font-semibold text-slate-900">Gunung Karang</p></div>
        </div>
        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-3">#</th><th class="px-3 py-3">Produk / artikel</th><th class="px-3 py-3">Warna / ukuran</th><th class="px-3 py-3">Target PO</th><th class="px-3 py-3">Target SPK</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <tr><td class="px-3 py-3 text-slate-500">{{ $loop->iteration }}</td><td class="px-3 py-3"><p class="font-semibold text-slate-900">{{ $order->product_name }}</p><p class="text-xs text-slate-500">{{ $order->model ?: $order->category }}</p></td><td class="px-3 py-3 text-slate-600">{{ $order->color ?: '—' }} / {{ $order->size ?: '—' }}</td><td class="px-3 py-3 text-slate-600">{{ number_format($order->order_quantity) }} pcs</td><td class="px-3 py-3 font-bold text-slate-900">{{ number_format($order->spk_quantity) }} pcs</td></tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-900"><tr><td colspan="4" class="px-3 py-3 text-right">Total target SPK</td><td class="px-3 py-3">{{ number_format($orders->sum('spk_quantity')) }} pcs</td></tr></tfoot>
            </table>
        </div>
        <div class="mt-5 rounded-xl bg-blue-50 p-4 text-xs leading-5 text-blue-900">
            <p class="font-bold">Instruksi kerja</p>
            <p class="mt-1">Laksanakan pekerjaan sesuai spesifikasi PO dan target SPK. Catat hasil cutting serta selisih aktual pada menu Input Hasil Cutting sebelum pekerjaan diteruskan ke proses berikutnya.</p>
        </div>

        <div class="mt-8 grid gap-8 sm:grid-cols-2">
            <div class="text-center">
                <p class="text-xs font-semibold text-slate-600">Diterbitkan oleh</p>
                <div class="mx-auto mt-3 flex h-24 max-w-xs items-end justify-center border-b border-slate-400 pb-2 text-sm font-semibold text-slate-800">{{ $group->creator?->name ?? 'Admin Workspace' }}</div>
                <p class="mt-2 text-[11px] text-slate-500">Admin / penerbit SPK</p>
            </div>
            <div class="text-center">
                <p class="text-xs font-semibold text-slate-600">Penanggung jawab produksi</p>
                <div class="mx-auto mt-3 flex h-24 max-w-xs items-end justify-center border-b border-slate-400 pb-2">
                    @if ($signatureDataUri)<img src="{{ $signatureDataUri }}" alt="Tanda tangan {{ $group->signer?->name }}" class="max-h-20 max-w-full object-contain">@endif
                </div>
                @if ($group->spk_signed_at)
                    <p class="mt-2 text-xs font-bold text-slate-800">{{ $group->signer?->name ?? 'Penanggung jawab' }}</p>
                    <p class="text-[10px] text-slate-500">Ditandatangani {{ $group->spk_signed_at->format('d/m/Y H:i') }}</p>
                @else
                    <p class="mt-2 text-[11px] text-slate-500">Tanda tangan penanggung jawab</p>
                @endif
            </div>
        </div>
    </article>

    @if ($canSign && ! $group->spk_signed_at)
        <section class="mx-auto mt-5 max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-bold text-slate-900">Tanda tangan penanggung jawab</h3>
            <p class="mt-1 text-sm leading-5 text-slate-500">Gunakan layar sentuh/trackpad untuk tanda tangan langsung. Di desktop, Anda dapat mengunggah scan JPG.</p>
            <form method="POST" action="{{ route('spk.sign', $group) }}" enctype="multipart/form-data" data-signature-form class="mt-4">
                @csrf
                <div class="signature-upload mb-4">
                    <label for="signature_image" class="mb-2 block text-xs font-semibold text-slate-700">Unggah tanda tangan (JPG, maks. 2 MB)</label>
                    <input id="signature_image" name="signature_image" type="file" accept="image/jpeg" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="signature-pad-wrap">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="signature-canvas" class="text-xs font-semibold text-slate-700">Tanda tangan di area berikut</label>
                        <button type="button" data-signature-clear class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Hapus tanda tangan</button>
                    </div>
                    <canvas id="signature-canvas" data-signature-canvas class="h-40 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50"></canvas>
                    <input type="hidden" name="signature_data" data-signature-data>
                </div>
                <button class="mt-4 w-full rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 sm:w-auto">Simpan dan kunci tanda tangan</button>
            </form>
        </section>
    @elseif ($group->spk_signed_at)
        <p class="mx-auto mt-4 max-w-4xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800">SPK telah ditandatangani dan dikunci. Perubahan tanda tangan tidak dapat dilakukan.</p>
    @endif
@endsection
