@props(['order'])
@php
    $styles = [
        'draft' => 'bg-slate-100 text-slate-600',
        'fabric_requested' => 'bg-blue-50 text-blue-700',
        'fabric_in_transit' => 'bg-sky-50 text-sky-700',
        'fabric_received' => 'bg-cyan-50 text-cyan-700',
        'spk_issued' => 'bg-amber-50 text-amber-700',
        'cutting_complete' => 'bg-orange-50 text-orange-700',
        'in_production' => 'bg-violet-50 text-violet-700',
        'quality_ready' => 'bg-emerald-50 text-emerald-700',
        'shipped' => 'bg-indigo-50 text-indigo-700',
        'delivered' => 'bg-teal-50 text-teal-700',
        'closed' => 'bg-slate-800 text-white',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ' . ($styles[$order->status] ?? 'bg-slate-100 text-slate-600')]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $order->statusLabel() }}
</span>
