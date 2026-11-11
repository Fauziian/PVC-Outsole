# Analisis Kelengkapan Bukti Screenshot Black Box Testing

Sumber analisis: `docx\Laporan\Laporan_Sumber-PVC.docx` (Bab IV > 4.2.3.1 Black Box Testing: `Tabel 4.4 Pelaksanaan Pengujian Fungsional Utama` dan `Tabel 4.5 Pelaksanaan Pengujian Tambahan`).

## A. Tabel 4.4 Pelaksanaan Pengujian Fungsional Utama (KU-01 s.d. KU-28)

| KU | Fitur | Status laporan saat ini | Gambar yang sudah ada | Gambar yang perlu dibuat |
| --- | --- | --- | --- | --- |
| KU-01 | Validasi Login Gagal | Sesuai | `image87.jpg` (teknis), `image88.jpg` (antarmuka) | Lengkap (tidak ada) |
| KU-02 | Login Berhasil & Redirect | Sesuai | `image89.jpg` (teknis), `image90.jpg` (antarmuka) | Lengkap (tidak ada) |
| KU-03 | Logout Sesi | Sesuai | `image91.jpg` (teknis) | `KU-03-antarmuka.png` (sebelum/sesudah logout ke halaman login) |
| KU-04 | Tambah Akun Pengguna | Sesuai | `image92.jpg` (teknis) | `KU-04-antarmuka.png` |
| KU-05 | Ubah Pengguna & Status | Sesuai | `image93.jpg` (teknis) | `KU-05-antarmuka.png` |
| KU-06 | Reset Kata Sandi | Sesuai | `image94.jpg` (teknis) | `KU-06-antarmuka.png` |
| KU-07 | Proteksi Akun Sendiri | Sesuai | `image95.jpg` (teknis) | `KU-07-antarmuka.png` |
| KU-08 | Tambah Data Karyawan | Sesuai | `image96.jpg` (teknis) | `KU-08-antarmuka.png` |
| KU-09 | Ubah Data Karyawan | Sesuai | `image97.jpg` (teknis) | `KU-09-antarmuka.png` |
| KU-10 | Hapus Data Karyawan | Sesuai | `image98.jpg` (teknis) | `KU-10-antarmuka.png` |
| KU-11 | Catat Check-In Masuk | Sesuai | `image99.jpg` (teknis) | `KU-11-antarmuka.png` |
| KU-12 | Validasi Duplikasi Masuk | Sesuai | `image100.jpg` (teknis) | `KU-12-antarmuka.png` |
| KU-13 | Catat Check-Out Pulang | Sesuai | `image101.jpg` (teknis) | `KU-13-antarmuka.png` |
| KU-14 | Ubah Tarif Upah Jam | Sesuai | `image102.jpg` (teknis) | `KU-14-antarmuka.png` |
| KU-15 | Kalkulasi Gaji Massal | Sesuai | `image103.jpg` (teknis) | `KU-15-antarmuka.png` |
| KU-16 | Pratinjau Lembar Slip | Sesuai | `image104.jpg` (teknis) | `KU-16-antarmuka.png` |
| KU-17 | Finalisasi Status Slip | Tidak Sesuai | `image105.jpg` (teknis), `image106.jpg` (kondisi sebelum), `image107.jpg` (hasil Tidak Sesuai) | Lengkap (tidak ada) |
| KU-18 | Unduh Slip Gaji PDF | Sesuai | `image108.jpg` (teknis) | `KU-18-antarmuka.png` |
| KU-19 | Katalog Stok Fisik | Sesuai | `image109.jpg` (teknis) | `KU-19-antarmuka.png` |
| KU-20 | Barang Masuk Batch | Sesuai | `image110.jpg` (teknis) | `KU-20-antarmuka.png` |
| KU-21 | Barang Keluar Pelanggan | Sesuai | `image111.jpg` (teknis) | `KU-21-antarmuka.png` |
| KU-22 | Validasi Stok Tidak Cukup | Sesuai | `image112.jpg` (teknis) | `KU-22-antarmuka.png` |
| KU-23 | Notifikasi Stok Minimum | Sesuai | `image113.jpg` (teknis) | `KU-23-antarmuka.png` |
| KU-24 | Tandai Baca Notifikasi | Tidak Sesuai | `image114.jpg` (teknis) | `KU-24-sebelum.png`, `KU-24-hasil.png` (perlu verifikasi status aktual aplikasi terlebih dahulu) |
| KU-25 | Ekspor Absensi PDF | Sesuai | `image115.jpg` (teknis) | `KU-25-antarmuka.png` |
| KU-26 | Ekspor Payroll Excel | Sesuai | `image116.jpg` (teknis) | `KU-26-antarmuka.png` |
| KU-27 | Ekspor Mutasi Stok Excel | Sesuai | `image117.jpg` (teknis) | `KU-27-antarmuka.png` |
| KU-28 | Proteksi RBAC URL Terlarang | Sesuai | `image118.jpg` (teknis) | `KU-28-antarmuka.png` |

## B. Tabel 4.5 Pelaksanaan Pengujian Tambahan (KU-29 s.d. KU-34) — Catatan Inventarisasi

| KU | Fitur | Status laporan saat ini | Gambar yang sudah ada | Gambar yang perlu dibuat |
| --- | --- | --- | --- | --- |
| KU-29 | Tarif Periode Historis | Tidak Sesuai | `image119.jpg` (teknis) | Belum ada antarmuka (di luar Tabel 4.4) |
| KU-30 | Pembaruan Nama dan Username | Tidak Sesuai | `image120.jpg` (teknis) | Belum ada antarmuka (di luar Tabel 4.4) |
| KU-31 | Sesi Akun Nonaktif | Tidak Sesuai | `image121.jpg` (teknis) | Belum ada antarmuka (di luar Tabel 4.4) |
| KU-32 | Jumlah Hadir HR | Tidak Sesuai | `image122.jpg` (teknis) | Belum ada antarmuka (di luar Tabel 4.4) |
| KU-33 | Batas Akhir Bulanan | Tidak Sesuai | `image123.jpg` (teknis) | Belum ada antarmuka (di luar Tabel 4.4) |
| KU-34 | Batas Akhir Mingguan | Tidak Sesuai | `image124.jpg` (teknis) | Belum ada antarmuka (di luar Tabel 4.4) |

## C. Ringkasan

- Total kasus uji di Tabel 4.4: 28 (KU-01 s.d. KU-28).
- Sudah lengkap bukti teknis + antarmuka: **3** (`KU-01`, `KU-02`, `KU-17`).
- Hanya memiliki bukti teknis dan membutuhkan screenshot antarmuka: **25** (`KU-03` s.d. `KU-16`, `KU-18` s.d. `KU-28`).
- Di luar Tabel 4.4, `Tabel 4.5` (`KU-29` s.d. `KU-34`) juga hanya memiliki bukti teknis tanpa bukti antarmuka.

