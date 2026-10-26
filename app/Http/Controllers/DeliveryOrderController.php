<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderGroup;
use App\Models\ProductionOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    private const LOCATIONS = ['mydent', 'gunung_karang', 'cibentang'];

    private const SOURCES = ['mydent', 'warehouse', 'gunung_karang', 'cibentang'];

    private const GOODS_TYPES = [
        'fabric' => ['Kain', 'yard/kg'],
        'accessories' => ['Aksesoris', 'pcs'],
        'cut_pieces' => ['Potongan bahan', 'pcs'],
        'finished_goods' => ['Baju jadi', 'pcs'],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = DeliveryOrderGroup::query()
            ->with(['creator', 'items'])
            ->latest();

        if (! in_array($user->role, ['admin', 'super_admin'], true)) {
            $query->where(fn ($builder) => $builder
                ->where('source_role', $user->role)
                ->orWhere('destination_role', $user->role));
        }

        return view('delivery-orders.index', [
            'deliveryOrders' => $query->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $sourceRole = in_array($user->role, ['admin', 'super_admin'], true) ? null : $user->role;
        $sourceRoles = in_array($user->role, ['admin', 'super_admin'], true)
            ? self::SOURCES
            : [$sourceRole];

        return view('delivery-orders.create', [
            'sourceRole' => $sourceRole,
            'sourceRoles' => $sourceRoles,
            'locations' => self::LOCATIONS,
            'goodsTypes' => self::GOODS_TYPES,
            'purchaseOrders' => ProductionOrder::query()
                ->where('status', '!=', 'closed')
                ->select('po_number')
                ->distinct()
                ->orderByDesc('po_number')
                ->pluck('po_number'),
        ]);
    }

    public function purchaseOrderItems(string $poNumber): JsonResponse
    {
        $items = ProductionOrder::query()
            ->where('po_number', $poNumber)
            ->where('status', '!=', 'closed')
            ->orderBy('id')
            ->get([
                'id',
                'category',
                'sub_category',
                'product_name',
                'model',
                'size',
                'color',
                'fabric_name',
                'order_quantity',
            ]);

        abort_if($items->isEmpty(), 404);

        return response()->json([
            'po_number' => $poNumber,
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isAdmin = in_array($request->user()->role, ['admin', 'super_admin'], true);
        $values = $request->validate([
            'source_role' => [$isAdmin ? 'required' : 'nullable', Rule::in(self::SOURCES)],
            'destination_role' => ['required', Rule::in(self::LOCATIONS)],
            'po_number' => ['required', 'string', 'max:60'],
            'goods_type' => ['required', Rule::in(array_keys(self::GOODS_TYPES))],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.production_order_id' => ['required', 'integer', 'distinct', 'exists:production_orders,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'dispatched_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $sourceRole = $isAdmin ? $values['source_role'] : $request->user()->role;
        if ($sourceRole === $values['destination_role']) {
            throw ValidationException::withMessages([
                'destination_role' => 'Lokasi tujuan harus berbeda dari lokasi pengirim.',
            ]);
        }

        $selectedIds = collect($values['items'])
            ->filter(fn (array $item): bool => (float) ($item['quantity'] ?? 0) > 0)
            ->pluck('production_order_id');
        if ($selectedIds->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Isi QTY lebih dari nol pada minimal satu item yang akan dikirim.',
            ]);
        }
        $productionOrders = ProductionOrder::query()
            ->whereIn('id', $selectedIds)
            ->where('po_number', $values['po_number'])
            ->where('status', '!=', 'closed')
            ->get()
            ->keyBy('id');
        if ($productionOrders->count() !== $selectedIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Item harus berasal dari PO yang dipilih dan belum ditutup.',
            ]);
        }

        $deliveryOrderGroup = DB::transaction(function () use ($request, $values, $sourceRole, $productionOrders): DeliveryOrderGroup {
            $unit = self::GOODS_TYPES[$values['goods_type']][1];
            $group = DeliveryOrderGroup::query()->create([
                'number' => 'DO-PENDING-'.Str::uuid(),
                'po_number' => $values['po_number'],
                'source_role' => $sourceRole,
                'destination_role' => $values['destination_role'],
                'goods_type' => $values['goods_type'],
                'unit' => $unit,
                'dispatched_at' => $values['dispatched_at'],
                'notes' => $values['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            $group->update([
                'number' => sprintf('DO-%s-%05d', now()->format('Y'), $group->id),
            ]);

            foreach ($values['items'] as $index => $item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }
                $productionOrder = $productionOrders->get((int) $item['production_order_id']);
                $line = $group->items()->create([
                    'number' => sprintf('%s-%03d', $group->number, $index + 1),
                    'production_order_id' => $productionOrder->id,
                    'source_role' => $sourceRole,
                    'destination_role' => $values['destination_role'],
                    'goods_type' => $values['goods_type'],
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'dispatched_at' => $values['dispatched_at'],
                    'notes' => $values['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                $productionOrder->entries()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'delivery_order_created',
                    'reference' => $group->number,
                    'quantity' => $quantity,
                    'metadata' => [
                        'delivery_order_group_id' => $group->id,
                        'destination_role' => $values['destination_role'],
                        'goods_type' => $values['goods_type'],
                        'unit' => $unit,
                    ],
                    'notes' => $values['notes'] ?? null,
                ]);
            }

            return $group;
        });

        return redirect()
            ->route('delivery-orders.index')
            ->with('success', "DO {$deliveryOrderGroup->number} berhasil diterbitkan.");
    }

    public function receipts(Request $request): View
    {
        $user = $request->user();
        $locations = [
            'mydent' => 'Mydent (Grosir)',
            'gunung_karang' => 'Konveksi Gunung Karang',
            'cibentang' => 'Konveksi Cibentang',
        ];
        $canReceive = in_array($user->role, ['admin', 'super_admin'], true) || in_array($user->role, self::LOCATIONS, true);
        $selectedLocation = in_array($user->role, ['admin', 'super_admin'], true)
            ? $request->query('location', 'mydent')
            : (in_array($user->role, self::LOCATIONS, true) ? $user->role : '');
        abort_unless($selectedLocation === '' || array_key_exists($selectedLocation, $locations), 422);
        $query = DeliveryOrder::query()
            ->with(['order', 'creator', 'receipts.receiver', 'group'])
            ->latest('dispatched_at');

        if ($selectedLocation !== '') {
            $query->where('destination_role', $selectedLocation);
        }

        return view('goods-receipts.index', [
            'deliveryOrders' => $query->paginate(15),
            'canReceive' => $canReceive,
            'locations' => $locations,
            'selectedLocation' => $selectedLocation,
            'canSelectLocation' => in_array($user->role, ['admin', 'super_admin'], true),
        ]);
    }

    public function storeReceipt(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'delivery_order_id' => ['required', 'integer', 'exists:delivery_orders,id'],
            'destination_role' => ['nullable', Rule::in(self::LOCATIONS)],
            'quantity_received' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'discrepancy_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryOrder = DB::transaction(function () use ($request, $values): DeliveryOrder {
            $deliveryOrder = DeliveryOrder::query()
                ->with('receipts')
                ->lockForUpdate()
                ->findOrFail($values['delivery_order_id']);

            $selectedLocation = $values['destination_role'] ?? $request->user()->role;
            if (in_array($request->user()->role, ['admin', 'super_admin'], true) && ! isset($values['destination_role'])) {
                $selectedLocation = $deliveryOrder->destination_role;
            }
            abort_unless($selectedLocation === $deliveryOrder->destination_role, 403);
            abort_unless(in_array($request->user()->role, ['admin', 'super_admin'], true) || $selectedLocation === $request->user()->role, 403);

            $pending = $deliveryOrder->pendingQuantity();
            if ((float) $values['quantity_received'] > $pending) {
                throw ValidationException::withMessages([
                    'quantity_received' => "Jumlah penerimaan melebihi sisa DO ({$pending} {$deliveryOrder->unit}).",
                ]);
            }

            if ((float) $values['quantity_received'] !== $pending && blank($values['discrepancy_note'] ?? null)) {
                throw ValidationException::withMessages([
                    'discrepancy_note' => 'Catatan wajib diisi jika jumlah penerimaan berbeda dari sisa DO.',
                ]);
            }

            $receipt = $deliveryOrder->receipts()->create([
                'quantity_received' => $values['quantity_received'],
                'discrepancy_note' => $values['discrepancy_note'] ?? null,
                'received_by' => $request->user()->id,
                'received_at' => now(),
            ]);

            $deliveryOrder->order->entries()->create([
                'user_id' => $request->user()->id,
                'action' => 'goods_receipt_do',
                'reference' => $deliveryOrder->group?->number ?? $deliveryOrder->number,
                'quantity' => $receipt->quantity_received,
                'metadata' => [
                    'destination_role' => $deliveryOrder->destination_role,
                    'receipt_location' => $selectedLocation,
                    'delivery_order_id' => $deliveryOrder->id,
                ],
                'notes' => $receipt->discrepancy_note,
            ]);

            return $deliveryOrder;
        });

        return redirect()
            ->route('goods-receipts.index', ['location' => $deliveryOrder->destination_role])
            ->with('success', 'Penerimaan untuk DO '.($deliveryOrder->group?->number ?? $deliveryOrder->number).' berhasil dicatat.');
    }
}
