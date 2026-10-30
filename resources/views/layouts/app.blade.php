<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Konveksi Gallery Anak Rantau</title>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div id="workspace" class="min-h-screen w-full min-w-0 lg:flex">
        <button id="sidebar-backdrop" type="button" aria-label="Tutup navigasi" class="sidebar-backdrop fixed inset-0 z-30 bg-slate-950/40 lg:hidden"></button>
        <aside id="app-sidebar" class="workspace-sidebar fixed inset-y-0 left-0 z-40 flex w-[min(18rem,calc(100vw-2.5rem))] shrink-0 flex-col overflow-y-auto border-r border-slate-200/80 bg-white lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-64">
            <a href="{{ route('dashboard') }}" class="flex h-[76px] items-center gap-3 border-b border-slate-100 px-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-700 text-xs font-bold tracking-[0.08em] text-white">GAR</span>
                <span class="min-w-0">
                    <span class="brand-wordmark block text-[14px] font-semibold leading-tight text-slate-900">Konveksi</span>
                    <span class="brand-wordmark block text-[12px] font-medium leading-tight text-slate-700">Gallery Anak Rantau</span>
                </span>
            </a>
            <div class="px-3 pt-5">
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Umum</p>
                <nav class="space-y-0.5 text-[13px] font-semibold tracking-[0.005em]">
                    @if (auth()->user()->canPermission('dashboard.view'))
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">⌂</span>
                        Dashboard
                    </a>
                    @endif
                    @if (auth()->user()->canPermission('orders.view'))
                        <a href="{{ route('orders.kanban') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('orders.kanban') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('orders.kanban') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">▥</span>
                            Papan Kanban
                        </a>
                    @endif
                    <p class="mb-1 mt-4 px-3 pt-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Pengadaan & Gudang</p>
                    @if (auth()->user()->canPermission('orders.view'))
                    <a href="{{ route('orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('orders.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('orders.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">▤</span>
                        Purchase Order
                    </a>
                    @endif
                    @if (auth()->user()->canPermission('delivery_orders.view'))
                    <a href="{{ route('delivery-orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('delivery-orders.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('delivery-orders.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">⇢</span>
                        DO
                    </a>
                    @endif
                    @if (auth()->user()->canPermission('goods_receipts.view'))
                    <a href="{{ route('goods-receipts.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('goods-receipts.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('goods-receipts.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">⇠</span>
                        Penerimaan (GRN)
                    </a>
                    @endif
                    @if (auth()->user()->canPermission('stock_cards'))
                        <a href="{{ route('stock-cards.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('stock-cards.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('stock-cards.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">▤</span>
                            Kartu Stok
                        </a>
                    @endif
                    <p class="mb-1 mt-4 px-3 pt-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Produksi & Utilitas</p>
                    @if (auth()->user()->canPermission('barcodes'))
                    <a href="{{ route('barcodes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('barcodes.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('barcodes.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">▥</span>
                        Cetak Barcode
                    </a>
                    @endif
                    @if (auth()->user()->canPermission('cutting'))
                        <a href="{{ route('cutting.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('cutting.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('cutting.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">✂</span>
                            Input Hasil Cutting
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('rejects.view'))
                        <a href="{{ route('rejects.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('rejects.*') ? 'bg-rose-50 text-rose-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('rejects.*') ? 'bg-white text-rose-700' : 'bg-rose-50 text-rose-600' }}">!</span>
                            Reject
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('spk.view'))
                        <a href="{{ route('spk.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('spk.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('spk.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">▣</span>
                            Surat Perintah Kerja
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('reports'))
                    <a href="{{ route('reports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[13px] font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('reports.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">▧</span>
                        Laporan
                    </a>
                    @endif
                    @if (auth()->user()->canPermission('manage.roles') || auth()->user()->canPermission('manage.users') || auth()->user()->canPermission('manage.branches') || auth()->user()->canPermission('manage.backup') || auth()->user()->canPermission('manage.audit_logs') || auth()->user()->role === 'super_admin')
                        <p class="mb-1 mt-4 px-3 pt-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Administrasi</p>
                    @endif
                    @if (auth()->user()->canPermission('manage.roles'))
                        <a href="{{ route('admin.role-access.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.role-access.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('admin.role-access.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">♙</span>
                            Role & Akses
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('manage.users'))
                        <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">♧</span>
                            Kelola Pengguna
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('manage.branches'))
                        <a href="{{ route('admin.branches.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.branches.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('admin.branches.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">⌖</span>
                            Master Cabang
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('manage.backup'))
                        <a href="{{ route('admin.backup.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.backup.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('admin.backup.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">⬇</span>
                            Backup
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('manage.audit_logs'))
                        <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('admin.audit-logs.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">◷</span>
                            Audit Log
                        </a>
                    @endif
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.settings.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-white text-emerald-700' : 'bg-slate-100 text-slate-500' }}">⚙</span>
                            Setting
                        </a>
                    @endif
                    @if (auth()->user()->canPermission('manage.master_data'))
                        <p class="mb-1 mt-5 px-3 pt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Master Barang</p>
                        <details class="group" @if (request()->routeIs('master-data.*')) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('master-data.*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="flex items-center gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100">▦</span>Master Barang</span>
                                <span class="text-xs">⌄</span>
                            </summary>
                            <div class="ml-6 mt-1 space-y-1 border-l border-slate-200 pl-3">
                                <a href="{{ route('master-data.products') }}" class="block rounded-lg px-3 py-2 text-xs font-semibold {{ request()->routeIs('master-data.products*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50' }}">Daftar Barang</a>
                                <a href="{{ route('master-data.import') }}" class="block rounded-lg px-3 py-2 text-xs font-semibold {{ request()->routeIs('master-data.import*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50' }}">Upload Master Data</a>
                                <a href="{{ route('master-data.ratios') }}" class="block rounded-lg px-3 py-2 text-xs font-semibold {{ request()->routeIs('master-data.ratios*') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-500 hover:bg-slate-50' }}">Rasio</a>
                                <a href="{{ route('master-data.index', 'units') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Unit</a>
                                <a href="{{ route('master-data.index', 'conversions') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Konversi</a>
                                <a href="{{ route('master-data.index', 'categories') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Kategori</a>
                                <a href="{{ route('master-data.index', 'sub-categories') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Sub-kategori</a>
                                <a href="{{ route('master-data.index', 'models') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Model / Artikel</a>
                                <a href="{{ route('master-data.index', 'sizes') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Ukuran</a>
                                <a href="{{ route('master-data.index', 'colors') }}" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-500 hover:bg-slate-50">Warna</a>
                            </div>
                        </details>
                    @endif
                    <a href="{{ route('knowledge-base.index') }}" class="mt-4 flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-700">?</span>
                        Knowledge Base
                    </a>
                </nav>
            </div>
            <div class="mt-auto p-4">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</span>
                        </span>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="block rounded-lg border border-slate-200 bg-white px-3 py-2 text-center text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">Profil & keamanan akun</a>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1 overflow-x-clip bg-[#f6f6f6]">
            @php
                $knowledgeMenu = match (true) {
                    request()->routeIs('dashboard') => 'dashboard',
                    request()->routeIs('orders.*') => 'orders',
                    request()->routeIs('delivery-orders.*') => 'delivery-orders',
                    request()->routeIs('goods-receipts.*') => 'goods-receipts',
                    request()->routeIs('stock-cards.*') => 'stock-cards',
                    request()->routeIs('spk.*') => 'spk',
                    request()->routeIs('cutting.*') => 'cutting',
                    request()->routeIs('barcodes.*') => 'barcodes',
                    request()->routeIs('reports.*') => 'reports',
                    request()->routeIs('master-data.import*') => 'master-data-import',
                    request()->routeIs('master-data.*') => 'master-data',
                    request()->routeIs('admin.role-access.*') => 'role-access',
                    request()->routeIs('admin.users.*') => 'users',
                    request()->routeIs('admin.branches.*') => 'branches',
                    request()->routeIs('admin.backup.*') => 'backup',
                    request()->routeIs('admin.audit-logs.*') => 'audit-logs',
                    request()->routeIs('admin.*') => 'admin',
                    request()->routeIs('profile.*') => 'profile',
                    default => null,
                };
            @endphp
            <header class="sticky top-0 z-20 flex h-[76px] items-center justify-between border-b border-slate-200/80 bg-white/95 px-3 backdrop-blur sm:px-8">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button id="sidebar-toggle" type="button" aria-label="Buka navigasi" aria-expanded="false" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="hidden h-9 w-9 items-center justify-center rounded-xl bg-emerald-700 text-xs font-black text-white sm:flex">RP</span>
                    <div>
                        <p class="hidden max-w-[45vw] truncate text-xs font-medium text-slate-400 sm:block">Workspace / <span class="text-slate-600">@yield('breadcrumb', 'Ringkasan')</span></p>
                        <h1 class="max-w-[40vw] truncate text-sm font-bold text-slate-900 sm:max-w-none sm:text-base">@yield('heading', 'Ringkasan operasional')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('knowledge-base.index', array_filter(['menu' => $knowledgeMenu])) }}" aria-label="Buka panduan menu ini" title="Panduan menu ini" class="flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-2.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 sm:px-3">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">?</span><span class="hidden sm:inline">Panduan</span>
                    </a>
                    <div class="hidden text-right sm:block">
                        <p class="text-xs font-semibold text-slate-700">{{ now()->translatedFormat('l, d F Y') }}</p>
                        <p class="text-[11px] text-slate-400">Gunung Karang · Cibentang</p>
                    </div>
                    <details class="group relative">
                        <summary aria-label="Notifikasi" data-notification-trigger data-read-url="{{ route('notifications.read') }}" class="relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-900/5">
                            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17H9.143m10.286 0H4.571c1.43-1.43 2.286-3.428 2.286-5.714V9a5.143 5.143 0 0 1 10.286 0v2.286c0 2.286.857 4.284 2.286 5.714ZM13.714 19.286a1.714 1.714 0 0 1-3.428 0" />
                            </svg>
                            @if ($notificationCount > 0)
                                <span id="notification-unread-count" class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[9px] font-bold text-white">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
                            @endif
                        </summary>
                        <div class="absolute right-0 z-30 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Aktivitas terbaru</p>
                                    <p class="text-[11px] text-slate-400">Pembaruan alur produksi</p>
                                </div>
                                @if ($notificationCount > 0)
                                    <span id="notification-unread-summary" class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">{{ $notificationCount }} belum dibaca</span>
                                @endif
                            </div>
                            <p data-notification-error role="alert" hidden class="border-b border-rose-100 bg-rose-50 px-4 py-2 text-xs text-rose-700">Notifikasi belum dapat ditandai sudah dibaca. Silakan coba lagi.</p>
                            @if ($recentNotifications->isEmpty())
                                <p class="px-4 py-8 text-center text-xs text-slate-400">Belum ada aktivitas produksi.</p>
                            @else
                                <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                                    @foreach ($recentNotifications as $notification)
                                        <a href="{{ route('orders.show', $notification->production_order_id) }}" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50">
                                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">✓</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-xs font-semibold text-slate-800">{{ str($notification->action)->replace('_', ' ')->title() }}</span>
                                                <span class="mt-1 block truncate text-[11px] text-slate-500">{{ $notification->order?->po_number ?? 'Purchase Order' }} · {{ $notification->user?->name ?? 'Sistem' }}</span>
                                            </span>
                                            <time class="shrink-0 text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</time>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </details>
                    <details class="group relative">
                        <summary aria-label="Menu akun" class="flex h-10 cursor-pointer list-none items-center gap-2 rounded-xl px-1.5 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-900/5 sm:gap-2.5 sm:pl-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-800">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="hidden text-left sm:block">
                                <span class="block max-w-32 truncate text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                                <span class="block text-[10px] text-slate-500">{{ auth()->user()->roleLabel() }}</span>
                            </span>
                            <svg aria-hidden="true" class="hidden h-4 w-4 text-slate-400 sm:block" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                        </summary>
                        <div class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-900/10">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Profil & ganti password</a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                                @csrf
                                <button class="w-full px-4 py-2.5 text-left text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Keluar dari aplikasi</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <section id="app-content" class="mx-auto min-w-0 max-w-[1440px] p-4 sm:p-8">
                @if (session('success'))
                    <div class="mb-5 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">✓</span>{{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                        <ul class="mt-1 list-inside list-disc text-xs">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </section>
            <footer class="mx-auto max-w-[1440px] px-5 pb-8 text-[11px] text-slate-400 sm:px-8">Konveksi Gallery Anak Rantau <span class="mx-1">·</span> Kontrol produksi terpadu Mydent, Gunung Karang & Cibentang</footer>
        </main>
    </div>
</body>
</html>
