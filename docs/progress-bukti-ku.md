# Catatan Progres: Melengkapi Bukti Screenshot Black Box Testing (Bab IV)

- **Terakhir diperbarui**: `2026-11-11 17:11:00 +07:00`
- **Prompt awal**: Melengkapi bukti screenshot Black Box Testing Bab IV (`Tabel 4.4 Pelaksanaan Pengujian Fungsional Utama` dan `Tabel 4.5 Pelaksanaan Pengujian Tambahan`) di `docx\Laporan\Laporan_Sumber-PVC.docx` untuk seluruh KU berdasarkan eksekusi nyata (PHPUnit SQLite terisolasi + Playwright antarmuka lokal), tanpa mengubah kode aplikasi di luar commit `9c18cf5`.

---

## Langkah yang Sudah Selesai

1. **Backup dokumen laporan**:
   - Tersimpan di `docx\Laporan\_backup\Laporan_Sumber-PVC_2026-11-11_1102.docx` (tidak tersentuh).
2. **Inventarisasi awal (Langkah 1)**:
   - Terdokumentasi di `docs\analisis-bukti-ku.md` dan hasil dump di `..\sumber-pvc-scratch\table_4_4_utf8.txt`.
3. **Suite pengujian `tests\Feature\ReferenceChapterFourTest.php`**:
   - Dibuat dengan `RefreshDatabase` (SQLite `:memory:`) dan `#[DataProvider('casesProvider')]` untuk `KU-01` s.d. `KU-28`.
   - Eksekusi penuh `php artisan test tests/Feature/ReferenceChapterFourTest.php`: **28 passed (109 assertions)**.
   - Eksekusi terfilter `php artisan test --filter=KU-17` (**1 passed, 3 assertions**, 0.97s) dan `php artisan test --filter=KU-24` (**1 passed, 3 assertions**, 0.52s) pada kode baseline `9c18cf5` (+ `eb7e9d9` hanya README).
4. **Template kartu & skrip capture Playwright (`scripts\evidence\card-template.html`, `scripts\evidence\capture-all.js`)**:
   - Selesai dibuat dan dijalankan pada server PHP lokal sementara melawan basis data SQLite terisolasi (`..\sumber-pvc-scratch\evidence_ui.sqlite`).
   - Menghasilkan **48 berkas bukti baru** di `docx\Laporan\gambar\`: `KU-XX-teknis.png` dan `KU-XX-antarmuka.png` untuk **24 KU** (`KU-03` s.d. `KU-16`, `KU-18` s.d. `KU-23`, dan `KU-25` s.d. `KU-28`), lengkap dengan verifikasi unduhan PDF (`%PDF`) dan XLSX (`PK\x03\x04`).
   - Kartu teknis terbaru `KU-17` dan `KU-24` sementara disimpan di `..\sumber-pvc-scratch\KU-17-teknis-terbaru.png` dan `..\sumber-pvc-scratch\KU-24-teknis-terbaru.png` sampai mendapat konfirmasi pengguna.
   - Seluruh hasil tercatat di `docs\evidence-manifest.json`.

---

## Langkah yang Belum Selesai (Menunggu Konfirmasi Pengguna)

1. Menunggu jawaban/konfirmasi pengguna terkait **KU-17**, **KU-24**, dan **Tabel 4.5 (KU-29 s.d. KU-34)**.
2. Pengambilan screenshot antarmuka terbaru untuk `KU-17` dan `KU-24` (serta `KU-29` s.d. `KU-34` bila disetujui untuk dijalankan ulang pada commit `9c18cf5`).
3. Penyusunan dan persetujuan pengguna atas draf perubahan teks (`Hasil Pengujian`, kalimat pengantar `Tabel 4.4` & `Tabel 4.5`, dan kesimpulan subbab `4.2.3`).
4. Penyisipan seluruh gambar bukti ke salinan kerja `Laporan_Sumber-PVC.docx`, validasi struktur XML & tampilan Word, lalu penulisan ke `docx\Laporan\Laporan_Sumber-PVC.docx` dan pembersihan folder scratch.

---

## Daftar Berkas yang Sudah Dibuat

- **Di dalam workspace**:
  - `docs\analisis-bukti-ku.md`
  - `docs\progress-bukti-ku.md` (catatan ini)
  - `docs\evidence-manifest.json`
  - `tests\Feature\ReferenceChapterFourTest.php`
  - `scripts\evidence\card-template.html`
  - `scripts\evidence\capture-all.js`
  - `docx\Laporan\gambar\KU-03-*.png` s.d. `KU-16-*.png`, `KU-18-*.png` s.d. `KU-23-*.png`, `KU-25-*.png` s.d. `KU-28-*.png` (48 file)
- **Status `docx\Laporan\Laporan_Sumber-PVC.docx`**: Belum disentuh/diubah sama sekali.
- **Status kode aplikasi (`app/`, `routes/`, `resources/`)**: Tidak ada perubahan sama sekali.

---

## Hal yang Masih Menunggu Jawaban Pengguna

1. **Output test terbaru `KU-17` dan `KU-24`**:
   - Hasil eksekusi nyata saat ini pada baseline `9c18cf5`:
     - `KU-17`: `PASS` (`status_sebelum_hitung_ulang: "final"`, `status_setelah_hitung_ulang: "final"`).
     - `KU-24`: `PASS` (`PUT stock/notifications/{notification}/read`, `POST stock/notifications/read-all`, `is_read: true`).
   - Mohon konfirmasi hasil pembanding dari Anda (jika ada) serta persetujuan menampilkan status terbaru (`Sesuai`) di `Tabel 4.4`.
2. **Isi `Tabel 4.5` (`KU-29` s.d. `KU-34`)**:
   - Tertulis di dokumen saat ini (semua `Tidak Sesuai`):
     - `KU-29` | Tarif Periode Historis | `Tidak Sesuai`
     - `KU-30` | Pembaruan Nama dan Username | `Tidak Sesuai`
     - `KU-31` | Sesi Akun Nonaktif | `Tidak Sesuai`
     - `KU-32` | Jumlah Hadir HR | `Tidak Sesuai`
     - `KU-33` | Batas Akhir Bulanan | `Tidak Sesuai`
     - `KU-34` | Batas Akhir Mingguan | `Tidak Sesuai`
   - Mohon konfirmasi apakah `KU-29` s.d. `KU-34` juga ditambahkan ke `ReferenceChapterFourTest.php`, dijalankan ulang pada commit `9c18cf5`, dilengkapi screenshot antarmuka, dan diperbarui hasilnya (dengan menyimpan bukti lama pra-perbaikan bila perlu).
3. **Konfirmasi penyimpanan bukti lama `KU-17`**:
   - Mohon konfirmasi agar tangkapan layar lama `KU-17` (`image105.jpg` teknis gagal, `image106.jpg` sebelum hitung ulang `FINAL`, `image107.jpg` hasil lama `DRAFT` / *Failed asserting*) tetap dipertahankan sebagai riwayat dengan keterangan *"Hasil pengujian sebelum perbaikan (commit lama)"* bersama bukti terbaru pasca-`9c18cf5`.
