<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\KomponenGaji;
use App\Models\Karyawan;
use App\Models\SettingGaji;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Menghitung gaji bulanan berdasarkan absensi aktual.
     *
     * Tidak ada gaji pokok atau persentase lembur: setiap jam kerja dibayar
     * Rp12.000 (< 5 tahun) atau Rp17.000 (>= 5 tahun). Delapan jam pertama
     * pada satu catatan adalah jam normal; sisanya adalah lembur.
     */
    public function calculate(Karyawan $karyawan, string $periode): array
    {
        $setting = SettingGaji::aktif();
        $kategoriMasaKerja = $karyawan->kategori_otomatis;
        $tarifPerJam = $kategoriMasaKerja === 'B'
            ? $setting->tarif_per_jam_kategori_b
            : $setting->tarif_per_jam_kategori_a;

        $absensiList = Absensi::where('id_karyawan', $karyawan->id)
            ->whereYear('tanggal', substr($periode, 0, 4))
            ->whereMonth('tanggal', substr($periode, 5, 2))
            ->orderBy('tanggal')
            ->get();

        $totalJamNormal = 0.0;
        $totalJamLembur = 0.0;
        $rincianAbsensi = [];

        foreach ($absensiList as $absensi) {
            $durasi = (float) $absensi->durasi_jam;
            // Catatan lama belum memiliki pemisahan jam; tetap dapat dihitung.
            $jamNormal = $absensi->jam_normal === null ? min($durasi, 8) : (float) $absensi->jam_normal;
            $jamLembur = $absensi->jam_lembur === null ? max($durasi - 8, 0) : (float) $absensi->jam_lembur;

            $totalJamNormal += $jamNormal;
            $totalJamLembur += $jamLembur;
            $rincianAbsensi[] = [
                'tanggal' => $absensi->tanggal->format('Y-m-d'),
                'shift' => $absensi->shift ?? '-',
                'jam_masuk' => substr($absensi->jam_masuk, 0, 5),
                'jam_keluar' => substr($absensi->jam_keluar, 0, 5),
                'jam_normal' => $jamNormal,
                'jam_lembur' => $jamLembur,
                'durasi' => $durasi,
            ];
        }

        $upahJamNormal = (int) round($totalJamNormal * $tarifPerJam);
        $upahLembur = (int) round($totalJamLembur * $tarifPerJam);

        return [
            'kategori_masa_kerja' => $kategoriMasaKerja,
            'tarif_per_jam' => $tarifPerJam,
            'gaji_pokok' => $upahJamNormal,
            'tunjangan' => 0,
            'insentif_lembur' => $upahLembur,
            'potongan' => 0,
            'total_gaji' => $upahJamNormal + $upahLembur,
            'hari_hadir' => $absensiList->count(),
            'hari_setengah' => $absensiList->filter(fn (Absensi $absensi) => (float) $absensi->durasi_jam < 8)->count(),
            'total_jam_normal' => $totalJamNormal,
            'jam_lebih' => 0,
            'jam_lembur' => $totalJamLembur,
            'rincian' => [
                'kategori_masa_kerja' => $kategoriMasaKerja,
                'tarif_per_jam' => $tarifPerJam,
                'upah_jam_normal' => $upahJamNormal,
                'upah_lembur' => $upahLembur,
                'absensi' => $rincianAbsensi,
            ],
        ];
    }

    public function generate(Karyawan $karyawan, string $periode): KomponenGaji
    {
        return DB::transaction(function () use ($karyawan, $periode) {
            $data = $this->calculate($karyawan, $periode);

            return KomponenGaji::updateOrCreate(
                ['id_karyawan' => $karyawan->id, 'periode' => $periode],
                [
                    'gaji_pokok' => $data['gaji_pokok'],
                    'tunjangan' => $data['tunjangan'],
                    'insentif_lembur' => $data['insentif_lembur'],
                    'potongan' => $data['potongan'],
                    'total_gaji' => $data['total_gaji'],
                    'hari_hadir' => $data['hari_hadir'],
                    'hari_setengah' => $data['hari_setengah'],
                    'total_jam_normal' => $data['total_jam_normal'],
                    'jam_lebih' => $data['jam_lebih'],
                    'jam_lembur' => $data['jam_lembur'],
                    'tarif_per_jam' => $data['tarif_per_jam'],
                    'rincian' => $data['rincian'],
                    'status' => 'draft',
                ]
            );
        });
    }
}
