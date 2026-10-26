@extends('layouts.app')

@section('title', 'Buat DO')
@section('heading', 'Buat Delivery Order')
@section('breadcrumb', 'DO / Baru')

@section('content')
    <div class="mb-6">
        <a href="{{ route('delivery-orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-emerald-700">← Kembali ke daftar DO</a>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Buat Delivery Order</h2>
        <p class="mt-1 text-sm text-slate-500">Pilih PO untuk memuat seluruh item, lalu isi QTY pada item yang akan dikirim.</p>
    </div>

    @php
        $roleNames = [
            'admin' => 'Admin Workspace',
            'super_admin' => 'Super Admin',
            'mydent' => 'Mydent (Grosir)',
            'warehouse' => 'Gudang',
            'gunung_karang' => 'Konveksi Gunung Karang',
            'cibentang' => 'Konveksi Cibentang',
        ];
        $destinationNames = [
            'mydent' => 'Mydent (Grosir)',
            'gunung_karang' => 'Konveksi Gunung Karang',
            'cibentang' => 'Konveksi Cibentang',
        ];
    @endphp

    <form method="POST" action="{{ route('delivery-orders.store') }}" class="max-w-4xl space-y-5">
        @csrf
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">Rute pengiriman</h3>
                <p class="mt-1 text-xs text-slate-400">DO dibuat dari lokasi akun aktif. Admin dapat memilih lokasi pengirim.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="source_role">Lokasi pengirim <span class="text-rose-500">*</span></label>
                    @if (in_array(auth()->user()->role, ['super_admin', 'admin'], true))
                        <select id="source_role" name="source_role" data-destination-source required>
                            <option value="">Pilih lokasi pengirim</option>
                            @foreach ($sourceRoles as $role)
                                <option value="{{ $role }}" @selected(old('source_role') === $role)>{{ $roleNames[$role] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" id="source_role" name="source_role" value="{{ auth()->user()->role }}">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-700">{{ $roleNames[auth()->user()->role] ?? auth()->user()->roleLabel() }}</div>
                    @endif
                    @error('source_role')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="destination_role">Lokasi tujuan <span class="text-rose-500">*</span></label>
                    <select id="destination_role" name="destination_role" required>
                        <option value="">Pilih tujuan</option>
                        @foreach ($locations as $role)
                            <option value="{{ $role }}" data-destination-role="{{ $role }}" @selected(old('destination_role') === $role)>{{ $destinationNames[$role] }}</option>
                        @endforeach
                    </select>
                    @error('destination_role')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-7">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">Rincian barang</h3>
                <p class="mt-1 text-xs text-slate-400">Nomor DO dibuat otomatis. Detail item dari PO hanya dapat dilihat; isi QTY kirim untuk item yang dipilih.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="po_number">Nomor PO <span class="text-rose-500">*</span></label>
                    <select id="po_number" name="po_number" required data-items-url="{{ route('delivery-orders.purchase-orders', ['poNumber' => '__PO_NUMBER__']) }}">
                        <option value="">Pilih nomor PO</option>
                        @foreach ($purchaseOrders as $poNumber)
                            <option value="{{ $poNumber }}" @selected(old('po_number') === $poNumber)>{{ $poNumber }}</option>
                        @endforeach
                    </select>
                    @error('po_number')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="goods_type">Jenis barang <span class="text-rose-500">*</span></label>
                    <select id="goods_type" name="goods_type" required>
                        <option value="">Pilih jenis barang</option>
                        @foreach ($goodsTypes as $key => [$label, $unit])
                            <option value="{{ $key }}" data-unit="{{ $unit }}" @selected(old('goods_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('goods_type')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dispatched_at">Tanggal kirim <span class="text-rose-500">*</span></label>
                    <input id="dispatched_at" name="dispatched_at" type="date" value="{{ old('dispatched_at', now()->toDateString()) }}" required>
                    @error('dispatched_at')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes">Catatan pengiriman</label>
                    <input id="notes" name="notes" value="{{ old('notes') }}" placeholder="Catatan untuk penerima atau kurir">
                    @error('notes')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="po-item-grid-wrap" class="mt-6 hidden">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">Item dalam PO</h4>
                        <p class="mt-1 text-[11px] text-slate-400">Detail produk dikunci dari PO. Isi QTY saja; item dengan QTY kosong tidak masuk DO.</p>
                    </div>
                    <span id="po-item-count" class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600"></span>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[900px] table-fixed text-left">
                        <colgroup>
                            @foreach (['w-[42px]', 'w-[105px]', 'w-[150px]', 'w-[105px]', 'w-[70px]', 'w-[100px]', 'w-[120px]', 'w-[120px]', 'w-[120px]'] as $columnWidth)
                                <col class="{{ $columnWidth }}">
                            @endforeach
                        </colgroup>
                        <thead class="bg-slate-50 text-[9px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                @foreach (['#', 'Kategori', 'Produk', 'Model', 'Ukuran', 'Warna', 'Jenis kain', 'Target PO'] as $column)
                                    <th class="border-b border-slate-200 px-2 py-2.5">{{ $column }}</th>
                                @endforeach
                                <th id="po-quantity-heading" class="border-b border-slate-200 px-2 py-2.5">QTY kirim</th>
                            </tr>
                        </thead>
                        <tbody id="po-item-grid" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                @error('items')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                @foreach ($errors->get('items.*.quantity') as $messages)
                    @foreach ($messages as $message)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@endforeach
                @endforeach
            </div>
        </section>

        <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
            <a href="{{ route('delivery-orders.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
            <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Terbitkan DO</button>
        </div>
    </form>

    <template id="po-do-item-template">
        <tr class="bg-white text-xs text-slate-600">
            <td class="px-2 py-2 text-center" data-label="Item" data-cell="number"></td>
            <td class="px-2 py-2" data-label="Kategori" data-cell="category"></td>
            <td class="px-2 py-2" data-label="Produk" data-cell="product"></td>
            <td class="px-2 py-2" data-label="Model" data-cell="model"></td>
            <td class="px-2 py-2" data-label="Ukuran" data-cell="size"></td>
            <td class="px-2 py-2" data-label="Warna" data-cell="color"></td>
            <td class="px-2 py-2" data-label="Jenis kain" data-cell="fabric_name"></td>
            <td class="px-2 py-2 text-right" data-label="Target PO" data-cell="target"></td>
            <td class="px-2 py-2" data-label="QTY kirim"><input data-cell="quantity" type="number" min="0" step="0.01" placeholder="0" class="!rounded-lg !px-2 !py-2 !text-xs"></td>
        </tr>
    </template>
@endsection
