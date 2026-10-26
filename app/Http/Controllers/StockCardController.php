<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockCardController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $products = DB::table('master_products as products')
            ->join('master_units as units', 'units.id', '=', 'products.unit_id')
            ->leftJoinSub(
                DB::table('stock_movements')
                    ->select('master_product_id')
                    ->selectRaw("SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END) as balance")
                    ->groupBy('master_product_id'),
                'balances',
                'balances.master_product_id',
                '=',
                'products.id',
            )
            ->where('products.is_active', true)
            ->when($search !== '', fn ($query) => $query->where(
                fn ($builder) => $builder
                    ->where('products.code', 'like', '%'.$search.'%')
                    ->orWhere('products.name', 'like', '%'.$search.'%')
            ))
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'units.symbol as unit_symbol',
                DB::raw('COALESCE(balances.balance, 0) as stock_balance'),
            ])
            ->orderBy('products.name')
            ->paginate(24)
            ->withQueryString();

        $selectedProductId = $request->integer('product_id');
        $selectedProduct = null;
        $movements = collect();
        $balance = 0;
        if ($selectedProductId > 0) {
            $selectedProduct = DB::table('master_products as products')
                ->join('master_units as units', 'units.id', '=', 'products.unit_id')
                ->where('products.id', $selectedProductId)
                ->select('products.id', 'products.code', 'products.name', 'units.symbol as unit_symbol')
                ->first();
            abort_if($selectedProduct === null, 404);

            $movements = StockMovement::query()
                ->with('user:id,name')
                ->where('master_product_id', $selectedProductId)
                ->latest()
                ->paginate(20, ['*'], 'movement_page')
                ->withQueryString();
            $balance = (float) DB::table('stock_movements')
                ->where('master_product_id', $selectedProductId)
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END), 0) as balance")
                ->value('balance');
            $firstMovement = $movements->first();
            if ($firstMovement !== null) {
                $newerBalance = (float) DB::table('stock_movements')
                    ->where('master_product_id', $selectedProductId)
                    ->where(fn ($query) => $query
                        ->where('created_at', '>', $firstMovement->created_at)
                        ->orWhere(fn ($sameTime) => $sameTime
                            ->where('created_at', $firstMovement->created_at)
                            ->where('id', '>', $firstMovement->id)))
                    ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END), 0) as balance")
                    ->value('balance');
                $runningBalance = $balance - $newerBalance;

                foreach ($movements as $movement) {
                    $movement->balance_after = $runningBalance;
                    $runningBalance -= $movement->movement_type === 'in'
                        ? (float) $movement->quantity
                        : -((float) $movement->quantity);
                }
            }
        }

        return view('stock-cards.index', [
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'movements' => $movements,
            'balance' => $balance,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'master_product_id' => ['required', 'integer', 'exists:master_products,id'],
            'movement_type' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $values): void {
            DB::table('master_products')
                ->where('id', $values['master_product_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $balance = (float) DB::table('stock_movements')
                ->where('master_product_id', $values['master_product_id'])
                ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END), 0) as balance")
                ->value('balance');

            if ($values['movement_type'] === 'out' && (float) $values['quantity'] > $balance) {
                abort(422, 'Stok tidak mencukupi. Saldo saat ini '.number_format($balance, 3, ',', '.').'.');
            }

            $movement = StockMovement::query()->create([
                ...$values,
                'user_id' => $request->user()->id,
            ]);
            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'event' => 'stock_'.$values['movement_type'],
                'subject_type' => 'stock_movement',
                'subject_id' => (string) $movement->id,
                'description' => 'Mutasi stok '.$values['movement_type'].' dicatat untuk barang #'.$values['master_product_id'].'.',
                'metadata' => [
                    'master_product_id' => $values['master_product_id'],
                    'quantity' => $values['quantity'],
                    'reference' => $values['reference'] ?? null,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('stock-cards.index', ['product_id' => $values['master_product_id']])
            ->with('success', 'Mutasi stok berhasil dicatat.');
    }
}
