# Sistem Informasi Manajemen Pabrik PVC - Sumber PVC

Sistem Informasi Manajemen Terpadu untuk operasional pabrik sandal PVC (Outsole, Tali Jepit, dan Boloni Gunung). Aplikasi mencakup pengelolaan absensi harian, perhitungan penggajian otomatis berbasis tarif masa kerja, kontrol stok dan mutasi barang jadi gudang, serta pusat rekapitulasi laporan dalam format PDF dan Excel.

---

## 🛠️ Arsitektur & Teknologi

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: React 18, TypeScript, Inertia.js
- **Styling**: Tailwind CSS, Lucide React Icons
- **Database**: MySQL (Produksi & Lokal XAMPP) / SQLite (Testing)
- **Ekspor Dokumen**: DomPDF (Slip Gaji & Laporan Kehadiran), PhpSpreadsheet (Rekap Mutasi & Penggajian)

---

## 👥 Hak Akses & Akun Demo

Aplikasi memiliki 3 peran (*role*) pengguna dengan batasan hak akses di level middleware:

| Role | Username | Password | Deskripsi Tugas |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `password` | Manajemen pengguna, pengaturan tarif pabrik, master produk, ringkasan dashboard eksekutif, dan seluruh laporan. |
| **HR / Keuangan** | `hr` | `password` | Manajemen data karyawan, pencatatan absensi harian (masuk/pulang/lembur), kalkulasi slip gaji bulanan, dan laporan absensi. |
| **Gudang (Warehouse)** | `warehouse` | `password` | Pencatatan barang masuk hasil cetak, barang keluar ke pelanggan, pemantauan stok kritis, notifikasi peringatan minimum, dan laporan mutasi. |

> **Catatan Keamanan**: Untuk lingkungan produksi publik, ubah seluruh kata sandi akun bawaan dan set `APP_DEBUG=false`.

---

## ⚙️ Modul & Logika Bisnis Utama

### 1. Modul Penggajian & Tarif Pabrik
- **Tarif Berdasarkan Masa Kerja**:
  - Kategori A (< 5 Tahun): Rp12.000 / jam
  - Kategori B (≥ 5 Tahun): Rp17.000 / jam
- **Jam Kerja & Lembur**:
  - Jam kerja reguler: 8 – 13 jam per hari
  - Jam ke-14 dan seterusnya dihitung sebagai jam lembur
- **Proteksi Slip Final**: Slip yang sudah berstatus `FINAL` dikunci dan tidak dapat ditimpa saat proses kalkulasi ulang payroll dijalankan.

### 2. Modul Kehadiran (Absensi)
- Pencatatan jam masuk dan jam pulang karyawan aktif dengan validasi tanggal maksimal hari ini.
- Mendukung durasi kerja fleksibel (termasuk setengah hari < 8 jam).
- Kunci absensi otomatis untuk periode penggajian yang telah difinalisasi.

### 3. Modul Kontrol Stok Gudang
- Satuan stok produk jadi dicatat dalam **kodi** (1 kodi = 20 pasang).
- Pengurangan stok menggunakan transaksi aman database (`DB::transaction` + `lockForUpdate`).
- Notifikasi stok kritis otomatis muncul untuk staf gudang dan admin ketika stok produk menyentuh atau berada di bawah batas minimum.
- Validasi pencegahan input ganda (*distinct items*) dan validasi tanggal transaksi.

### 4. Pusat Rekapitulasi Laporan
- Ekspor slip gaji individu PDF dengan rincian jam kerja dan tanda tangan digital.
- Ekspor rekap kehadiran bulanan PDF.
- Ekspor rekapitulasi penggajian bulanan Excel.
- Ekspor rekap mutasi barang masuk, keluar, dan selisih stok (mingguan/bulanan) Excel & PDF.

---

## 🚀 Panduan Instalasi Lokal

1. **Clone & Masuk ke Direktori Proyek**:
   ```bash
   cd c:/xampp/htdocs/sumber-pvc
   ```

2. **Instal Dependensi**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**:
   Salin `.env.example` ke `.env` (bila belum ada) dan sesuaikan konfigurasi database MySQL.
   ```env
   APP_NAME="Sumber PVC"
   APP_ENV=local
   APP_DEBUG=true
   APP_TIMEZONE=Asia/Jakarta
   APP_LOCALE=id

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sumber-pvc
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Migrasi & Seeder Database**:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

5. **Build Aset Frontend & Jalankan Server**:
   ```bash
   npm run build
   php artisan serve
   ```
   Buka browser di `http://127.0.0.1:8000` atau melalui virtual host XAMPP Anda.

---

## 🧪 Pengujian Otomatis

Jalankan test suite aplikasi:
```bash
php artisan test
# Atau via PHPUnit langsung:
php vendor/phpunit/phpunit/phpunit
```
