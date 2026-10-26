# Ruang Produksi

Aplikasi web mandiri untuk mencatat siklus produksi konveksi: Purchase Order Mydent, DO dan penerimaan kain, SPK, cutting Gunung Karang, vendor sablon/bordir, transfer jahit Cibentang, finishing, QC, pengiriman, Goods Receipt, dan penutupan PO.

## Teknologi

- Laravel 12 dan PHP 8.2+
- Vite, Tailwind CSS 3, dan Blade
- MySQL/MariaDB untuk aplikasi; SQLite tersedia untuk pengujian otomatis

## Menjalankan di Windows / Laragon

Pastikan PHP 8.2+ (termasuk ekstensi `pdo_mysql`), Composer, Node.js 18+, dan npm tersedia. Jalankan MySQL dari Laragon. Aplikasi menggunakan database `ruang_produksi` ber-charset `utf8mb4`; sesuaikan host, port, nama database, dan kredensial pada `.env` jika diperlukan. Jalankan migrasi untuk membuat tabel:

```powershell
$env:PHP_ROOT = Split-Path (Get-Command php).Source
$env:PHPRC = (Get-Location).Path
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
.\serve.ps1
```

Jika beralih dari instalasi SQLite yang telah dipakai, gunakan proses migrasi data terencana dan simpan file SQLite asli sebagai cadangan sampai semua data terverifikasi di MySQL.

Buka `http://127.0.0.1:8000`. Skrip `serve.ps1` memuat ekstensi PHP dari `php.ini` proyek dan menyajikan file Vite dengan router bawaan Laravel. Jika PowerShell membatasi skrip, jalankan `powershell -ExecutionPolicy Bypass -File .\serve.ps1`. Jika Laragon atau terminal belum memasukkan Composer/Node ke `PATH`, gunakan executable dari folder instalasinya.

### Akun demo lokal

Seeder hanya menambahkan akun dan contoh pesanan ketika `APP_ENV=local`. Semua akun demo memakai kata sandi `password` secara default:

| Peran | Email |
|---|---|
| Admin Workspace | `admin@konveksi.test` |
| Toko Mydent | `mydent@konveksi.test` |
| Gudang | `warehouse@konveksi.test` |
| Gunung Karang | `gunungkarang@konveksi.test` |
| Cibentang | `cibentang@konveksi.test` |

Pada halaman login lokal, klik salah satu akun untuk mengisi email dan kata sandi secara otomatis. Untuk pengembangan lokal, ubah `DEMO_PASSWORD` di `.env` lalu jalankan ulang `php artisan db:seed` jika ingin kata sandi demo berbeda. Daftar akun dan kata sandi demo tidak ditampilkan pada environment selain `local`; jangan gunakan kata sandi demo pada deployment.

## Alur dan kontrol

- Mydent/Admin membuat satu PO dengan beberapa varian produk; setiap varian menyimpan kategori, model/motif, warna, ukuran, kebutuhan kain, rib, rasio, dan target pcs hasil perhitungan. Nomor PO dapat dibuat otomatis atau ditentukan manual. Transaksi produksi dicatat per varian dan Close PO mengunci seluruh variannya sekaligus.
- Gudang mencatat DO serta penerimaan fisik; selisih wajib diberi catatan sebelum SPK dapat diterbitkan.
- Admin menerbitkan SPK. Cutting hanya dapat dicatat oleh Admin/Gunung Karang.
- Permintaan serta pengeluaran bahan tambahan dicocokkan terhadap nama item, satuan, jumlah, batch, dan penerima serah-terima.
- Cutting mencatat hasil aktual, pemakaian kain, dan kategori yield. Vendor dan Cibentang memiliki transaksi pengiriman, penerimaan, hasil baik, dan reject yang tervalidasi terhadap saldo potongan atau baju jahit.
- Gunung Karang mencatat jahit, finishing, QC 100%, serta pengiriman hanya dari stok lolos QC.
- Mydent mencatat Goods Receipt; perbedaan kirim/terima memerlukan alasan. Close PO membutuhkan catatan rekonsiliasi dan mengunci transaksi berikutnya.
- Menu **DO** memuat seluruh varian setelah nomor PO dipilih; detail produk bersifat hanya-baca dan QTY dikirim diisi per item. Satu nomor DO dibuat otomatis untuk semua item yang dipilih, untuk pengiriman kain, aksesoris, potongan, atau baju jadi. Tujuan Mydent, Gunung Karang, dan Cibentang disesuaikan dengan lokasi pengirim; menu **Penerimaan (GRN)** menampilkan item DO masuk, menerima kuantitas secara bertahap, dan meminta catatan untuk selisih.
- Setiap langkah menjadi entri audit append-only dengan pengguna, waktu, referensi dokumen, kuantitas, dan catatan.
- Menu **Audit Log** (khusus Admin) menyatukan transaksi workflow, perubahan master barang, dan aktivitas backup; tersedia filter pengguna/kata kunci, jenis aktivitas, serta rentang tanggal.
- Menu **Backup** (khusus Admin) membuat snapshot SQLite atau dump MySQL dan menyimpan file secara privat. Backup MySQL memerlukan `mysqldump` yang tersedia di `PATH` atau path executable yang ditentukan melalui `MYSQLDUMP_PATH`.
- Menu **Cetak Barcode** dapat mencari barang berdasarkan kode/nama, menambahkan beberapa barang dan jumlah label, mengatur ukuran kertas X × Y (50–500 mm) serta kolom per baris (1–10), melihat preview Code 128, dan mencetak hingga 500 label sekaligus. Pilih skala 100% dan matikan header/footer pada dialog printer.
- Autentikasi memakai sesi Laravel; pembatasan peran dan validasi saldo dijalankan di server.
- Menu akun di kanan atas menyediakan profil, penggantian kata sandi dengan verifikasi kata sandi saat ini, dan logout. Lonceng menampilkan aktivitas transaksi terbaru.

Item PO dapat diisi dalam tabel grid dan ditambah beberapa baris sekaligus. Rasio menggunakan kebutuhan kain per pcs; target dihitung dengan membagi yard/kg terhadap rasio lalu membulatkan ke pcs terdekat. Dari halaman detail item PO, gunakan **Cetak / Unduh PDF** untuk mengunduh dokumen landscape berisi seluruh varian serta total kain dan pcs.

Menu **Master Barang** (khusus Admin Workspace) menyediakan input unit, konversi unit, kategori, sub-kategori, model/artikel, ukuran, warna, dan master barang. Setiap data memperoleh kode acak otomatis. Daftar barang memiliki tampilan Grid dan Kanban, dengan layout kartu untuk perangkat mobile. Navigasi sidebar dapat dibuka atau disembunyikan dari tombol hamburger di header pada desktop maupun mobile.

Dashboard menampilkan enam ringkasan status PO dan tiga analitik: perbandingan rasio target PO dengan hasil cutting, selisih hasil cutting dengan jumlah potongan pada DO, serta jumlah PO aktif per lokasi (Gunung Karang, Cibentang, Gudang, dan Grosir Mydent). Status dihitung per nomor PO (bukan per varian); jika varian dalam satu PO memiliki status berbeda, status paling maju yang belum ditutup mewakili PO tersebut. Pemetaan lokasi merupakan estimasi PIC berdasarkan tahap aktif, termasuk transfer yang masih menunggu penerimaan Cibentang.

## Pengujian

```powershell
php artisan test
npm run build
```

Pengujian memakai database SQLite sementara, sehingga tidak mengubah database aplikasi lokal.
