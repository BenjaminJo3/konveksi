@extends('layouts.app')

@section('title', $order->po_number)
@section('heading', 'Detail Purchase Order')
@section('breadcrumb', 'Purchase Order / '.$order->po_number)

@section('content')
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-emerald-700">← Kembali ke daftar PO</a>
        <div class="mt-3 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><div class="flex flex-wrap items-center gap-2.5"><h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $order->po_number }}</h2><x-order-status :order="$order" /></div><p class="mt-1 text-sm text-slate-500">{{ $order->product_name }}{{ $order->model ? ' · '.$order->model : '' }}{{ $order->size ? ' · '.$order->size : '' }}{{ $order->color ? ' · '.$order->color : '' }}{{ $order->fabric_name ? ' · '.$order->fabric_name : '' }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $order->category }}{{ $order->sub_category ? ' · '.$order->sub_category : '' }}{{ $order->po_date ? ' · PO '.$order->po_date->format('d M Y') : '' }}</p></div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canManagePo)
                    <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Edit PO</a>
                    <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Hapus seluruh PO {{ $order->po_number }} beserta semua variannya? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Hapus PO</button>
                    </form>
                @endif
                <a href="{{ route('orders.pdf', $order) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-slate-800">Cetak / Unduh PDF</a>
                <p class="text-xs text-slate-400">Dibuat {{ $order->created_at->format('d M Y') }} oleh <span class="font-semibold text-slate-600">{{ $order->creator->name }}</span></p>
            </div>
        </div>
    </div>

    @if ($poItems->count() > 1)
        <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Varian dalam Purchase Order</h3>
                <p class="mt-0.5 text-xs text-slate-400">Setiap ukuran/warna memiliki saldo produksi tersendiri; penutupan berlaku untuk seluruh PO.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left">
                    <thead><tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-bold uppercase tracking-wider text-slate-400"><th class="px-5 py-3">Produk</th><th class="px-4 py-3">Warna</th><th class="px-4 py-3">Ukuran</th><th class="px-4 py-3">Target</th><th class="px-5 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($poItems as $poItem)
                            <tr class="{{ $poItem->id === $order->id ? 'bg-slate-50' : '' }}">
                                <td class="px-5 py-3"><a href="{{ route('orders.show', $poItem) }}" class="text-xs font-semibold text-slate-800 hover:text-emerald-700">{{ $poItem->product_name }}{{ $poItem->model ? ' · '.$poItem->model : '' }}</a></td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $poItem->color ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $poItem->size ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs font-semibold text-slate-700">{{ number_format($poItem->order_quantity) }} pcs</td>
                                <td class="px-5 py-3"><x-order-status :order="$poItem" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-6">
        @foreach ([['Target PO', $order->order_quantity, 'pcs', 'bg-blue-50 text-blue-700'], ['Kain DO', $order->fabric_delivered ?? '—', 'yard', 'bg-cyan-50 text-cyan-700'], ['Hasil cutting', $summary['cut'], 'pcs', 'bg-amber-50 text-amber-700'], ['Selesai jahit', $summary['sewn'], 'pcs', 'bg-violet-50 text-violet-700'], ['Lolos QC', $summary['passed'], 'pcs', 'bg-emerald-50 text-emerald-700'], ['Diterima toko', $summary['received'], 'pcs', 'bg-teal-50 text-teal-700']] as [$label, $value, $unit, $color])
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4"><p class="text-[10px] font-semibold text-slate-400">{{ $label }}</p><p class="mt-2 text-lg font-bold text-slate-900">{{ is_numeric($value) ? number_format((float) $value, is_float($value) && floor($value) !== $value ? 2 : 0) : $value }} <span class="text-[10px] font-medium text-slate-400">{{ $unit }}</span></p><span class="mt-2 inline-flex rounded-md px-1.5 py-0.5 text-[9px] font-bold {{ $color }}">LIVE</span></div>
        @endforeach
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <section class="rounded-2xl border border-slate-200/80 bg-white">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-bold text-slate-900">Alur & riwayat transaksi</h3><p class="mt-0.5 text-xs text-slate-400">Setiap langkah tercatat sebagai jejak audit dan tidak dapat dihapus.</p></div>
            <div class="p-5">
                @php
                    $labels = [
                        'po_created' => 'Purchase Order dibuat', 'request_fabric' => 'Permintaan kain', 'deliver_fabric' => 'DO kain diterbitkan',
                        'receive_fabric' => 'Penerimaan kain', 'issue_spk' => 'SPK diterbitkan', 'request_material' => 'Permintaan bahan tambahan',
                        'issue_material' => 'Pengeluaran bahan & serah-terima', 'cutting_report' => 'Laporan cutting',
                        'vendor_send' => 'Kirim ke vendor', 'vendor_receive' => 'Penerimaan vendor', 'site_transfer' => 'Transfer ke Cibentang',
                        'site_receive' => 'Potongan diterima Cibentang', 'site_return' => 'Hasil jahit Cibentang diterima',
                        'sewing' => 'Proses jahit Gunung Karang', 'finishing' => 'Finishing selesai', 'quality_check' => 'Pemeriksaan QC',
                        'prepare_shipment' => 'Pengiriman disiapkan', 'shipment' => 'Barang dikirim', 'goods_receipt' => 'Penerimaan Mydent', 'close_order' => 'PO ditutup',
                    ];
                @endphp
                @if ($timeline->isEmpty())
                    <p class="py-8 text-center text-xs text-slate-400">Belum ada transaksi untuk PO ini.</p>
                @else
                    <div class="relative space-y-0">
                        <div class="absolute bottom-5 left-[15px] top-5 w-px bg-slate-100"></div>
                        @foreach ($timeline as $entry)
                            <div class="relative flex gap-3 py-3 first:pt-0 last:pb-0">
                                <span class="z-10 mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-4 border-white bg-emerald-100 text-[10px] font-bold text-emerald-800">✓</span>
                                <div class="min-w-0 flex-1 pb-3">
                                    <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-start"><p class="text-xs font-bold text-slate-800">{{ $labels[$entry->action] ?? str($entry->action)->replace('_', ' ')->title() }}</p><time class="text-[10px] text-slate-400">{{ $entry->created_at->format('d M Y, H:i') }}</time></div>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $entry->user?->name ?? 'Sistem' }}{{ $entry->reference ? ' · '.$entry->reference : '' }}</p>
                                    @if ($entry->quantity !== null || $entry->secondary_quantity !== null)
                                        <p class="mt-1 text-[11px] font-semibold text-slate-600">
                                            @if ($entry->action === 'quality_check') Diperiksa {{ data_get($entry->metadata, 'inspected_quantity', 0) }} pcs · @endif
                                            @if (in_array($entry->action, ['deliver_fabric', 'receive_fabric'], true))
                                                {{ number_format((float) $entry->quantity, 2) }} yard
                                            @else
                                                {{ number_format($entry->quantity ?? 0) }} pcs
                                            @endif
                                            @if ($entry->secondary_quantity) <span class="font-normal text-rose-600">· reject {{ number_format($entry->secondary_quantity) }}</span> @endif
                                        </p>
                                    @endif
                                    @if ($entry->metadata)<p class="mt-1 text-[10px] text-slate-400">{{ collect($entry->metadata)->map(fn ($value, $key) => str($key)->replace('_', ' ')->title().': '.$value)->implode(' · ') }}</p>@endif
                                    @if ($entry->notes)<p class="mt-1 rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-5 text-slate-500">{{ $entry->notes }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="space-y-6">
            <section id="workflow-actions" class="scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white">
                <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-bold text-slate-900">Catat langkah berikutnya</h3><p class="mt-0.5 text-xs text-slate-400">Formulir yang tersedia mengikuti peran Anda dan status PO.</p></div>
                @if ($order->status === 'closed')
                    <div class="p-5"><div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-bold text-slate-800">PO sudah ditutup</p><p class="mt-1 text-xs leading-5 text-slate-500">{{ $order->close_note }}</p><p class="mt-2 text-[10px] text-slate-400">Ditutup {{ $order->closed_at?->format('d M Y, H:i') }}</p></div></div>
                @elseif (empty($availableActions))
                    <div class="p-5"><div class="rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-500">Belum ada langkah yang dapat dilakukan dengan peran Anda. Tahap berikutnya mungkin menunggu serah-terima dari tim lain.</div></div>
                @else
                    <div class="space-y-3 p-4 sm:p-5">
                        @foreach ($availableActions as $item)
                            <details class="group rounded-xl border border-slate-200 bg-white open:border-emerald-200 open:bg-emerald-50/20" @if ($item['key'] === 'cutting_report') open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5">
                                    <span><span class="block text-xs font-bold text-slate-800">{{ $item['label'] }}</span><span class="mt-1 block text-[10px] leading-4 text-slate-400">{{ $item['description'] }}</span></span>
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400 transition group-open:rotate-45 group-open:bg-emerald-100 group-open:text-emerald-800">+</span>
                                </summary>
                                <form method="POST" action="{{ route('orders.actions.store', $order) }}" class="space-y-3 border-t border-slate-100 px-4 py-4">
                                    @csrf
                                    <input type="hidden" name="action" value="{{ $item['key'] }}">
                                    @switch($item['key'])
                                        @case('request_fabric')
                                            <div><label for="notes-{{ $item['key'] }}">Catatan permintaan (opsional)</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2" placeholder="Keterangan kebutuhan kain">{{ old('notes') }}</textarea></div>
                                            @break
                                        @case('deliver_fabric')
                                            <div><label for="reference-{{ $item['key'] }}">Nomor DO kain *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="DO-KAIN-2026-001"></div>
                                            <div><label for="quantity-{{ $item['key'] }}">Jumlah dikirim (yard) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="0.01" step="0.01" required placeholder="{{ $order->fabric_estimate }}"></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('receive_fabric')
                                            <div><label for="quantity-{{ $item['key'] }}">Jumlah fisik diterima (yard) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="0.01" step="0.01" required placeholder="{{ $order->fabric_estimate }}"></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan selisih / hasil verifikasi</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2" placeholder="Wajib jika jumlah fisik berbeda dari DO"></textarea></div>
                                            @break
                                        @case('issue_spk')
                                            <div><label for="quantity-{{ $item['key'] }}">Target potong SPK (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" max="{{ $order->order_quantity }}" required value="{{ $order->order_quantity }}"></div>
                                            <p class="text-[10px] text-slate-400">Target maksimum PO: {{ number_format($order->order_quantity) }} pcs.</p>
                                            @break
                                        @case('request_material')
                                            <div><label for="reference-{{ $item['key'] }}">Nama bahan tambahan *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="Benang, kancing, zipper, label"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="quantity-{{ $item['key'] }}">Jumlah diminta *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required></div><div><label for="unit-{{ $item['key'] }}">Satuan *</label><input id="unit-{{ $item['key'] }}" name="unit" required placeholder="pcs / cone / gross"></div></div>
                                            <div><label for="notes-{{ $item['key'] }}">Alasan / catatan permintaan</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('issue_material')
                                            <div><label for="reference-{{ $item['key'] }}">Nama bahan sesuai permintaan *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="Nama bahan yang diminta"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="quantity-{{ $item['key'] }}">Jumlah dikeluarkan *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required></div><div><label for="unit-{{ $item['key'] }}">Satuan *</label><input id="unit-{{ $item['key'] }}" name="unit" required placeholder="pcs / cone / gross"></div></div>
                                            <div><label for="batch-{{ $item['key'] }}">Nomor batch / lot *</label><input id="batch-{{ $item['key'] }}" name="batch" required></div>
                                            <div><label for="received-{{ $item['key'] }}">Diterima oleh *</label><input id="received-{{ $item['key'] }}" name="received_by" required placeholder="Nama penerima di site"></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan serah-terima</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('cutting_report')
                                            <div><label for="quantity-{{ $item['key'] }}">Hasil potong aktual (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required placeholder="Masukkan jumlah aktual"></div>
                                            <div><label for="fabric-used-{{ $item['key'] }}">Pemakaian kain aktual (yard) *</label><input id="fabric-used-{{ $item['key'] }}" type="number" name="fabric_used" min="0" step="0.01" required></div>
                                            <div><label for="yield-{{ $item['key'] }}">Kategori hasil / selisih *</label><select id="yield-{{ $item['key'] }}" name="yield_category"><option value="target">Sesuai target</option><option value="over_yield">Over-yield</option><option value="waste">Waste / sisa potong</option><option value="defective_fabric">Reject / cacat kain</option></select></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan cutting</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('site_transfer')
                                        @case('site_receive')
                                        @case('sewing')
                                        @case('finishing')
                                            @if (in_array($item['key'], ['site_transfer', 'site_receive'], true))
                                                <div><label for="reference-{{ $item['key'] }}">Nomor transfer / surat jalan *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="TR-CIB-2026-001"></div>
                                            @endif
                                            <div><label for="quantity-{{ $item['key'] }}">Jumlah (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required placeholder="Masukkan jumlah aktual"></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan (opsional)</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('vendor_send')
                                            <div><label for="vendor-{{ $item['key'] }}">Nama vendor *</label><input id="vendor-{{ $item['key'] }}" name="vendor" required placeholder="Vendor sablon / bordir"></div>
                                            <div><label for="reference-{{ $item['key'] }}">Nomor DO vendor *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="DO-VND-2026-001"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="process-{{ $item['key'] }}">Jenis proses *</label><select id="process-{{ $item['key'] }}" name="process"><option>Sablon</option><option>Bordir</option></select></div><div><label for="material-{{ $item['key'] }}">Tipe material *</label><select id="material-{{ $item['key'] }}" name="material_type"><option>Potongan Bahan</option><option>Baju Jahit</option></select></div></div>
                                            <div><label for="quantity-{{ $item['key'] }}">Jumlah dikirim (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required></div>
                                            <div><label for="notes-{{ $item['key'] }}">Instruksi kerja</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2" placeholder="Contoh: sablon rubber dada kiri 10 x 10 cm"></textarea></div>
                                            @break
                                        @case('vendor_receive')
                                            <div><label for="material-type-{{ $item['key'] }}">Tipe material yang kembali *</label><select id="material-type-{{ $item['key'] }}" name="material_type" required><option>Potongan Bahan</option><option>Baju Jahit</option></select></div>
                                            <div><label for="reference-{{ $item['key'] }}">Nomor DO vendor asal *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="DO-VND-2026-001"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="accepted-{{ $item['key'] }}">Diterima baik (pcs) *</label><input id="accepted-{{ $item['key'] }}" type="number" name="accepted_quantity" min="0" required value="0"></div><div><label for="rejected-{{ $item['key'] }}">Reject (pcs) *</label><input id="rejected-{{ $item['key'] }}" type="number" name="rejected_quantity" min="0" required value="0"></div></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan hasil pemeriksaan</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('site_return')
                                            <div><label for="reference-{{ $item['key'] }}">Nomor transfer dari Cibentang *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="TR-CIB-2026-001"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="quantity-{{ $item['key'] }}">Baju jadi baik (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="0" required value="0"></div><div><label for="rejected-{{ $item['key'] }}">Reject jahit (pcs) *</label><input id="rejected-{{ $item['key'] }}" type="number" name="rejected_quantity" min="0" required value="0"></div></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan retur</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('quality_check')
                                            <div><label for="inspected-{{ $item['key'] }}">Jumlah diperiksa (pcs) *</label><input id="inspected-{{ $item['key'] }}" type="number" name="inspected_quantity" min="1" required></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="passed-{{ $item['key'] }}">Lolos QC *</label><input id="passed-{{ $item['key'] }}" type="number" name="passed_quantity" min="0" required value="0"></div><div><label for="rejected-{{ $item['key'] }}">Reject *</label><input id="rejected-{{ $item['key'] }}" type="number" name="rejected_quantity" min="0" required value="0"></div></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan QC</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2"></textarea></div>
                                            @break
                                        @case('prepare_shipment')
                                            <div><label for="quantity-{{ $item['key'] }}">Jumlah dikirim (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required></div>
                                            <div><label for="reference-{{ $item['key'] }}">Nomor surat jalan *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="SJ-2026-001"></div>
                                            <div class="grid grid-cols-2 gap-3"><div><label for="receiver-{{ $item['key'] }}">Penerima di toko *</label><input id="receiver-{{ $item['key'] }}" name="receiver" required></div><div><label for="driver-{{ $item['key'] }}">Kurir / sopir</label><input id="driver-{{ $item['key'] }}" name="driver"></div></div>
                                            @break
                                        @case('shipment')
                                            <div><label for="reference-{{ $item['key'] }}">Nomor surat jalan yang diberangkatkan *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="SJ-2026-001"></div>
                                            <div class="rounded-lg bg-blue-50 px-3 py-2 text-[10px] leading-5 text-blue-800">Barang dan jumlah telah dialokasikan saat surat jalan disiapkan. Periksa nomor dokumen sebelum konfirmasi.</div>
                                            @break
                                        @case('goods_receipt')
                                            <div><label for="reference-{{ $item['key'] }}">Nomor surat jalan yang diterima *</label><input id="reference-{{ $item['key'] }}" name="reference" required placeholder="SJ-2026-001"></div>
                                            <div><label for="quantity-{{ $item['key'] }}">Jumlah diterima aktual (pcs) *</label><input id="quantity-{{ $item['key'] }}" type="number" name="quantity" min="1" required></div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan selisih</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="2" placeholder="Wajib jika barang diterima kurang dari surat jalan"></textarea></div>
                                            @break
                                        @case('close_order')
                                            <div class="rounded-lg bg-amber-50 px-3 py-2 text-[10px] leading-5 text-amber-800">Penutupan akan mengunci PO dan seluruh alur transaksi. Tindakan ini tidak dapat dibatalkan.</div>
                                            <div><label for="notes-{{ $item['key'] }}">Catatan rekonsiliasi / persetujuan selisih *</label><textarea id="notes-{{ $item['key'] }}" name="notes" rows="3" required minlength="5" placeholder="Tulis persetujuan akhir dan keterangan selisih"></textarea></div>
                                            @break
                                    @endswitch
                                    <button class="w-full rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-emerald-800">Simpan {{ strtolower($item['label']) }}</button>
                                </form>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5">
                <h3 class="text-sm font-bold text-slate-900">Ringkasan spesifikasi</h3>
                <div class="mt-4 divide-y divide-slate-100">
                    <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Kategori / sub-kategori</span><span class="text-right text-xs font-semibold text-slate-800">{{ $order->category ?: '—' }}{{ $order->sub_category ? ' / '.$order->sub_category : '' }}</span></div>
                    <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Model / warna / ukuran</span><span class="text-right text-xs font-semibold text-slate-800">{{ $order->model ?: '—' }} / {{ $order->color ?: '—' }} / {{ $order->size ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Target PO</span><span class="text-xs font-semibold text-slate-800">{{ number_format($order->order_quantity) }} pcs</span></div>
                    <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Estimasi kebutuhan kain</span><span class="text-xs font-semibold text-slate-800">{{ number_format((float) $order->fabric_estimate, 2) }} yard/kg</span></div>
                    @if ($order->rib_qty !== null)<div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Jumlah rib</span><span class="text-xs font-semibold text-slate-800">{{ number_format((float) $order->rib_qty, 2) }}</span></div>@endif
                    @if ($order->ratio !== null)
                        <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Rasio kain per pcs</span><span class="text-xs font-semibold text-slate-800">{{ number_format((float) $order->ratio, 4) }} yard/pcs</span></div>
                        <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Hasil perhitungan rasio</span><span class="text-xs font-semibold text-slate-800">{{ number_format((float) $order->ratio_result_qty, 4) }} pcs → {{ number_format($order->order_quantity) }} pcs</span></div>
                    @else
                        <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Rasio estimasi</span><span class="text-xs font-semibold text-slate-800">{{ number_format((float) $order->fabric_ratio, 3) }} pcs / yard</span></div>
                    @endif
                    <div class="flex justify-between gap-3 py-2.5"><span class="text-xs text-slate-500">Target selesai</span><span class="text-xs font-semibold text-slate-800">{{ $order->delivery_due_at?->format('d M Y') ?? 'Belum ditentukan' }}</span></div>
                </div>
            </section>
        </div>
    </div>
@endsection
