<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    private const ARTICLES = [
        ['key' => 'dashboard', 'category' => 'Umum', 'title' => 'Ringkasan operasional', 'summary' => 'Pantau KPI, PO gantung, hasil cutting, dan alur vendor lintas lokasi.', 'steps' => ['Gunakan kartu KPI untuk membuka daftar transaksi yang mendasarinya.', 'Bandingkan PO dengan hasil cutting dan rekonsiliasi vendor.', 'Tindak lanjuti PO gantung berdasarkan lokasi sampai penerimaan dan rekonsiliasi selesai.']],
        ['key' => 'orders', 'category' => 'Pengadaan & Gudang', 'title' => 'Purchase Order', 'summary' => 'Buat PO multi-item, verifikasi kain, terbitkan SPK, dan jalankan aktivitas produksi.', 'steps' => ['Buat PO dengan nomor, tanggal, target, serta detail setiap item.', 'Simpan PO lalu cetak PDF sebagai dokumen referensi.', 'Catat penerimaan kain dan verifikasi fisik sebelum menerbitkan SPK.', 'Lanjutkan transaksi melalui aksi yang tersedia; sistem memeriksa role, kuantitas, dan urutan proses.']],
        ['key' => 'delivery-orders', 'category' => 'Pengadaan & Gudang', 'title' => 'Delivery Order (DO)', 'summary' => 'Catat perpindahan barang antar lokasi dengan serah-terima yang dapat ditelusuri.', 'steps' => ['Pilih PO dan tujuan pengiriman yang tersedia untuk akun Anda.', 'Periksa item PO yang terisi otomatis, lalu masukkan kuantitas kirim.', 'Terbitkan DO dan pastikan lokasi penerima mengonfirmasi barang secara fisik.']],
        ['key' => 'goods-receipts', 'category' => 'Pengadaan & Gudang', 'title' => 'Penerimaan (GRN)', 'summary' => 'Catat jumlah aktual yang diterima di lokasi yang sesuai.', 'steps' => ['Pilih DO yang akan diterima dan verifikasi nomor dokumennya.', 'Periksa jumlah fisik; catat kekurangan atau selisih pada penerimaan.', 'Simpan GRN agar saldo dan riwayat PO diperbarui.']],
        ['key' => 'stock-cards', 'category' => 'Pengadaan & Gudang', 'title' => 'Kartu stok', 'summary' => 'Tinjau saldo berjalan dan catat penerimaan atau pengeluaran barang.', 'steps' => ['Pilih barang dan lokasi untuk melihat mutasi serta saldo setelah transaksi.', 'Catat mutasi dengan referensi dokumen dan kuantitas yang benar.', 'Pengeluaran melebihi saldo ditolak; selesaikan selisih melalui transaksi koreksi yang sah.']],
        ['key' => 'spk', 'category' => 'Produksi', 'title' => 'Surat Perintah Kerja (SPK)', 'summary' => 'Cetak instruksi kerja dari target PO dan lengkapi tanda tangan penanggung jawab.', 'steps' => ['Admin menerbitkan target SPK pada detail PO setelah kain diterima.', 'Buka menu SPK, pilih PO, dan tinjau spesifikasi serta target setiap item.', 'Tanda tangani langsung di layar sentuh mobile atau unggah scan JPG dari desktop.', 'Unduh PDF SPK untuk arsip; setelah tanda tangan tersimpan dokumen dikunci.']],
        ['key' => 'cutting', 'category' => 'Produksi', 'title' => 'Hasil cutting', 'summary' => 'Catat hasil potong dan bandingkan dengan rasio/target PO.', 'steps' => ['Pilih PO dengan SPK terbit.', 'Masukkan jumlah hasil cutting aktual dan rincian yield yang diminta form.', 'Simpan laporan setelah memeriksa satuan dan selisih target.']],
        ['key' => 'rejects', 'category' => 'Produksi', 'title' => 'Reject', 'summary' => 'Pantau jumlah barang reject yang dicatat pada penerimaan vendor, retur Cibentang, dan pemeriksaan QC.', 'steps' => ['Buka menu Reject untuk meninjau seluruh catatan reject.', 'Gunakan nomor PO, referensi, atau catatan untuk menelusuri kejadian.', 'Buka detail PO untuk memeriksa konteks transaksi dan tindak lanjut yang dicatat.']],
        ['key' => 'barcodes', 'category' => 'Produksi', 'title' => 'Cetak barcode', 'summary' => 'Cari barang, atur ukuran label, tinjau, lalu cetak barcode.', 'steps' => ['Cari barang dengan kode atau nama.', 'Atur ukuran kertas dan jumlah label per baris sesuai media cetak.', 'Tinjau preview dan gunakan dialog cetak browser.']],
        ['key' => 'reports', 'category' => 'Laporan', 'title' => 'Laporan', 'summary' => 'Tinjau data operasional dan ekspor untuk analisis/arsip.', 'steps' => ['Atur filter periode/lokasi yang tersedia.', 'Periksa hasil di layar sebelum ekspor.', 'Pilih PDF untuk dokumen atau Excel untuk pengolahan data.']],
        ['key' => 'master-data', 'category' => 'Master Barang', 'title' => 'Master barang', 'summary' => 'Kelola barang dan atribut pendukung agar transaksi memakai data konsisten.', 'steps' => ['Isi unit dan konversi, kategori/subkategori, model, ukuran, dan warna.', 'Tambahkan barang dengan kode yang dihasilkan otomatis.', 'Atur rasio yard kain per pcs melalui submenu Rasio agar otomatis terisi di PO.', 'Tinjau daftar barang dalam mode grid atau kanban dan perbarui data yang tidak tepat.']],
        ['key' => 'master-data-import', 'category' => 'Master Barang', 'title' => 'Upload master data', 'summary' => 'Impor barang dan atribut dari Excel (.xlsx) atau CSV mengikuti format template.', 'steps' => ['Siapkan header KATEGORI, SUB_KATEGORI, UKURAN, ARTIKEL, WARNA, JENIS KAIN, dan NAMA BARANG. Kategori seperti Celana dipisahkan dari jenis kain seperti Chinos.', 'Isi kategori, sub-kategori, dan warna. Ukuran boleh kosong dan diisi All Size, sedangkan artikel dan jenis kain opsional. Unit otomatis Pcs.', 'Unggah file maksimal 10 MB dan 5.000 baris. Jika ada baris wajib yang tidak valid, upload dibatalkan.', 'Kategori dan atribut dibuat bila belum tersedia; kode master dibuat otomatis dan barang duplikat dilewati. Template lama BAHAN/KATEGORI tetap didukung.']],
        ['key' => 'role-access', 'category' => 'Administrasi', 'title' => 'Role & Akses', 'summary' => 'Tinjau batas akses setiap peran dan terapkan pemisahan tanggung jawab.', 'steps' => ['Periksa matriks akses sebelum memberikan role kepada pengguna.', 'Pisahkan tugas input, penerimaan, produksi, dan rekonsiliasi akhir.', 'Akun Super Admin dikelola secara terbatas dan tidak untuk transaksi rutin.']],
        ['key' => 'users', 'category' => 'Administrasi', 'title' => 'Kelola pengguna', 'summary' => 'Buat dan rawat akun kerja sesuai identitas dan tanggung jawabnya.', 'steps' => ['Buat pengguna dengan nama, email unik, role, dan cabang yang benar.', 'Pastikan akun diuji oleh pemiliknya dan kata sandi awal segera diganti.', 'Nonaktifkan atau hapus akun yang tidak lagi berwenang sesuai kebijakan.']],
        ['key' => 'branches', 'category' => 'Administrasi', 'title' => 'Master cabang', 'summary' => 'Jaga referensi cabang agar pelaporan dan transaksi konsisten.', 'steps' => ['Tambahkan cabang dengan kode unik dan nama yang mudah dikenali.', 'Pastikan status aktif sebelum menetapkan cabang ke pengguna.', 'Jangan menghapus referensi yang masih diperlukan untuk riwayat transaksi.']],
        ['key' => 'backup', 'category' => 'Administrasi', 'title' => 'Backup database', 'summary' => 'Buat dan unduh arsip basis data untuk pemulihan operasional.', 'steps' => ['Jalankan backup berkala dari menu Backup.', 'Unduh lalu simpan salinan di lokasi terpisah yang terlindungi.', 'Uji prosedur pemulihan secara terkontrol sesuai kebijakan IT.']],
        ['key' => 'audit-logs', 'category' => 'Administrasi', 'title' => 'Audit Log', 'summary' => 'Telusuri aktivitas perubahan dan transaksi penting pengguna.', 'steps' => ['Filter log berdasarkan periode, pelaku, atau jenis kejadian.', 'Periksa identitas pengguna, waktu, objek, serta metadata aktivitas.', 'Gunakan catatan sebagai bukti pemeriksaan; jangan mengubah log secara manual.']],
        ['key' => 'settings', 'category' => 'Administrasi', 'title' => 'Setting dan truncate database', 'summary' => 'Tools khusus Super Admin untuk menghapus data aplikasi setelah backup.', 'steps' => ['Buat dan unduh backup dari menu Backup sebelum reset.', 'Periksa jumlah data yang akan dihapus pada halaman Setting.', 'Masukkan kata sandi Super Admin, centang konfirmasi backup, lalu ketik HAPUS SEMUA DATA.', 'Akun Super Admin aktif dan tabel infrastruktur/migrasi dipertahankan; data aplikasi lain akan dihapus permanen.']],
        ['key' => 'profile', 'category' => 'Akun', 'title' => 'Profil dan keamanan akun', 'summary' => 'Perbarui identitas akun dan kata sandi.', 'steps' => ['Buka menu akun di kanan atas lalu pilih profil.', 'Untuk mengganti kata sandi, masukkan kata sandi saat ini dan kata sandi baru yang kuat.', 'Keluar setelah menggunakan perangkat bersama.']],
    ];

    public function index(Request $request): View
    {
        $this->ensureDefaultArticles();
        $menu = $request->query('menu');
        $search = trim((string) $request->query('q', ''));
        $articles = KnowledgeBaseArticle::query()
            ->when($menu, fn ($query) => $query->where('key', $menu))
            ->orderBy('category')
            ->orderBy('title')
            ->get()
            ->when($search !== '', fn ($results) => $results
                ->filter(fn (KnowledgeBaseArticle $article): bool => str_contains(
                    mb_strtolower(implode(' ', [$article->title, $article->category, $article->summary, ...$article->steps])),
                    mb_strtolower($search),
                ))
                ->values());

        return view('knowledge-base.index', [
            'articles' => $articles,
            'selectedMenu' => $menu,
            'search' => $search,
            'menuOptions' => KnowledgeBaseArticle::query()->orderBy('title')->pluck('title', 'key'),
            'canEdit' => $request->user()->role === 'super_admin',
        ]);
    }

    public function edit(KnowledgeBaseArticle $article): View
    {
        abort_unless(request()->user()->role === 'super_admin', 403);

        return view('knowledge-base.edit', compact('article'));
    }

    public function update(Request $request, KnowledgeBaseArticle $article): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $values = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:2000'],
            'steps' => ['required', 'string', 'max:10000'],
        ]);
        $steps = collect(preg_split('/\r\n|\r|\n/', $values['steps']))
            ->map(fn (string $step): string => trim($step))
            ->filter()
            ->values();
        if ($steps->isEmpty()) {
            return back()->withErrors(['steps' => 'Masukkan sedikitnya satu langkah panduan.'])->withInput();
        }
        $previous = $article->only(['category', 'title', 'summary', 'steps']);
        $article->update([
            'category' => $values['category'],
            'title' => $values['title'],
            'summary' => $values['summary'],
            'steps' => $steps->all(),
        ]);
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'event' => 'knowledge_base_article_updated',
            'subject_type' => 'knowledge_base_article',
            'subject_id' => (string) $article->id,
            'description' => 'Panduan '.$article->title.' diperbarui.',
            'metadata' => [
                'key' => $article->key,
                'before' => $previous,
                'after' => $article->only(['category', 'title', 'summary', 'steps']),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('knowledge-base.index', ['menu' => $article->key])
            ->with('success', 'Panduan '.$article->title.' berhasil diperbarui.');
    }

    private function ensureDefaultArticles(): void
    {
        foreach (self::ARTICLES as $article) {
            KnowledgeBaseArticle::query()->firstOrCreate(['key' => $article['key']], $article);
        }
    }
}
