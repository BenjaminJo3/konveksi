<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationalKanbanController extends Controller
{
    private const COLUMNS = [
        'new' => ['PO Baru', 'Menunggu permintaan kain', 'bg-slate-500'],
        'fabric' => ['Permintaan & Kain', 'Pengadaan dan penerimaan kain', 'bg-sky-600'],
        'cutting' => ['SPK & Cutting', 'Persiapan dan pemotongan', 'bg-indigo-600'],
        'production' => ['Vendor / Cibentang / Produksi', 'Makloon dan proses jahit', 'bg-violet-600'],
        'qc' => ['QC & Pengiriman', 'Pemeriksaan dan surat jalan', 'bg-amber-600'],
        'received' => ['Diterima Mydent', 'Penerimaan barang jadi', 'bg-emerald-600'],
        'closed' => ['Close PO', 'Rekonsiliasi akhir selesai', 'bg-slate-800'],
    ];

    private const STATUS_RANKS = [
        'draft' => 0,
        'fabric_requested' => 1,
        'fabric_in_transit' => 2,
        'fabric_received' => 3,
        'spk_issued' => 4,
        'cutting_complete' => 5,
        'in_production' => 6,
        'quality_ready' => 7,
        'shipped' => 8,
        'delivered' => 9,
        'closed' => 10,
    ];

    public function __invoke(Request $request): View
    {
        $orders = ProductionOrder::query()
            ->with(['creator', 'entries.user'])
            ->latest()
            ->get()
            ->groupBy('po_number')
            ->map(function ($items): array {
                $openItems = $items->reject(fn (ProductionOrder $order): bool => $order->status === 'closed');
                $candidates = $openItems->isEmpty() ? $items : $openItems;
                $order = $candidates->sortByDesc(
                    fn (ProductionOrder $item): int => self::STATUS_RANKS[$item->status] ?? 0,
                )->first();
                $latestEntry = $items->flatMap(fn (ProductionOrder $item) => $item->entries)
                    ->sortByDesc('created_at')
                    ->first();
                $orderedQuantity = (int) $items->sum('order_quantity');
                $receivedQuantity = (int) $items->sum(fn (ProductionOrder $item): int => $item->quantityFor('goods_receipt'));
                $createdAt = $items->min('created_at');
                $stageStartedAt = $latestEntry?->created_at ?? $createdAt;
                $nextAction = $this->nextAction($order);
                $isOverdue = $order->delivery_due_at !== null
                    && $order->delivery_due_at->isBefore(today())
                    && ! in_array($order->status, ['delivered', 'closed'], true);

                return [
                    'order' => $order,
                    'items' => $items,
                    'stage' => $this->stageFor($order->status),
                    'quantity' => $orderedQuantity,
                    'cutting' => (int) $items->sum(fn (ProductionOrder $item): int => $item->quantityFor('cutting_report')),
                    'received' => $receivedQuantity,
                    'progress' => $orderedQuantity > 0
                        ? min(100, (int) round($receivedQuantity / $orderedQuantity * 100))
                        : 0,
                    'latestEntry' => $latestEntry,
                    'poAgeDays' => $this->elapsedDays($createdAt),
                    'stageAgeDays' => $this->elapsedDays($stageStartedAt),
                    'isOverdue' => $isOverdue,
                    'overdueDays' => $isOverdue ? (int) $order->delivery_due_at->diffInDays(today()) : 0,
                    'nextAction' => $nextAction,
                ];
            });

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $orders = $orders->filter(function (array $card) use ($search): bool {
                return str_contains(mb_strtolower($card['order']->po_number), mb_strtolower($search))
                    || $card['items']->contains(fn (ProductionOrder $item): bool => str_contains(
                        mb_strtolower($item->product_name.' '.$item->color),
                        mb_strtolower($search),
                    ));
            });
        }

        $columns = collect(self::COLUMNS)->map(fn (array $column, string $key): array => [
            'key' => $key,
            'title' => $column[0],
            'description' => $column[1],
            'color' => $column[2],
            'orders' => $orders->filter(fn (array $card): bool => $card['stage'] === $key)->values(),
        ])->values();

        return view('orders.kanban', [
            'columns' => $columns,
            'totalOrders' => $orders->count(),
            'overdueOrders' => $orders->where('isOverdue', true)->count(),
            'search' => $search,
        ]);
    }

    private function elapsedDays(CarbonInterface $date): int
    {
        return (int) $date->diffInDays(now());
    }

    private function nextAction(ProductionOrder $order): array
    {
        return match ($order->status) {
            'draft' => ['Mydent', 'Ajukan permintaan kain'],
            'fabric_requested', 'fabric_in_transit' => ['Gudang', 'Kirim dan konfirmasi kain'],
            'fabric_received' => ['Admin PO', 'Terbitkan SPK'],
            'spk_issued' => ['Gunung Karang', 'Mulai proses cutting'],
            'cutting_complete' => ['Gunung Karang', 'Alokasikan jahit atau makloon'],
            'in_production' => $this->productionNextAction($order),
            'quality_ready' => ['Gunung Karang', 'Siapkan dan kirim barang jadi'],
            'shipped' => ['Mydent', 'Catat penerimaan barang (GRN)'],
            'delivered' => ['Mydent', 'Rekonsiliasi dan Close PO'],
            'closed' => ['Selesai', 'Tidak ada aksi lanjutan'],
            default => ['Admin PO', 'Periksa status PO'],
        };
    }

    private function productionNextAction(ProductionOrder $order): array
    {
        if ($order->pendingSiteQuantity() > 0) {
            return ['Cibentang', 'Konfirmasi penerimaan potongan'];
        }
        if ($order->pendingVendorQuantity() > 0) {
            return ['Vendor / Gunung Karang', 'Tindak lanjuti retur vendor'];
        }
        if ($order->availableForSewing() > 0) {
            return ['Gunung Karang', 'Catat proses jahit'];
        }
        if ($order->finishedQuantity() > $order->quantityFor('finishing')) {
            return ['Gunung Karang', 'Selesaikan finishing'];
        }
        if ($order->remainingQcQuantity() > 0) {
            return ['Gunung Karang', 'Lakukan pemeriksaan QC'];
        }

        return ['Gunung Karang', 'Periksa transaksi produksi berikutnya'];
    }

    private function stageFor(string $status): string
    {
        return match ($status) {
            'draft' => 'new',
            'fabric_requested', 'fabric_in_transit' => 'fabric',
            'fabric_received', 'spk_issued', 'cutting_complete' => 'cutting',
            'in_production' => 'production',
            'quality_ready', 'shipped' => 'qc',
            'delivered' => 'received',
            'closed' => 'closed',
            default => 'new',
        };
    }
}
