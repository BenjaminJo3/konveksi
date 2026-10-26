<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 9px; }
        h1 { margin: 0 0 5px; font-size: 18px; }
        .meta { color: #64748b; margin-bottom: 12px; }
        .summary { margin-bottom: 14px; }
        .summary td { padding: 6px 10px; background: #f1f5f9; border-right: 2px solid #fff; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th { padding: 7px 5px; background: #0f766e; color: #fff; text-align: left; }
        .data td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Operasional Produksi</h1>
    <p class="meta">Periode {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }} · Dicetak {{ now()->format('d M Y H:i') }}</p>
    <table class="summary"><tr><td>Jumlah PO: <strong>{{ number_format($summary['po_count']) }}</strong></td><td>Target: <strong>{{ number_format($summary['target']) }} pcs</strong></td><td>Cutting: <strong>{{ number_format($summary['cutting']) }} pcs</strong></td><td>Jahit: <strong>{{ number_format($summary['sewing']) }} pcs</strong></td><td>Diterima: <strong>{{ number_format($summary['received']) }} pcs</strong></td></tr></table>
    <table class="data">
        <thead><tr><th>Tanggal</th><th>No. PO</th><th>Cabang</th><th>Produk</th><th class="number">Target</th><th class="number">Cutting</th><th class="number">Jahit</th><th class="number">Lolos QC</th><th class="number">Diterima</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($orders as $order)
                <tr><td>{{ $order->po_date?->format('d/m/Y') }}</td><td>{{ $order->po_number }}</td><td>{{ $order->branch?->name ?? 'Belum ditentukan' }}</td><td>{{ $order->product_name }}{{ $order->color ? ' · '.$order->color : '' }}</td><td class="number">{{ number_format($order->order_quantity) }}</td><td class="number">{{ number_format($order->quantityFor('cutting_report')) }}</td><td class="number">{{ number_format($order->finishedQuantity()) }}</td><td class="number">{{ number_format($order->quantityFor('quality_check')) }}</td><td class="number">{{ number_format($order->quantityFor('goods_receipt')) }}</td><td>{{ $order->statusLabel() }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
