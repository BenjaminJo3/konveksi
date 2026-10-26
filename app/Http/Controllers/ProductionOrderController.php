<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Services\RolePermissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionOrderController extends Controller
{
    private const ACTION_DETAILS = [
        'request_fabric' => ['Permintaan kain', 'Toko Mydent mengajukan kebutuhan kain untuk PO ini.'],
        'deliver_fabric' => ['Kirim kain & terbitkan DO', 'Gudang mencatat nomor DO dan jumlah kain yang dikirim.'],
        'receive_fabric' => ['Terima & verifikasi kain', 'Catat hasil ukur fisik; selisih wajib disertai catatan.'],
        'issue_spk' => ['Terbitkan SPK', 'Buat surat perintah kerja untuk cutting di Gunung Karang.'],
        'request_material' => ['Minta bahan tambahan', 'Mandor atau Admin mengajukan kebutuhan benang, kancing, zipper, atau label.'],
        'issue_material' => ['Keluarkan bahan & serah-terima', 'Gudang mencatat batch, jumlah, dan penerima material.'],
        'cutting_report' => ['Laporan cutting', 'Catat jumlah potong aktual. Seluruh cutting dilakukan di Gunung Karang.'],
        'vendor_send' => ['Kirim ke vendor', 'Kirim potongan ke vendor sablon atau bordir.'],
        'vendor_receive' => ['Terima hasil vendor', 'Pisahkan hasil baik dan reject vendor.'],
        'site_transfer' => ['Transfer ke Cibentang', 'Kirim alokasi potongan untuk proses jahit di Cibentang.'],
        'site_receive' => ['Terima potongan di Cibentang', 'Konfirmasi jumlah fisik potongan yang tiba di Cibentang.'],
        'site_return' => ['Terima hasil jahit Cibentang', 'Gunung Karang mencatat hasil baik dan reject jahitan yang kembali.'],
        'sewing' => ['Catat jahit Gunung Karang', 'Catat jumlah baju yang selesai dijahit di Gunung Karang.'],
        'finishing' => ['Finishing', 'Catat jumlah baju selesai finishing dan siap diperiksa QC.'],
        'quality_check' => ['Quality control', 'Rekonsiliasi jumlah diperiksa, lolos QC, dan reject.'],
        'prepare_shipment' => ['Siapkan pengiriman', 'Buat surat jalan dan alokasikan stok yang lolos QC.'],
        'shipment' => ['Tandai barang telah dikirim', 'Konfirmasi keberangkatan berdasarkan surat jalan yang disiapkan.'],
        'goods_receipt' => ['Penerimaan Mydent', 'Toko mencatat jumlah fisik yang diterima dari pengiriman.'],
        'close_order' => ['Close PO', 'Rekonsiliasi akhir; PO yang ditutup tidak dapat menerima transaksi baru.'],
    ];

    public function index(Request $request): View
    {
        $query = ProductionOrder::with(['creator', 'entries.user'])->latest();
        $status = $request->string('status')->toString();

        if ($status === 'active') {
            $query->where('status', '!=', 'closed');
        } elseif ($status === 'closed') {
            $query->where('status', 'closed');
        } elseif ($status === 'draft') {
            $query->where('status', 'draft');
        } elseif ($status === 'processing') {
            $query->whereIn('status', ['fabric_requested', 'fabric_in_transit', 'fabric_received', 'spk_issued', 'cutting_complete', 'in_production']);
        } elseif ($status === 'waiting') {
            $query->whereIn('status', ['quality_ready', 'shipped']);
        } elseif ($status === 'completed') {
            $query->where('status', 'delivered');
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('po_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        return view('orders.index', [
            'orders' => $query->paginate(10)->withQueryString(),
            'statusFilter' => $status,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canPermission('orders.create'), 403);

        return view('orders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canPermission('orders.create'), 403);

        $legacyPayload = ! $request->has('items');
        if ($legacyPayload) {
            $legacy = $request->validate([
                'po_number' => ['nullable', 'string', 'max:60'],
                'product_name' => ['required', 'string', 'max:160'],
                'color' => ['nullable', 'string', 'max:80'],
                'fabric_name' => ['nullable', 'string', 'max:120'],
                'order_quantity' => ['required', 'integer', 'min:1'],
                'fabric_estimate' => ['required', 'numeric', 'min:0'],
                'fabric_ratio' => ['required', 'numeric', 'gt:0'],
                'delivery_due_at' => ['nullable', 'date'],
            ]);
            $items = [[
                'product_name' => $legacy['product_name'],
                'color' => $legacy['color'] ?? null,
                'fabric_name' => $legacy['fabric_name'] ?? null,
                'target_qty' => (int) $legacy['order_quantity'],
                'yard_kg' => $legacy['fabric_estimate'],
                'ratio' => 1 / (float) $legacy['fabric_ratio'],
            ]];
            $poDate = now()->toDateString();
            $manualPoNumber = $legacy['po_number'] ?? null;
            $numberMode = $manualPoNumber ? 'manual' : 'automatic';
        } else {
            $values = $request->validate([
                'po_number_mode' => ['required', Rule::in(['automatic', 'manual'])],
                'po_number' => [
                    Rule::requiredIf(fn (): bool => $request->input('po_number_mode') === 'manual'),
                    'nullable',
                    'string',
                    'max:60',
                ],
                'po_date' => ['required', 'date'],
                'delivery_due_at' => ['nullable', 'date'],
                'items' => ['required', 'array', 'min:1', 'max:50'],
                'items.*.category' => ['required', 'string', 'max:120'],
                'items.*.sub_category' => ['required', 'string', 'max:120'],
                'items.*.model' => ['required', 'string', 'max:120'],
                'items.*.size' => ['required', 'string', 'max:80'],
                'items.*.color' => ['required', 'string', 'max:80'],
                'items.*.product_name' => ['required', 'string', 'max:160'],
                'items.*.fabric_name' => ['nullable', 'string', 'max:120'],
                'items.*.yard_kg' => ['required', 'numeric', 'gt:0', 'max:100000000'],
                'items.*.rib_qty' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
                'items.*.ratio' => ['required', 'numeric', 'decimal:0,4', 'gt:0', 'max:100000000'],
            ]);
            $items = $values['items'];
            $poDate = $values['po_date'];
            $manualPoNumber = $values['po_number'] ?? null;
            $numberMode = $values['po_number_mode'];
        }

        if ($numberMode === 'manual'
            && (DB::table('production_order_groups')->where('po_number', trim((string) $manualPoNumber))->exists()
                || ProductionOrder::where('po_number', trim((string) $manualPoNumber))->exists())) {
            throw ValidationException::withMessages([
                'po_number' => 'Nomor PO sudah digunakan. Gunakan nomor lain atau pilih nomor otomatis.',
            ]);
        }

        foreach ($items as $index => &$item) {
            $ratioResult = isset($item['target_qty'])
                ? (float) $item['target_qty']
                : round((float) $item['yard_kg'] / (float) $item['ratio'], 4, PHP_ROUND_HALF_UP);
            $targetQuantity = isset($item['target_qty'])
                ? (int) $item['target_qty']
                : (int) round($ratioResult, 0, PHP_ROUND_HALF_UP);

            if ($ratioResult > 999999999.9999 || $targetQuantity < 1 || $targetQuantity > 4294967295) {
                throw ValidationException::withMessages([
                    "items.{$index}.yard_kg" => 'Hasil perhitungan rasio harus sedikitnya 1 pcs dan tidak melebihi kapasitas sistem.',
                ]);
            }

            $item['ratio_result_qty'] = $ratioResult;
            $item['order_quantity'] = $targetQuantity;
            $item['fabric_estimate'] = $item['yard_kg'];
            $item['fabric_ratio'] = 1 / (float) $item['ratio'];
        }
        unset($item);

        $order = DB::transaction(function () use ($request, $items, $poDate, $manualPoNumber, $numberMode): ProductionOrder {
            $poNumber = $numberMode === 'manual'
                ? trim((string) $manualPoNumber)
                : $this->nextPoNumber(Carbon::parse($poDate)->year);
            if (DB::table('production_order_groups')->where('po_number', $poNumber)->exists()
                || ProductionOrder::where('po_number', $poNumber)->exists()) {
                throw ValidationException::withMessages([
                    'po_number' => 'Nomor PO sudah digunakan. Gunakan nomor lain atau pilih nomor otomatis.',
                ]);
            }
            DB::table('production_order_groups')->insert([
                'po_number' => $poNumber,
                'po_date' => $poDate,
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $createdOrders = collect($items)->map(function (array $item) use ($request, $poDate, $poNumber): ProductionOrder {
                $order = ProductionOrder::create([
                    'po_number' => $poNumber,
                    'po_date' => $poDate,
                    'category' => $item['category'] ?? null,
                    'sub_category' => $item['sub_category'] ?? null,
                    'model' => $item['model'] ?? null,
                    'size' => $item['size'] ?? null,
                    'product_name' => $item['product_name'],
                    'color' => $item['color'] ?? null,
                    'fabric_name' => $item['fabric_name'] ?? null,
                    'order_quantity' => $item['order_quantity'],
                    'fabric_estimate' => $item['fabric_estimate'],
                    'fabric_ratio' => $item['fabric_ratio'],
                    'yard_kg' => $item['yard_kg'],
                    'rib_qty' => $item['rib_qty'] ?? null,
                    'ratio' => $item['ratio'],
                    'ratio_result_qty' => $item['ratio_result_qty'],
                    'delivery_due_at' => $request->input('delivery_due_at'),
                    'created_by' => $request->user()->id,
                    'branch_id' => $request->user()->branch_id,
                ]);

                $this->record($order, $request->user()->id, 'po_created', [
                    'notes' => 'Item Purchase Order dibuat.',
                ]);

                return $order;
            });

            return $createdOrders->first();
        });

        return redirect()->route('orders.show', $order)->with('success', 'Purchase Order berhasil dibuat.');
    }

    private function nextPoNumber(int $year): string
    {
        $prefix = "MYD-{$year}-";
        $numbers = DB::table('production_order_groups')
            ->where('po_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('po_number')
            ->map(fn (string $number): int => (int) substr($number, strlen($prefix)));

        return $prefix.str_pad((string) ($numbers->max() + 1), 3, '0', STR_PAD_LEFT);
    }

    public function show(ProductionOrder $order): View
    {
        $order->load(['creator', 'entries.user']);
        $entries = $order->entries;

        return view('orders.show', [
            'order' => $order,
            'poItems' => ProductionOrder::query()
                ->where('po_number', $order->po_number)
                ->orderBy('id')
                ->get(),
            'timeline' => $entries,
            'availableActions' => $this->availableActions($order),
            'summary' => [
                'cut' => $order->quantityFor('cutting_report'),
                'sewn' => $order->finishedQuantity(),
                'passed' => $order->quantityFor('quality_check'),
                'rejected' => $order->quantityFor('quality_check', 'secondary_quantity'),
                'shipped' => $order->quantityFor('shipment'),
                'received' => $order->quantityFor('goods_receipt'),
            ],
        ]);
    }

    public function pdf(ProductionOrder $order)
    {
        $items = ProductionOrder::query()
            ->where('po_number', $order->po_number)
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('orders.pdf', [
            'order' => $order->load('creator'),
            'items' => $items,
            'totalQuantity' => $items->sum('order_quantity'),
            'totalFabric' => $items->sum(fn (ProductionOrder $item): float => (float) $item->fabric_estimate),
            'totalRib' => $items->sum(fn (ProductionOrder $item): float => (float) $item->rib_qty),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('PO-'.str($order->po_number)->slug('-').'.pdf');
    }

    public function storeAction(Request $request, ProductionOrder $order): RedirectResponse
    {
        $action = $request->validate([
            'action' => ['required', Rule::in(array_keys(self::ACTION_DETAILS))],
        ])['action'];

        abort_unless($request->user()->canPermission($this->permissionForAction($action)), 403);
        abort_if($order->status === 'closed', 409, 'PO sudah ditutup dan tidak dapat diubah.');

        $validated = $this->validateAction($request, $action);

        DB::transaction(function () use ($request, $order, $action, $validated): void {
            $order = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            abort_if($order->status === 'closed', 409, 'PO sudah ditutup dan tidak dapat diubah.');
            $order->load('entries');
            $this->assertActionReady($order, $action, $validated);

            if ($action === 'close_order') {
                $poItems = ProductionOrder::query()
                    ->where('po_number', $order->po_number)
                    ->lockForUpdate()
                    ->get();

                foreach ($poItems as $poItem) {
                    $poItem->load('entries');
                    if (! $this->canCloseOrderItem($poItem)
                        || ($poItem->quantityFor('goods_receipt') < $poItem->order_quantity
                            && blank($validated['notes']))) {
                        throw ValidationException::withMessages([
                            'action' => 'Semua varian harus selesai direkonsiliasi dan catatan selisih wajib diisi sebelum PO ditutup.',
                        ]);
                    }
                }

                foreach ($poItems as $poItem) {
                    $this->record($poItem, $request->user()->id, $action, $this->entryData($poItem, $action, $validated));
                    $poItem->close_note = $validated['notes'];
                    $poItem->closed_at = now();
                    $poItem->status = 'closed';
                    $poItem->save();
                }

                return;
            }

            $entryData = $this->entryData($order, $action, $validated);
            $this->record($order, $request->user()->id, $action, $entryData);

            if ($action === 'receive_fabric') {
                $order->fabric_delivered = $validated['quantity'];
            }

            if ($action === 'issue_spk') {
                $order->spk_quantity = $validated['quantity'];
            }

            if ($action === 'close_order') {
                $order->close_note = $validated['notes'];
                $order->closed_at = now();
            }

            $order->status = $this->nextStatus($action);
            $order->save();
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', self::ACTION_DETAILS[$action][0].' berhasil dicatat.');
    }

    private function validateAction(Request $request, string $action): array
    {
        $rules = match ($action) {
            'request_fabric' => ['notes' => ['nullable', 'string', 'max:1000']],
            'deliver_fabric' => [
                'reference' => ['required', 'string', 'max:80'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'receive_fabric' => [
                'quantity' => ['required', 'numeric', 'gt:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'issue_spk', 'sewing', 'finishing' => [
                'quantity' => ['required', 'integer', 'min:1'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'site_transfer', 'site_receive' => [
                'reference' => ['required', 'string', 'max:80'],
                'quantity' => ['required', 'integer', 'min:1'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'cutting_report' => [
                'quantity' => ['required', 'integer', 'min:1'],
                'fabric_used' => ['required', 'numeric', 'min:0'],
                'yield_category' => ['required', Rule::in(['over_yield', 'waste', 'defective_fabric', 'target'])],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'request_material' => [
                'reference' => ['required', 'string', 'max:120'],
                'quantity' => ['required', 'integer', 'min:1'],
                'unit' => ['required', 'string', 'max:30'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'issue_material' => [
                'reference' => ['required', 'string', 'max:120'],
                'quantity' => ['required', 'integer', 'min:1'],
                'unit' => ['required', 'string', 'max:30'],
                'batch' => ['required', 'string', 'max:80'],
                'received_by' => ['required', 'string', 'max:120'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'vendor_send' => [
                'quantity' => ['required', 'integer', 'min:1'],
                'reference' => ['required', 'string', 'max:80'],
                'vendor' => ['required', 'string', 'max:120'],
                'process' => ['required', Rule::in(['Sablon', 'Bordir'])],
                'material_type' => ['required', Rule::in(['Potongan Bahan', 'Baju Jahit'])],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'vendor_receive' => [
                'reference' => ['required', 'string', 'max:80'],
                'material_type' => ['required', Rule::in(['Potongan Bahan', 'Baju Jahit'])],
                'accepted_quantity' => ['required', 'integer', 'min:0'],
                'rejected_quantity' => ['required', 'integer', 'min:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'site_return' => [
                'reference' => ['required', 'string', 'max:80'],
                'quantity' => ['required', 'integer', 'min:0'],
                'rejected_quantity' => ['required', 'integer', 'min:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'quality_check' => [
                'inspected_quantity' => ['required', 'integer', 'min:1'],
                'passed_quantity' => ['required', 'integer', 'min:0'],
                'rejected_quantity' => ['required', 'integer', 'min:0'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'prepare_shipment' => [
                'quantity' => ['required', 'integer', 'min:1'],
                'reference' => ['required', 'string', 'max:80'],
                'receiver' => ['required', 'string', 'max:120'],
                'driver' => ['nullable', 'string', 'max:120'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'shipment' => [
                'reference' => ['required', 'string', 'max:80'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'goods_receipt' => [
                'reference' => ['required', 'string', 'max:80'],
                'quantity' => ['required', 'integer', 'min:1'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            'close_order' => ['notes' => ['required', 'string', 'min:5', 'max:1000']],
            default => [],
        };

        $validated = $request->validate($rules);

        if (in_array($action, ['vendor_receive', 'site_return'], true)
            && ($validated['accepted_quantity'] ?? $validated['quantity'] ?? 0) + $validated['rejected_quantity'] < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah barang baik dan reject harus lebih dari nol.',
            ]);
        }

        if ($action === 'quality_check'
            && $validated['inspected_quantity'] !== $validated['passed_quantity'] + $validated['rejected_quantity']) {
            throw ValidationException::withMessages([
                'inspected_quantity' => 'Jumlah lolos QC dan reject harus sama dengan jumlah yang diperiksa.',
            ]);
        }

        return $validated;
    }

    private function assertActionReady(ProductionOrder $order, string $action, array $data): void
    {
        $allowed = match ($action) {
            'request_fabric' => $order->status === 'draft',
            'deliver_fabric' => $order->entries->contains('action', 'request_fabric')
                && ! $order->entries->contains('action', 'deliver_fabric'),
            'receive_fabric' => $order->entries->contains('action', 'deliver_fabric')
                && ! $order->entries->contains('action', 'receive_fabric'),
            'issue_spk' => $order->status === 'fabric_received'
                && $data['quantity'] <= $order->order_quantity,
            'cutting_report' => $order->spk_quantity !== null,
            'request_material' => $order->spk_quantity !== null,
            'issue_material' => $data['quantity'] <= $this->pendingMaterialQuantity(
                $order,
                $data['reference'],
                $data['unit'],
            ),
            'vendor_send' => $order->quantityFor('cutting_report') > 0
                && ! $order->entries->where('action', 'vendor_send')->contains('reference', $data['reference'])
                && $data['quantity'] <= ($data['material_type'] === 'Potongan Bahan'
                    ? $this->availableCutAllocation($order)
                    : $order->availableForGarmentVendor()),
            'vendor_receive' => $this->pendingVendorBatch(
                $order,
                $data['reference'],
                $data['material_type'],
            ) > 0
                && $this->pendingVendorBatch($order, $data['reference'], $data['material_type'])
                    >= $data['accepted_quantity'] + $data['rejected_quantity'],
            'site_transfer' => $order->quantityFor('cutting_report') > 0
                && ! $order->entries->where('action', 'site_transfer')->contains('reference', $data['reference'])
                && $data['quantity'] <= $this->availableCutAllocation($order),
            'site_receive' => $data['quantity'] <= $this->pendingSiteBatch($order, $data['reference']),
            'site_return' => $this->pendingSiteReturnBatch($order, $data['reference'])
                >= $data['quantity'] + $data['rejected_quantity'],
            'sewing' => $data['quantity'] <= $order->availableForSewing(),
            'finishing' => $data['quantity'] <= max(
                0,
                $order->finishedQuantity() - $order->quantityFor('finishing'),
            ),
            'quality_check' => $data['inspected_quantity'] <= $order->remainingQcQuantity(),
            'prepare_shipment' => ! $order->entries->where('action', 'prepare_shipment')->contains(
                'reference',
                $data['reference'],
            )
                && $data['quantity'] <= max(
                    0,
                    $order->quantityFor('quality_check')
                        - $order->quantityFor('shipment')
                        - $this->pendingPreparedShipmentQuantity($order),
                ),
            'shipment' => $this->pendingPreparedShipments($order)->contains(
                fn ($entry): bool => $entry->reference === $data['reference'],
            ),
            'goods_receipt' => $data['quantity'] <= $this->pendingShipmentBatch($order, $data['reference']),
            'close_order' => $this->purchaseOrderItems($order)->every(
                fn (ProductionOrder $item): bool => $this->canCloseOrderItem($item)
                    && ($item->quantityFor('goods_receipt') >= $item->order_quantity
                        || filled($data['notes'] ?? null)),
            ),
            default => false,
        };

        if (! $allowed) {
            throw ValidationException::withMessages([
                'action' => 'Tahap ini belum dapat diproses atau jumlah melebihi saldo yang tersedia.',
            ]);
        }

        if ($action === 'receive_fabric'
            && (float) $data['quantity'] !== (float) $order->entries->firstWhere('action', 'deliver_fabric')->quantity
            && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan selisih wajib diisi jika jumlah kain diterima berbeda dari DO.',
            ]);
        }

        if ($action === 'goods_receipt'
            && $data['quantity'] < $this->pendingShipmentBatch($order, $data['reference'])
            && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages([
                'notes' => 'Alasan selisih wajib diisi jika jumlah diterima kurang dari jumlah yang dikirim.',
            ]);
        }

    }

    private function purchaseOrderItems(ProductionOrder $order): Collection
    {
        return ProductionOrder::query()
            ->where('po_number', $order->po_number)
            ->with('entries')
            ->get();
    }

    private function canCloseOrderItem(ProductionOrder $order): bool
    {
        return $order->quantityFor('goods_receipt') > 0
            && $order->pendingShipmentQuantity() === 0
            && $order->pendingPreparedShipmentQuantity() === 0
            && $order->pendingVendorQuantity() === 0
            && $order->pendingSiteQuantity() === 0
            && $order->pendingSiteReturnQuantity() === 0;
    }

    private function availableCutAllocation(ProductionOrder $order): int
    {
        return max(
            0,
            $order->quantityFor('cutting_report')
                - $order->quantityForMaterial('vendor_send', 'Potongan Bahan')
                - $order->quantityFor('site_transfer')
                - $order->quantityFor('sewing'),
        );
    }

    private function pendingMaterialQuantity(ProductionOrder $order, string $item, string $unit): int
    {
        $matches = fn ($entry): bool => $entry->reference === $item
            && data_get($entry->metadata, 'unit') === $unit;

        return max(
            0,
            $order->entries->where('action', 'request_material')->filter($matches)->sum('quantity')
                - $order->entries->where('action', 'issue_material')->filter($matches)->sum('quantity'),
        );
    }

    private function pendingVendorBatch(ProductionOrder $order, string $reference, string $materialType): int
    {
        return max(
            0,
            $this->quantityForReference($order, 'vendor_send', $reference, 'quantity', $materialType)
                - $this->quantityForReference($order, 'vendor_receive', $reference, 'quantity', $materialType)
                - $this->quantityForReference($order, 'vendor_receive', $reference, 'secondary_quantity', $materialType),
        );
    }

    private function pendingSiteBatch(ProductionOrder $order, string $reference): int
    {
        return max(
            0,
            $this->quantityForReference($order, 'site_transfer', $reference)
                - $this->quantityForReference($order, 'site_receive', $reference),
        );
    }

    private function pendingSiteReturnBatch(ProductionOrder $order, string $reference): int
    {
        return max(
            0,
            $this->quantityForReference($order, 'site_receive', $reference)
                - $this->quantityForReference($order, 'site_return', $reference)
                - $this->quantityForReference($order, 'site_return', $reference, 'secondary_quantity'),
        );
    }

    private function pendingShipmentBatch(ProductionOrder $order, string $reference): int
    {
        return max(
            0,
            $this->quantityForReference($order, 'shipment', $reference)
                - $this->quantityForReference($order, 'goods_receipt', $reference),
        );
    }

    private function pendingPreparedShipments(ProductionOrder $order): Collection
    {
        return $order->entries
            ->where('action', 'prepare_shipment')
            ->filter(fn ($entry): bool => ! $order->entries
                ->where('action', 'shipment')
                ->contains('reference', $entry->reference));
    }

    private function pendingPreparedShipmentQuantity(ProductionOrder $order): int
    {
        return (int) $this->pendingPreparedShipments($order)->sum('quantity');
    }

    private function quantityForReference(
        ProductionOrder $order,
        string $action,
        string $reference,
        string $column = 'quantity',
        ?string $materialType = null,
    ): int {
        return $order->entries
            ->where('action', $action)
            ->filter(fn ($entry): bool => $entry->reference === $reference
                && ($materialType === null || data_get($entry->metadata, 'material_type') === $materialType))
            ->sum($column);
    }

    private function entryData(ProductionOrder $order, string $action, array $data): array
    {
        $preparedShipment = $action === 'shipment'
            ? $order->entries->first(fn ($entry): bool => $entry->action === 'prepare_shipment'
                && $entry->reference === $data['reference'])
            : null;

        $quantity = match ($action) {
            'vendor_receive' => $data['accepted_quantity'],
            'quality_check' => $data['passed_quantity'],
            'shipment' => $preparedShipment?->quantity,
            default => $data['quantity'] ?? null,
        };

        $secondary = match ($action) {
            'vendor_receive', 'site_return', 'quality_check' => $data['rejected_quantity'],
            default => null,
        };

        $metadata = match ($action) {
            'vendor_send' => [
                'process' => $data['process'],
                'material_type' => $data['material_type'],
                'vendor' => $data['vendor'],
            ],
            'request_material' => ['unit' => $data['unit']],
            'issue_material' => [
                'unit' => $data['unit'],
                'batch' => $data['batch'],
                'received_by' => $data['received_by'],
            ],
            'cutting_report' => [
                'fabric_used' => $data['fabric_used'],
                'yield_category' => $data['yield_category'],
            ],
            'vendor_receive' => ['material_type' => $data['material_type']],
            'quality_check' => ['inspected_quantity' => $data['inspected_quantity']],
            'prepare_shipment' => [
                'receiver' => $data['receiver'],
                'driver' => $data['driver'] ?? null,
            ],
            'shipment' => $preparedShipment?->metadata,
            default => null,
        };

        return [
            'action' => $action,
            'reference' => $data['reference'] ?? null,
            'quantity' => $quantity,
            'secondary_quantity' => $secondary,
            'metadata' => $metadata,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function record(ProductionOrder $order, int $userId, string $action, array $data): void
    {
        $order->entries()->create(['user_id' => $userId, 'action' => $action, ...$data]);
        $order->unsetRelation('entries');
        $order->load('entries');
    }

    private function nextStatus(string $action): string
    {
        return match ($action) {
            'request_fabric' => 'fabric_requested',
            'deliver_fabric' => 'fabric_in_transit',
            'receive_fabric' => 'fabric_received',
            'issue_spk' => 'spk_issued',
            'cutting_report' => 'cutting_complete',
            'sewing', 'vendor_send', 'vendor_receive', 'site_transfer', 'site_receive', 'site_return', 'finishing' => 'in_production',
            'quality_check' => 'quality_ready',
            'shipment' => 'shipped',
            'prepare_shipment' => 'quality_ready',
            'goods_receipt' => 'delivered',
            'close_order' => 'closed',
            default => 'in_production',
        };
    }

    private function availableActions(ProductionOrder $order): array
    {
        $user = auth()->user();
        $eligible = collect(array_keys(self::ACTION_DETAILS))
            ->filter(fn (string $action): bool => $user->canPermission($this->permissionForAction($action)))
            ->all();

        $checks = [
            'request_fabric' => $order->status === 'draft',
            'deliver_fabric' => $order->entries->contains('action', 'request_fabric')
                && ! $order->entries->contains('action', 'deliver_fabric'),
            'receive_fabric' => $order->entries->contains('action', 'deliver_fabric')
                && ! $order->entries->contains('action', 'receive_fabric'),
            'issue_spk' => $order->status === 'fabric_received',
            'request_material' => $order->spk_quantity !== null,
            'issue_material' => $order->entries->where('action', 'request_material')->contains(
                fn ($entry): bool => $this->pendingMaterialQuantity(
                    $order,
                    $entry->reference,
                    (string) data_get($entry->metadata, 'unit'),
                ) > 0,
            ),
            'cutting_report' => $order->spk_quantity !== null,
            'vendor_send' => $this->availableCutAllocation($order) > 0 || $order->availableForGarmentVendor() > 0,
            'vendor_receive' => $order->pendingVendorQuantity() > 0,
            'site_transfer' => $this->availableCutAllocation($order) > 0,
            'site_receive' => $order->pendingSiteQuantity() > 0,
            'site_return' => $order->pendingSiteReturnQuantity() > 0,
            'sewing' => $order->availableForSewing() > 0,
            'finishing' => $order->finishedQuantity() > $order->quantityFor('finishing'),
            'quality_check' => $order->remainingQcQuantity() > 0,
            'shipment' => $this->pendingPreparedShipments($order)->isNotEmpty(),
            'prepare_shipment' => $order->quantityFor('quality_check')
                > $order->quantityFor('shipment') + $this->pendingPreparedShipmentQuantity($order),
            'goods_receipt' => $order->pendingShipmentQuantity() > 0,
            'close_order' => $this->purchaseOrderItems($order)->every(
                fn (ProductionOrder $item): bool => $this->canCloseOrderItem($item),
            ),
        ];

        if ($order->status === 'closed') {
            return [];
        }

        return collect($eligible)
            ->filter(fn (string $action): bool => $checks[$action] ?? false)
            ->map(fn (string $action): array => [
                'key' => $action,
                'label' => self::ACTION_DETAILS[$action][0],
                'description' => self::ACTION_DETAILS[$action][1],
            ])
            ->values()
            ->all();
    }

    private function permissionForAction(string $action): string
    {
        return match ($action) {
            'request_fabric' => 'orders.request_fabric',
            'deliver_fabric', 'receive_fabric', 'issue_material' => 'fabric.manage',
            'issue_spk' => 'spk.issue',
            'cutting_report' => 'cutting',
            'site_receive' => 'production.cibentang',
            'goods_receipt', 'close_order' => 'orders.close_receive',
            default => 'production.gunung_karang',
        };
    }
}
