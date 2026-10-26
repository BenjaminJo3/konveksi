<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $order->po_number }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 0 0 5px; font-size: 12px; }
        p { margin: 3px 0; }
        .header { border-bottom: 2px solid #1f2937; margin-bottom: 18px; padding-bottom: 12px; }
        .muted { color: #64748b; }
        .meta { margin: 14px 0 18px; width: 100%; }
        .meta td { padding: 4px 10px 4px 0; vertical-align: top; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cbd5e1; padding: 7px 6px; text-align: left; }
        .items th { background: #f1f5f9; font-size: 8px; text-transform: uppercase; }
        .items td.number, .items th.number { text-align: right; }
        .items tfoot td { background: #f8fafc; font-weight: bold; }
        .signatures { margin-top: 45px; width: 100%; }
        .signatures td { padding: 0 18px; text-align: center; width: 33.33%; }
        .sign-line { margin-top: 58px; border-top: 1px solid #475569; padding-top: 6px; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; margin-top: 20px; padding-top: 7px; }
    </style>
</head>
<body>
    <header class="header">
        <table style="width:100%">
            <tr>
                <td>
                    <p class="muted">RUANG PRODUKSI · SISTEM MANAJEMEN KONVEKSI</p>
                    <h1>PURCHASE ORDER</h1>
                </td>
                <td style="text-align:right">
                    <h2>{{ $order->po_number }}</h2>
                    <p>Tanggal PO: {{ $order->po_date?->format('d F Y') ?? $order->created_at->format('d F Y') }}</p>
                    <p>Status: {{ $order->statusLabel() }}</p>
                </td>
            </tr>
        </table>
    </header>

    <table class="meta">
        <tr>
            <td><strong>Pemesan</strong><br>{{ $order->creator->name }}</td>
            <td><strong>Tanggal target selesai</strong><br>{{ $order->delivery_due_at?->format('d F Y') ?? 'Belum ditentukan' }}</td>
            <td><strong>Jumlah varian</strong><br>{{ number_format($items->count()) }} item</td>
        </tr>
    </table>

    <h2>Rincian item pesanan</h2>
    <table class="items">
        <thead>
            <tr>
                <th>No.</th>
                <th>Kategori / sub-kategori</th>
                <th>Produk / model</th>
                <th>Warna</th>
                <th>Ukuran</th>
                <th>Jenis kain</th>
                <th class="number">Kain (yard/kg)</th>
                <th class="number">Rib</th>
                <th class="number">Rasio (yard/pcs)</th>
                <th class="number">Estimasi hasil (pcs)</th>
                <th class="number">Target (pcs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->category ?: '—' }}{{ $item->sub_category ? ' / '.$item->sub_category : '' }}</td>
                    <td>{{ $item->product_name }}{{ $item->model ? ' / '.$item->model : '' }}</td>
                    <td>{{ $item->color ?: '—' }}</td>
                    <td>{{ $item->size ?: '—' }}</td>
                    <td>{{ $item->fabric_name ?: '—' }}</td>
                    <td class="number">{{ number_format((float) $item->yard_kg, 2) }}</td>
                    <td class="number">{{ $item->rib_qty === null ? '—' : number_format((float) $item->rib_qty, 2) }}</td>
                    <td class="number">{{ number_format((float) $item->ratio, 4) }}</td>
                    <td class="number">{{ number_format((float) $item->ratio_result_qty, 4) }}</td>
                    <td class="number">{{ number_format($item->order_quantity) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">TOTAL</td>
                <td class="number">{{ number_format((float) $totalFabric, 2) }}</td>
                <td class="number">{{ number_format((float) $totalRib, 2) }}</td>
                <td></td>
                <td></td>
                <td class="number">{{ number_format($totalQuantity) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top:14px"><strong>Catatan PO:</strong> {{ $order->notes ?: '—' }}</p>
    <table class="signatures">
        <tr>
            <td><div class="sign-line">Dibuat oleh<br>{{ $order->creator->name }}</div></td>
            <td><div class="sign-line">Diperiksa oleh<br>Admin / Gudang Konveksi</div></td>
            <td><div class="sign-line">Disetujui oleh<br>Toko Mydent</div></td>
        </tr>
    </table>
    <footer class="footer">Dokumen dibuat dari Konveksi Gallery Anak Rantau pada {{ now()->format('d F Y, H:i') }}. Dokumen ini memuat {{ $items->count() }} varian dalam satu PO.</footer>
</body>
</html>
