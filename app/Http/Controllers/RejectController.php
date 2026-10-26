<?php

namespace App\Http\Controllers;

use App\Models\WorkflowEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RejectController extends Controller
{
    private const REJECT_ACTIONS = ['vendor_receive', 'site_return', 'quality_check'];

    public function __invoke(Request $request): View
    {
        $query = WorkflowEntry::query()
            ->with(['order.creator', 'user'])
            ->whereIn('action', self::REJECT_ACTIONS)
            ->where('secondary_quantity', '>', 0)
            ->latest();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($orders) => $orders
                        ->where('po_number', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%"));
            });
        }

        $rejects = $query->paginate(20)->withQueryString();

        return view('rejects.index', [
            'rejects' => $rejects,
            'search' => $request->string('q')->toString(),
            'totalQuantity' => (clone $query)->sum('secondary_quantity'),
        ]);
    }
}
