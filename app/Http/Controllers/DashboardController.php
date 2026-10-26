<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'string', Rule::in(['day', 'month', 'year'])],
        ]);
        $period = $validated['period'] ?? 'month';
        $now = now();
        [$periodStart, $periodEnd] = match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };

        $orders = ProductionOrder::with(['creator', 'entries.user'])
            ->latest()
            ->get()
            ->filter(fn (ProductionOrder $order): bool => ($order->po_date ?? $order->created_at)
                ->betweenIncluded($periodStart, $periodEnd))
            ->values();

        $statusRanks = [
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
        $purchaseOrders = $orders->groupBy('po_number')->map(function ($items) use ($statusRanks): ProductionOrder {
            $openItems = $items->reject(fn (ProductionOrder $order): bool => $order->status === 'closed');
            $candidates = $openItems->isEmpty() ? $items : $openItems;

            return $candidates->sortByDesc(fn (ProductionOrder $order): int => $statusRanks[$order->status] ?? 0)->first();
        });
        $varianceByPurchaseOrder = $orders->groupBy('po_number')->map(function ($items, string $poNumber): array {
            $ratioTarget = (float) $items->sum(fn (ProductionOrder $order): float => (float) ($order->ratio_result_qty ?? $order->order_quantity));
            $cutting = (float) $items->sum(fn (ProductionOrder $order): int => $order->quantityFor('cutting_report'));
            $sewing = (float) $items->sum(fn (ProductionOrder $order): int => $order->quantityFor('sewing') + $order->quantityFor('site_return'));
            $vendorOutput = (float) $items->sum(fn (ProductionOrder $order): int => $order->quantityFor('vendor_receive'));

            return [
                'label' => $poNumber,
                'target' => $ratioTarget,
                'cutting' => $cutting,
                'ratio_variance' => round($cutting - $ratioTarget, 2),
                'sewing' => $sewing,
                'vendor_output' => $vendorOutput,
            ];
        });
        $locationGroups = [
            ['role' => 'gunung_karang', 'label' => 'Konveksi Gunung Karang', 'color' => '#0866c6'],
            ['role' => 'cibentang', 'label' => 'Konveksi Cibentang', 'color' => '#2d95f5'],
            ['role' => 'warehouse', 'label' => 'Gudang', 'color' => '#238b45'],
            ['role' => 'mydent', 'label' => 'Grosir Mydent', 'color' => '#655ce8'],
        ];
        $locationWorkload = collect($locationGroups)->map(fn (array $location): array => [
            ...$location,
            'value' => $purchaseOrders->filter(function (ProductionOrder $order) use ($location): bool {
                if ($order->status === 'closed') {
                    return false;
                }
                if ($order->pendingSiteQuantity() > 0) {
                    return $location['role'] === 'cibentang';
                }

                $role = match ($order->status) {
                    'draft', 'delivered' => 'mydent',
                    'fabric_requested', 'fabric_in_transit' => 'warehouse',
                    default => 'gunung_karang',
                };

                return $role === $location['role'];
            })->count(),
        ])->sortByDesc('value')->values();

        return view('dashboard', [
            'period' => $period,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'orders' => $orders->take(6),
            'openOrders' => $purchaseOrders->reject(fn (ProductionOrder $order): bool => $order->status === 'closed')->count(),
            'totalOrders' => $purchaseOrders->count(),
            'newOrders' => $purchaseOrders->filter(fn (ProductionOrder $order): bool => $order->status === 'draft')->count(),
            'processingOrders' => $purchaseOrders->filter(fn (ProductionOrder $order): bool => in_array($order->status, ['fabric_requested', 'fabric_in_transit', 'fabric_received', 'spk_issued', 'cutting_complete', 'in_production'], true))->count(),
            'waitingOrders' => $purchaseOrders->filter(fn (ProductionOrder $order): bool => in_array($order->status, ['quality_ready', 'shipped'], true))->count(),
            'completedOrders' => $purchaseOrders->filter(fn (ProductionOrder $order): bool => $order->status === 'delivered')->count(),
            'closedOrders' => $purchaseOrders->filter(fn (ProductionOrder $order): bool => $order->status === 'closed')->count(),
            'ratioCuttingVariance' => $varianceByPurchaseOrder->sortByDesc(fn (array $item): float => abs($item['ratio_variance']))->take(6)->values(),
            'productionVendorComparison' => $varianceByPurchaseOrder
                ->sortByDesc(fn (array $item): float => $item['sewing'] + $item['vendor_output'])
                ->take(6)
                ->values(),
            'locationWorkload' => $locationWorkload,
            'recentEntries' => $orders->flatMap(fn (ProductionOrder $order) => $order->entries->map(
                fn ($entry) => ['order' => $order, 'entry' => $entry],
            ))->sortByDesc(fn (array $item) => $item['entry']->created_at)->take(5),
        ]);
    }
}
