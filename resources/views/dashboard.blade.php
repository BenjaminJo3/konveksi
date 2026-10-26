@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    <div class="mb-7">
        <div>
            <p class="text-sm font-medium text-slate-500">Ringkasan berdasarkan tanggal PO: {{ $periodStart->format('d M Y') }}{{ $periodEnd->toDateString() !== $periodStart->toDateString() ? ' – '.$periodEnd->format('d M Y') : '' }}.</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Selamat bekerja, {{ explode(' ', auth()->user()->name)[0] }} <span class="text-emerald-600">👋</span></h2>
        </div>
        <a href="{{ route('orders.kanban') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 sm:mt-0">
            <span aria-hidden="true">▥</span> Papan Kanban Operasional
        </a>
    </div>

    <nav aria-label="Periode dashboard" class="mb-5 flex w-fit max-w-full gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="tablist">
        @foreach (['day' => 'Per Hari', 'month' => 'Per Bulan', 'year' => 'Per Tahun'] as $key => $label)
            <a href="{{ route('dashboard', ['period' => $key]) }}" role="tab" aria-selected="{{ $period === $key ? 'true' : 'false' }}" @if ($period === $key) aria-current="page" @endif class="whitespace-nowrap rounded-lg px-4 py-2 text-xs font-semibold transition {{ $period === $key ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <section aria-label="Statistik Purchase Order" class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['Total PO', $totalOrders, 'Semua pesanan', '▤', 'bg-blue-50 text-blue-600', ''],
            ['Baru', $newOrders, 'Menunggu proses', '+', 'bg-sky-50 text-sky-600', 'draft'],
            ['Proses', $processingOrders, 'Produksi berjalan', '⚙', 'bg-indigo-50 text-indigo-600', 'processing'],
            ['Menunggu', $waitingOrders, 'QC / pengiriman', '◷', 'bg-amber-50 text-amber-600', 'waiting'],
            ['Selesai', $completedOrders, 'Diterima Mydent', '✓', 'bg-emerald-50 text-emerald-600', 'completed'],
            ['Ditutup', $closedOrders, 'Close PO', '▣', 'bg-slate-100 text-slate-500', 'closed'],
        ] as [$label, $value, $description, $icon, $iconClass, $status])
            <a href="{{ route('orders.index', array_filter(['status' => $status])) }}" aria-label="Lihat daftar PO: {{ $label }} ({{ $value }})" class="group block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
            <article data-stat="{{ \Illuminate\Support\Str::slug($label) }}" data-stat-count="{{ $value }}" class="h-full rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition group-hover:-translate-y-0.5 group-hover:border-emerald-300 group-hover:shadow-md sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ $iconClass }} text-sm font-bold">{{ $icon }}</span>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($value) }}</p>
                <div class="mt-2 flex items-center justify-between gap-1">
                    <p class="truncate text-[10px] text-slate-500">{{ $description }}</p>
                    <span class="text-xs text-slate-400" aria-hidden="true">→</span>
                </div>
            </article>
            </a>
        @endforeach
    </section>

    @php
        $ratioChartMax = max(1, $ratioCuttingVariance->flatMap(fn (array $row): array => [$row['target'], $row['cutting']])->max());
        $productionVendorChartMax = max(1, $productionVendorComparison->flatMap(fn (array $row): array => [$row['sewing'], $row['vendor_output']])->max());
        $workloadTotal = $locationWorkload->sum('value');
        $workloadCircumference = 276.46;
        $workloadOffset = 0;
    @endphp
    <section aria-label="Dashboard Analitik Operasional" class="mb-7 grid min-w-0 gap-4 xl:grid-cols-3">
        <article class="min-w-0 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-2 flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-sm text-blue-600">±</span>
                <div><h3 class="text-sm font-bold text-slate-900">PO Vs H. Cutting</h3><p class="mt-0.5 text-[10px] text-slate-400">Target rasio PO dibanding hasil cutting (pcs)</p></div>
            </div>
            <div class="mb-1 flex justify-center gap-4 text-[9px]">
                <span class="flex items-center gap-1.5 text-slate-500"><span class="h-2 w-2 rounded-sm bg-slate-300"></span>Target rasio PO</span>
                <span class="flex items-center gap-1.5 text-slate-500"><span class="h-2 w-2 rounded-sm bg-blue-600"></span>Hasil cutting</span>
            </div>
            <div class="overflow-x-auto">
                <svg id="ratio-cutting-chart" role="img" aria-label="Perbandingan target Purchase Order dan hasil cutting" viewBox="0 0 420 270" class="h-[250px] min-w-[360px] w-full">
                    @foreach ([0, 1, 2, 3, 4] as $line)
                        @php($y = 30 + ($line * 44))
                        <line x1="46" y1="{{ $y }}" x2="408" y2="{{ $y }}" stroke="#e5e7eb"/>
                        <text x="38" y="{{ $y + 4 }}" text-anchor="end" fill="#94a3b8" font-size="9">{{ number_format($ratioChartMax * (4 - $line) / 4, 0) }}</text>
                    @endforeach
                    @forelse ($ratioCuttingVariance as $index => $row)
                        @php($targetHeight = $row['target'] > 0 ? max(4, (int) round($row['target'] / $ratioChartMax * 162)) : 3)
                        @php($cuttingHeight = $row['cutting'] > 0 ? max(4, (int) round($row['cutting'] / $ratioChartMax * 162)) : 3)
                        @php($x = 57 + ($index * 58))
                        <g style="--chart-bar-delay: {{ $index * 45 }}ms"><title>{{ $row['label'] }} · target {{ number_format($row['target']) }} pcs · cutting {{ number_format($row['cutting']) }} pcs · selisih {{ number_format($row['ratio_variance']) }} pcs</title>
                            <rect class="dashboard-chart-bar" x="{{ $x }}" y="{{ 210 - $targetHeight }}" width="20" height="{{ $targetHeight }}" rx="5" fill="#cbd5e1"/>
                            <rect class="dashboard-chart-bar" x="{{ $x + 23 }}" y="{{ 210 - $cuttingHeight }}" width="20" height="{{ $cuttingHeight }}" rx="5" fill="#0866c6"/>
                        </g>
                        <text x="{{ $x + 21 }}" y="230" text-anchor="middle" fill="#64748b" font-size="8" font-weight="600">PO {{ $index + 1 }}</text>
                        <text x="{{ $x + 21 }}" y="246" text-anchor="middle" fill="{{ abs($row['ratio_variance']) > 0.01 ? '#dc2626' : '#16a34a' }}" font-size="8" font-weight="700">{{ $row['ratio_variance'] > 0 ? '+' : '' }}{{ number_format($row['ratio_variance'], 0) }}</text>
                    @empty
                        <text x="210" y="130" text-anchor="middle" fill="#94a3b8" font-size="11">Belum ada data PO untuk dibandingkan</text>
                    @endforelse
                </svg>
            </div>
        </article>

        <article class="min-w-0 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-2 flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-sm text-indigo-600">⇄</span>
                <div><h3 class="text-sm font-bold text-slate-900">Konveksi Vs Sablon dan Bordir</h3><p class="mt-0.5 text-[10px] text-slate-400">Output jahit konveksi dibanding hasil baik vendor (pcs)</p></div>
            </div>
            <div class="mb-1 flex justify-center gap-4 text-[9px]">
                <span class="flex items-center gap-1.5 text-slate-500"><span class="h-2 w-2 rounded-sm bg-sky-500"></span>Output jahit konveksi</span>
                <span class="flex items-center gap-1.5 text-slate-500"><span class="h-2 w-2 rounded-sm bg-violet-600"></span>Hasil vendor sablon/bordir</span>
            </div>
            <div class="overflow-x-auto">
                <svg id="production-vendor-chart" role="img" aria-label="Perbandingan output jahit konveksi dengan hasil vendor sablon dan bordir" viewBox="0 0 420 270" class="h-[250px] min-w-[360px] w-full">
                    @foreach ([0, 1, 2, 3, 4] as $line)
                        @php($y = 30 + ($line * 44))
                        <line x1="46" y1="{{ $y }}" x2="408" y2="{{ $y }}" stroke="#e5e7eb"/>
                        <text x="38" y="{{ $y + 4 }}" text-anchor="end" fill="#94a3b8" font-size="9">{{ number_format($productionVendorChartMax * (4 - $line) / 4, 0) }}</text>
                    @endforeach
                    @forelse ($productionVendorComparison as $index => $row)
                        @php($sewingHeight = $row['sewing'] > 0 ? max(4, (int) round($row['sewing'] / $productionVendorChartMax * 162)) : 3)
                        @php($vendorHeight = $row['vendor_output'] > 0 ? max(4, (int) round($row['vendor_output'] / $productionVendorChartMax * 162)) : 3)
                        @php($x = 57 + ($index * 58))
                        <g style="--chart-bar-delay: {{ $index * 45 }}ms"><title>{{ $row['label'] }} · output konveksi {{ number_format($row['sewing']) }} pcs · hasil baik vendor sablon/bordir {{ number_format($row['vendor_output']) }} pcs</title>
                            <rect class="dashboard-chart-bar" x="{{ $x }}" y="{{ 210 - $sewingHeight }}" width="20" height="{{ $sewingHeight }}" rx="5" fill="#2d95f5"/>
                            <rect class="dashboard-chart-bar" x="{{ $x + 23 }}" y="{{ 210 - $vendorHeight }}" width="20" height="{{ $vendorHeight }}" rx="5" fill="#655ce8"/>
                        </g>
                        <text x="{{ $x + 21 }}" y="230" text-anchor="middle" fill="#64748b" font-size="8" font-weight="600">PO {{ $index + 1 }}</text>
                    @empty
                        <text x="210" y="130" text-anchor="middle" fill="#94a3b8" font-size="11">Belum ada data jahit atau hasil vendor</text>
                    @endforelse
                </svg>
            </div>
        </article>

        <article class="min-w-0 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-2 flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-sm text-emerald-600">♧</span>
                <div><h3 class="text-sm font-bold text-slate-900">PO Gantung (belum closing per Lokasi)</h3><p class="mt-0.5 text-[10px] text-slate-400">Jumlah PO belum ditutup menurut PIC saat ini</p></div>
            </div>
            <div class="mt-4 grid items-center gap-4 sm:grid-cols-[minmax(130px,0.8fr)_minmax(0,1fr)]">
                <div class="relative mx-auto h-40 w-40" role="img" aria-label="Diagram donat PO gantung per lokasi, total {{ $workloadTotal }} PO">
                    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" data-dashboard-donut role="presentation">
                        <circle cx="60" cy="60" r="44" fill="none" stroke="#f1f5f9" stroke-width="28"/>
                        @foreach ($locationWorkload as $location)
                            @php($segmentLength = $workloadTotal > 0 ? $location['value'] / $workloadTotal * $workloadCircumference : 0)
                            @if ($segmentLength > 0)
                                <circle class="dashboard-donut-segment" cx="60" cy="60" r="44" fill="none" stroke="{{ $location['color'] }}" stroke-width="28" stroke-dasharray="{{ $segmentLength }} {{ $workloadCircumference - $segmentLength }}" stroke-dashoffset="{{ -$workloadOffset }}" style="--donut-circumference: {{ $workloadCircumference }}; --donut-segment-length: {{ $segmentLength }}; --donut-segment-gap: {{ $workloadCircumference - $segmentLength }}; --donut-segment-delay: {{ $workloadOffset / $workloadCircumference * 1.25 }}s; --donut-segment-duration: {{ $segmentLength / $workloadCircumference * 1.25 }}s" />
                            @endif
                            @php($workloadOffset += $segmentLength)
                        @endforeach
                    </svg>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">{{ $workloadTotal }}</span>
                        <span class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">PO gantung</span>
                    </div>
                </div>
                <div class="space-y-3">
                @foreach ($locationWorkload as $location)
                    <div class="flex items-center justify-between gap-2" title="{{ $location['label'] }}: {{ $location['value'] }} PO belum closing">
                        <span class="flex min-w-0 items-center gap-2 truncate text-[10px] font-medium text-slate-600"><span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $location['color'] }}"></span>{{ $location['label'] }}</span>
                        <span class="text-right text-xs font-bold text-slate-700">{{ $location['value'] }} <span class="text-[9px] font-normal text-slate-400">PO</span></span>
                    </div>
                @endforeach
                </div>
            </div>
        </article>
    </section>

    <div class="grid min-w-0 gap-6 xl:grid-cols-[1.55fr_0.85fr]">
        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                <div><h3 class="text-sm font-bold text-slate-900">Pesanan berjalan</h3><p class="mt-0.5 text-xs text-slate-400">Status terkini Purchase Order</p></div>
                <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">Lihat semua <span aria-hidden="true">→</span></a>
            </div>
            @if ($orders->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-500">▤</span>
                    <h4 class="mt-4 text-sm font-semibold text-slate-800">Belum ada Purchase Order</h4>
                    <p class="mt-1 text-xs text-slate-400">Buat pesanan pertama untuk memulai alur produksi.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-left">
                        <thead><tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-bold uppercase tracking-wider text-slate-400"><th class="px-6 py-3">Pesanan</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Progress</th><th class="px-4 py-3">Status</th><th class="px-6 py-3"></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($orders as $order)
                                @php($cut = $order->quantityFor('cutting_report'))
                                @php($progress = $order->order_quantity > 0 ? min(100, (int) round($order->quantityFor('goods_receipt') / $order->order_quantity * 100)) : 0)
                                <tr class="transition hover:bg-slate-50/60">
                                    <td class="px-6 py-4"><a href="{{ route('orders.show', $order) }}" class="text-xs font-bold text-slate-800 hover:text-emerald-700">{{ $order->po_number }}</a><p class="mt-1 max-w-[190px] truncate text-[11px] text-slate-400">{{ $order->product_name }}{{ $order->color ? ' · '.$order->color : '' }}</p></td>
                                    <td class="px-4 py-4 text-xs font-semibold text-slate-700">{{ number_format($order->order_quantity) }} <span class="font-normal text-slate-400">pcs</span></td>
                                    <td class="px-4 py-4"><div class="flex items-center gap-2"><div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div></div><span class="text-[10px] text-slate-500">{{ $progress }}%</span></div><p class="mt-1 text-[10px] text-slate-400">{{ number_format($cut) }} pcs cutting</p></td>
                                    <td class="px-4 py-4"><x-order-status :order="$order" /></td>
                                    <td class="px-6 py-4 text-right"><a href="{{ route('orders.show', $order) }}" class="text-sm text-slate-400 hover:text-emerald-700" aria-label="Buka detail {{ $order->po_number }}">→</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200/80 bg-white">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-bold text-slate-900">Aktivitas terbaru</h3><p class="mt-0.5 text-xs text-slate-400">Jejak audit transaksi produksi</p></div>
            @if ($recentEntries->isEmpty())
                <div class="px-5 py-12 text-center text-xs text-slate-400">Aktivitas akan muncul setelah ada transaksi.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recentEntries as $item)
                        <a href="{{ route('orders.show', $item['order']) }}" class="flex gap-3 px-5 py-4 transition hover:bg-slate-50">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-700">✓</span>
                            <span class="min-w-0 flex-1"><span class="block truncate text-xs font-semibold text-slate-800">{{ str($item['entry']->action)->replace('_', ' ')->title() }}</span><span class="mt-1 block truncate text-[11px] text-slate-400">{{ $item['order']->po_number }} · {{ $item['entry']->user?->name ?? 'Sistem' }}</span></span>
                            <span class="shrink-0 text-[10px] text-slate-400">{{ $item['entry']->created_at->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="mx-5 mb-5 rounded-xl bg-[#f3f7f4] p-4">
                <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span><p class="text-xs font-bold text-slate-700">Kontrol serah-terima aktif</p></div>
                <p class="mt-1.5 text-[11px] leading-5 text-slate-500">Setiap transaksi menyimpan pengguna, waktu, jumlah, referensi dokumen, dan catatan selisih.</p>
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6">
        <div class="mb-5 flex items-center justify-between">
            <div><h3 class="text-sm font-bold text-slate-900">Alur kerja produksi</h3><p class="mt-0.5 text-xs text-slate-400">Toko Mydent → Gunung Karang → vendor / Cibentang → Mydent</p></div>
            <span class="hidden rounded-full bg-slate-50 px-3 py-1 text-[10px] font-semibold text-slate-500 sm:inline-flex">14 tahapan kontrol</span>
        </div>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['01', 'Pesanan & kain', 'PO · DO kain · penerimaan'], ['02', 'Persiapan produksi', 'SPK · material · serah-terima'], ['03', 'Cutting & makloon', 'Gunung Karang · vendor · Cibentang'], ['04', 'QC & rekonsiliasi', 'Finishing · pengiriman · Close PO']] as [$number, $title, $description])
                <div class="flex items-start gap-3 rounded-xl border border-slate-100 p-3.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-[10px] font-bold text-emerald-700">{{ $number }}</span>
                    <span><span class="block text-xs font-bold text-slate-800">{{ $title }}</span><span class="mt-1 block text-[10px] leading-4 text-slate-400">{{ $description }}</span></span>
                </div>
            @endforeach
        </div>
    </section>
@endsection
