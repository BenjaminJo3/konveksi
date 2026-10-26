<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuttingController extends Controller
{
    public function index(Request $request): View
    {
        $orders = ProductionOrder::query()
            ->with(['branch', 'entries'])
            ->whereNotNull('spk_quantity')
            ->where('status', '!=', 'closed')
            ->when($request->filled('q'), fn ($query) => $query->where(
                fn ($search) => $search
                    ->where('po_number', 'like', '%'.$request->string('q')->trim().'%')
                    ->orWhere('product_name', 'like', '%'.$request->string('q')->trim().'%')
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('cutting.index', [
            'orders' => $orders,
            'search' => $request->string('q')->toString(),
        ]);
    }
}
