<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\SettingGaji;
use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Setting Gaji (Konfigurasi Parameter Penggajian)
        // DEFAULT: NILAI CONTOH — bukan kebijakan riil perusahaan
        $settingGaji = SettingGaji::create([
            'gaji_pokok_kategori_a'  => 4500000, // < 5 tahun masa kerja
            'gaji_pokok_kategori_b'  => 6000000, // >= 5 tahun masa kerja
            'insentif_jam_lebih_pct' => 7.00,    // 7% per jam lebih
            'insentif_lembur_pct'    => 17.50,   // 17.5% per jam lembur
            'potongan_setengah_pct'  => 40.00,   // 40% potongan setengah hari
            'is_active'              => true,
            'keterangan'             => 'Konfigurasi Default Proposal Kerja Praktik',
        ]);

        // 2. Seed Pengguna (Users) sesuai 4 Role Utama
        $usersData = [
            [
                'nama'      => 'Andi Pratama',
                'username'  => 'admin',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'is_active' => true,
            ],
            [
                'nama'      => 'Siti Rahayu',
                'username'  => 'hr',
                'password'  => Hash::make('password'),
                'role'      => 'hr',
                'is_active' => true,
            ],
            [
                'nama'      => 'Budi Santoso',
                'username'  => 'warehouse',
                'password'  => Hash::make('password'),
                'role'      => 'warehouse',
                'is_active' => true,
            ],
            [
                'nama'      => 'Drs. Haryanto, M.M.',
                'username'  => 'management',
                'password'  => Hash::make('password'),
                'role'      => 'management',
                'is_active' => true,
            ],
            [
                'nama'      => 'Nurul Fadilah',
                'username'  => 'hr.nurul',
                'password'  => Hash::make('password'),
                'role'      => 'hr',
                'is_active' => true,
            ],
            [
                'nama'      => 'Wahyu Setiawan',
                'username'  => 'wh.wahyu',
                'password'  => Hash::make('password'),
                'role'      => 'warehouse',
                'is_active' => true,
            ],
            [
                'nama'      => 'Bambang Sutrisno',
                'username'  => 'mgmt.bambang',
                'password'  => Hash::make('password'),
                'role'      => 'management',
                'is_active' => false, // Nonaktifkan satu user manajemen untuk demonstrasi
            ]
        ];

        $users = [];
        foreach ($usersData as $u) {
            $users[$u['username']] = User::create($u);
        }

        // 3. Seed Karyawan (Tanpa field departemen, sesuai ERD)
        $karyawanData = [
            [
                'nama' => 'Ahmad Fauzi',
                'jabatan' => 'Operator Produksi',
                'tanggal_masuk' => '2019-03-15', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Siti Rahayu',
                'jabatan' => 'Staf Administrasi HRD',
                'tanggal_masuk' => '2020-07-01', // >= 5 tahun (Kategori B)
                'is_active' => true,
                'username_linked' => 'hr',
            ],
            [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Operator Gudang Utama',
                'tanggal_masuk' => '2018-11-20', // >= 5 tahun (Kategori B)
                'is_active' => true,
                'username_linked' => 'warehouse',
            ],
            [
                'nama' => 'Dewi Permata',
                'jabatan' => 'Supervisor Produksi',
                'tanggal_masuk' => '2017-05-10', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Rini Wulandari',
                'jabatan' => 'Akuntan Keuangan',
                'tanggal_masuk' => '2021-01-15', // < 5 tahun (Kategori A)
                'is_active' => true,
            ],
            [
                'nama' => 'Joko Prasetyo',
                'jabatan' => 'Teknisi Mesin Outsole',
                'tanggal_masuk' => '2020-09-08', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Maya Indah',
                'jabatan' => 'Staf Pengadaan Bahan Baku',
                'tanggal_masuk' => '2022-03-01', // < 5 tahun (Kategori A)
                'is_active' => true,
            ],
            [
                'nama' => 'Hendra Kurnia',
                'jabatan' => 'Quality Control Officer',
                'tanggal_masuk' => '2019-08-22', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Lestari Dewi',
                'jabatan' => 'Operator Produksi Outsole',
                'tanggal_masuk' => '2021-06-14', // < 5 tahun (Kategori A)
                'is_active' => false, // Nonaktif
            ],
            [
                'nama' => 'Wahyu Setiawan',
                'jabatan' => 'Kepala Gudang',
                'tanggal_masuk' => '2016-12-01', // >= 5 tahun (Kategori B)
                'is_active' => true,
                'username_linked' => 'wh.wahyu',
            ],
            [
                'nama' => 'Nurul Fadilah',
                'jabatan' => 'Staf HR Karyawan',
                'tanggal_masuk' => '2022-09-05', // < 5 tahun (Kategori A)
                'is_active' => true,
                'username_linked' => 'hr.nurul',
            ],
            [
                'nama' => 'Agus Setiawan',
                'jabatan' => 'Driver Distribusi',
                'tanggal_masuk' => '2020-04-18', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
        ];

        $karyawans = [];
        foreach ($karyawanData as $k) {
            $usernameLinked = $k['username_linked'] ?? null;
            unset($k['username_linked']);

            // Tentukan kategori masa kerja otomatis
            $years = Carbon::parse($k['tanggal_masuk'])->diffInYears(Carbon::now());
            $k['kategori_masa_kerja'] = $years >= 5 ? 'B' : 'A';

            if ($usernameLinked && isset($users[$usernameLinked])) {
                $k['id_pengguna'] = $users[$usernameLinked]->id;
            }

            $karyawans[] = Karyawan::create($k);
        }

        // 4. Seed Absensi (Bulan berjalan dan bulan lalu)
        // Kita seed data 22 hari kerja untuk Karyawan Aktif
        $startDate = Carbon::now()->subMonth()->startOfMonth(); // Bulan Lalu
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        foreach ($karyawans as $emp) {
            if (!$emp->is_active) continue;

            $date = clone $startDate;
            $dayCount = 0;

            while ($date->lessThanOrEqualTo($endDate) && $dayCount < 22) {
                // Lewati akhir pekan (Sabtu & Minggu)
                if ($date->isWeekend()) {
                    $date->addDay();
                    continue;
                }

                $statusRandom = rand(1, 100);
                if ($statusRandom <= 5) {
                    // Setengah hari (< 8 jam, e.g. 5 jam)
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '13:00:00';
                    $durasi = 5.00;
                    $status = 'Setengah Hari';
                } elseif ($statusRandom <= 15) {
                    // Jam Lebih (8-12 jam, e.g. 10 jam)
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '18:00:00';
                    $durasi = 10.00;
                    $status = 'Jam Lebih';
                } elseif ($statusRandom <= 20) {
                    // Lembur (> 15 jam, e.g. 16 jam)
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '00:00:00'; // keesokan harinya/lewat tengah malam
                    $durasi = 16.00;
                    $status = 'Lembur';
                } else {
                    // Normal
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '17:00:00';
                    $durasi = 9.00; // termasuk 1 jam istirahat = 8 jam kerja bersih
                    $status = 'Penuh';
                }

                Absensi::create([
                    'id_karyawan' => $emp->id,
                    'tanggal' => $date->toDateString(),
                    'jam_masuk' => $jamMasuk,
                    'jam_keluar' => $jamKeluar,
                    'durasi_jam' => $durasi,
                    'status_kehadiran' => $status,
                    'keterangan' => 'Kehadiran Harian Terdata',
                ]);

                $date->addDay();
                $dayCount++;
            }
        }

        // 5. Katalog barang jadi. Jumlah disimpan dan ditampilkan dalam kodi.
        $barangData = [
            ['kode_barang' => 'TJ-PD-PTH', 'kategori' => 'Tali Jepit', 'jenis' => 'Pria Dewasa',   'warna' => 'Putih', 'stok_saat_ini' => 240],
            ['kode_barang' => 'TJ-PD-MRH', 'kategori' => 'Tali Jepit', 'jenis' => 'Pria Dewasa',   'warna' => 'Merah', 'stok_saat_ini' => 180],
            ['kode_barang' => 'TJ-PD-HTM', 'kategori' => 'Tali Jepit', 'jenis' => 'Pria Dewasa',   'warna' => 'Hitam', 'stok_saat_ini' => 210],
            ['kode_barang' => 'TJ-WD-PTH', 'kategori' => 'Tali Jepit', 'jenis' => 'Wanita Dewasa', 'warna' => 'Putih', 'stok_saat_ini' => 190],
            ['kode_barang' => 'TJ-WD-MRH', 'kategori' => 'Tali Jepit', 'jenis' => 'Wanita Dewasa', 'warna' => 'Merah', 'stok_saat_ini' => 170],
            ['kode_barang' => 'TJ-WD-HTM', 'kategori' => 'Tali Jepit', 'jenis' => 'Wanita Dewasa', 'warna' => 'Hitam', 'stok_saat_ini' => 160],
            ['kode_barang' => 'TJ-UP-BRU', 'kategori' => 'Tali Jepit', 'jenis' => 'Upin',           'warna' => 'Biru',  'stok_saat_ini' => 120],
            ['kode_barang' => 'TJ-IP-PNK', 'kategori' => 'Tali Jepit', 'jenis' => 'Ipin',           'warna' => 'Pink',  'stok_saat_ini' => 110],
        ];

        $barangData = array_map(fn ($barang) => [
            ...$barang,
            'nama_barang' => "{$barang['jenis']} - {$barang['warna']}",
            'satuan' => 'kodi',
            'stok_minimum' => 25,
            'keterangan' => null,
        ], $barangData);

        $barangs = [];
        foreach ($barangData as $b) {
            $barangs[$b['kode_barang']] = BarangPvc::create($b);
        }

        // 6. Contoh hasil cetak yang masuk ke gudang.
        $incomingData = [
            ['kode_barang' => 'TJ-PD-PTH', 'jumlah' => 100],
            ['kode_barang' => 'TJ-WD-MRH', 'jumlah' => 100],
            ['kode_barang' => 'TJ-UP-BRU', 'jumlah' => 50],
        ];

        foreach ($incomingData as $in) {
            $kb = $in['kode_barang'];
            unset($in['kode_barang']);
            $in['id_barang'] = $barangs[$kb]->id;
            $in['tanggal'] = Carbon::now()->toDateString();
            $in['keterangan'] = 'Hasil cetak harian';
            BarangMasuk::create($in);
        }

        // 7. Contoh pengiriman barang jadi kepada pelanggan.
        $outgoingData = [
            ['kode_barang' => 'TJ-PD-PTH', 'jumlah' => 40, 'tujuan_penggunaan' => 'Toko Sandal Jaya'],
            ['kode_barang' => 'TJ-WD-MRH', 'jumlah' => 30, 'tujuan_penggunaan' => 'Toko Sandal Jaya'],
        ];

        foreach ($outgoingData as $out) {
            $kb = $out['kode_barang'];
            unset($out['kode_barang']);
            $out['id_barang'] = $barangs[$kb]->id;
            $out['tanggal'] = Carbon::now()->subDay()->toDateString();
            $out['keterangan'] = 'Pengiriman barang jadi';
            BarangKeluar::create($out);
        }
    }
}
