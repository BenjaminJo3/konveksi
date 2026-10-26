<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductionOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$orders, $filters] = $this->filteredOrders($request);
        $orders = $orders->get();

        return view('reports.index', [
            'orders' => $orders,
            'filters' => $filters,
            'branches' => Branch::query()->orderBy('name')->get(),
            'summary' => $this->summary($orders),
        ]);
    }

    public function pdf(Request $request)
    {
        [$query, $filters] = $this->filteredOrders($request);
        $orders = $query->get();

        return Pdf::loadView('reports.pdf', [
            'orders' => $orders,
            'filters' => $filters,
            'summary' => $this->summary($orders),
        ])->setPaper('a4', 'landscape')
            ->download('laporan-produksi-'.$filters['from'].'-'.$filters['to'].'.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        [$query, $filters] = $this->filteredOrders($request);
        $orders = $query->get();
        $filename = 'laporan-produksi-'.$filters['from'].'-'.$filters['to'].'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];
        $callback = static function () use ($orders): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Stream ekspor Excel tidak dapat dibuat.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['No. PO', 'Tanggal PO', 'Cabang', 'Produk', 'Warna', 'Ukuran', 'Target (pcs)', 'Hasil Cutting (pcs)', 'Hasil Jahit (pcs)', 'Lolos QC (pcs)', 'Diterima (pcs)', 'Status'], ';');
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order->po_number,
                    $order->po_date?->format('Y-m-d'),
                    $order->branch?->name ?? 'Belum ditentukan',
                    $order->product_name,
                    $order->color,
                    $order->size,
                    $order->order_quantity,
                    $order->quantityFor('cutting_report'),
                    $order->finishedQuantity(),
                    $order->quantityFor('quality_check'),
                    $order->quantityFor('goods_receipt'),
                    $order->statusLabel(),
                ], ';');
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * @return array{0: Builder<ProductionOrder>, 1: array{from: string, to: string, branch_id: string}}
     */
    private function filteredOrders(Request $request): array
    {
        $values = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
        $from = $values['from'] ?? now()->startOfMonth()->toDateString();
        $to = $values['to'] ?? now()->toDateString();
        $branchId = (string) ($values['branch_id'] ?? '');

        $query = ProductionOrder::query()
            ->with(['branch', 'entries'])
            ->whereBetween('po_date', [$from, $to])
            ->when($branchId !== '', fn (Builder $builder) => $builder->where('branch_id', $branchId))
            ->orderBy('po_number')
            ->orderBy('id');

        return [$query, ['from' => $from, 'to' => $to, 'branch_id' => $branchId]];
    }

    private function summary(Collection $orders): array
    {
        return [
            'po_count' => $orders->pluck('po_number')->unique()->count(),
            'target' => $orders->sum('order_quantity'),
            'cutting' => $orders->sum(fn (ProductionOrder $order): int => $order->quantityFor('cutting_report')),
            'sewing' => $orders->sum(fn (ProductionOrder $order): int => $order->finishedQuantity()),
            'received' => $orders->sum(fn (ProductionOrder $order): int => $order->quantityFor('goods_receipt')),
        ];
    }
}
