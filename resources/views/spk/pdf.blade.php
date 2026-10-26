<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $spkNumber }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .title { border-bottom: 2px solid #172033; padding-bottom: 13px; }
        .title h1 { font-size: 18px; margin: 7px 0; }
        .metadata { margin: 18px 0; width: 100%; }
        .metadata td { padding: 5px 0; width: 50%; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cbd5e1; padding: 7px; }
        .items th { background: #f1f5f9; text-align: left; }
        .instructions { background: #eff6ff; margin-top: 16px; padding: 12px; line-height: 1.6; }
        .signatures { margin-top: 34px; width: 100%; }
        .signatures td { text-align: center; vertical-align: bottom; width: 50%; }
        .signature-space { height: 90px; }
        .signature { height: 65px; max-width: 180px; object-fit: contain; }
        .sign-line { border-top: 1px solid #64748b; margin: 0 auto; padding-top: 6px; width: 78%; }
    </style>
</head>
<body>
    <header class="title center">
        <div class="muted">RUANG PRODUKSI · GUNUNG KARANG</div>
        <h1>SURAT PERINTAH KERJA</h1>
        <strong>{{ $spkNumber }}</strong>
    </header>
    <table class="metadata">
        <tr><td><span class="muted">Nomor PO</span><br><strong>{{ $group->po_number }}</strong></td><td><span class="muted">Tanggal PO</span><br><strong>{{ $group->po_date?->format('d F Y') ?? '—' }}</strong></td></tr>
        <tr><td><span class="muted">Diterbitkan oleh</span><br><strong>{{ $group->creator?->name ?? '—' }}</strong></td><td><span class="muted">Lokasi proses</span><br><strong>Gunung Karang</strong></td></tr>
    </table>
    <table class="items">
        <thead><tr><th>#</th><th>Produk / artikel</th><th>Warna / ukuran</th><th>Target PO</th><th>Target SPK</th></tr></thead>
        <tbody>
            @foreach ($orders as $order)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $order->product_name }}<br><span class="muted">{{ $order->model ?: $order->category }}</span></td><td>{{ $order->color ?: '—' }} / {{ $order->size ?: '—' }}</td><td>{{ number_format($order->order_quantity) }} pcs</td><td>{{ number_format($order->spk_quantity) }} pcs</td></tr>
            @endforeach
            <tr><th colspan="4" style="text-align:right">Total target SPK</th><th>{{ number_format($totalQuantity) }} pcs</th></tr>
        </tbody>
    </table>
    <div class="instructions"><strong>Instruksi kerja</strong><br>Laksanakan pekerjaan sesuai spesifikasi PO dan target SPK. Catat hasil cutting serta selisih aktual pada menu Input Hasil Cutting sebelum pekerjaan diteruskan ke proses berikutnya.</div>
    <table class="signatures">
        <tr><td><div class="signature-space"></div><div class="sign-line">{{ $group->creator?->name ?? 'Admin Workspace' }}<br><span class="muted">Admin / penerbit SPK</span></div></td>
            <td>
                @if ($signatureDataUri)<div class="signature-space"><img src="{{ $signatureDataUri }}" class="signature" alt="Tanda tangan"></div>@else<div class="signature-space"></div>@endif
                <div class="sign-line">{{ $group->signer?->name ?? 'Penanggung jawab produksi' }}<br><span class="muted">@if ($group->spk_signed_at)Ditandatangani {{ $group->spk_signed_at->format('d/m/Y H:i') }}@else Tanda tangan @endif</span></div>
            </td>
        </tr>
    </table>
</body>
</html>
