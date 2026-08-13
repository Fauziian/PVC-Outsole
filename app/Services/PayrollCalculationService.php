<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\SettingGaji;
use App\Models\KomponenGaji;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Hitung komponen gaji untuk satu karyawan pada periode tertentu.
     * Periode format: "YYYY-MM" (e.g. "2026-08")
     *
     * Aturan Bisnis dari Proposal:
     * - Gaji Pokok ditentukan dari Kategori Masa Kerja Karyawan:
     *   - Kategori A (< 5 tahun): dari setting_gaji (default Rp4,500,000)
     *   - Kategori B (>= 5 tahun): dari setting_gaji (default Rp6,000,000)
     * - Tunjangan Jabatan: Rp500,000 untuk jabatan Supervisor / Kepala / Quality Control.
     * - Tunjangan Transport: Rp250,000 (fixed untuk karyawan aktif)
     * - Tunjangan Makan: Rp150,000 (fixed untuk karyawan aktif)
     * - Hari Kerja Standar per Bulan: 22 Hari.
     * - Gaji Harian = Gaji Pokok / 22
     * - Potongan Setengah Hari (< 8 jam): 40% dari Gaji Harian.
     * - Insentif Kerja Lebih (8-12 jam): 7% dari Gaji Harian per jam lebih.
     * - Insentif Lembur (> 15 jam): 17.5% dari Gaji Harian per jam lembur.
     * - Tanpa PPh 21 dan BPJS (ditiadakan sesuai revisi ERD).
     */
    public function calculate(Karyawan $karyawan, string $periode): array
    {
        $setting = SettingGaji::aktif();

        // 1. Tentukan Gaji Pokok
        $kategoriMasaKerja = $karyawan->kategori_masa_kerja;
        $gajiPokok = $kategoriMasaKerja === 'B' 
            ? $setting->gaji_pokok_kategori_b 
            : $setting->gaji_pokok_kategori_a;

        // Gaji harian untuk basis potongan & insentif
        $gajiHarian = $gajiPokok / 22;

        // 2. Tunjangan
        $isSenior = false;
        $jabatanLower = strtolower($karyawan->jabatan);
        foreach (['supervisor', 'kepala', 'quality control', 'hrd', 'pimpinan'] as $keyword) {
            if (str_contains($jabatanLower, $keyword)) {
                $isSenior = true;
                break;
            }
        }
        $tunjanganJabatan = $isSenior ? 500000 : 0;
        $tunjanganTransport = 250000;
        $tunjanganMakan = 150000;
        $totalTunjangan = $tunjanganJabatan + $tunjanganTransport + $tunjanganMakan;

        // 3. Proses Kehadiran & Jam Kerja
        // Ambil data absensi karyawan pada periode ini
        $absensiList = Absensi::where('id_karyawan', $karyawan->id)
            ->whereYear('tanggal', substr($periode, 0, 4))
            ->whereMonth('tanggal', substr($periode, 5, 2))
            ->get();

        $hariHadir = 0;
        $hariSetengah = 0;
        $jamLebih = 0.0;
        $jamLembur = 0.0;

        $insentifJamLebih = 0.0;
        $insentifLembur = 0.0;
        $potonganSetengah = 0.0;

        $rincianAbsensi = [];

        foreach ($absensiList as $abs) {
            $durasi = floatval($abs->durasi_jam);
            $status = $abs->status_kehadiran;

            if ($durasi >= 8.0) {
                $hariHadir++;
                
                // Jam Lebih (8 - 12 jam)
                if ($durasi > 8.0 && $durasi <= 12.0) {
                    $lebih = $durasi - 8.0;
                    $jamLebih += $lebih;
                    // 7% dari gaji harian per jam lebih
                    $insentifJamLebih += $lebih * $gajiHarian * ($setting->insentif_jam_lebih_pct / 100);
                } 
                // Lembur (> 15 jam)
                elseif ($durasi > 15.0) {
                    $lembur = $durasi - 8.0; // hitung lembur dari jam ke-8
                    $jamLembur += $lembur;
                    // 17.5% dari gaji harian per jam lembur
                    $insentifLembur += $lembur * $gajiHarian * ($setting->insentif_lembur_pct / 100);
                }
            } else {
                // Setengah Hari (< 8 jam)
                $hariSetengah++;
                // Potongan 40% dari gaji harian
                $potonganSetengah += $gajiHarian * ($setting->potongan_setengah_pct / 100);
            }

            $rincianAbsensi[] = [
                'tanggal' => $abs->tanggal->format('Y-m-d'),
                'durasi' => $durasi,
                'status' => $status
            ];
        }

        $totalInsentif = round($insentifJamLebih + $insentifLembur);
        $totalPotongan = round($potonganSetengah);

        // 4. Hitung Total Gaji Bersih
        $totalGaji = $gajiPokok + $totalTunjangan + $totalInsentif - $totalPotongan;

        return [
            'kategori_masa_kerja' => $kategoriMasaKerja,
            'gaji_pokok' => $gajiPokok,
            'tunjangan_jabatan' => $tunjanganJabatan,
            'tunjangan_transport' => $tunjanganTransport,
            'tunjangan_makan' => $tunjanganMakan,
            'tunjangan' => $totalTunjangan,
            'insentif_lembur' => $totalInsentif, // disatukan sesuai kolom tabel
            'potongan' => $totalPotongan,
            'total_gaji' => max(0, $totalGaji),
            'hari_hadir' => $hariHadir,
            'hari_setengah' => $hariSetengah,
            'jam_lebih' => $jamLebih,
            'jam_lembur' => $jamLembur,
            'rincian' => [
                'kategori_masa_kerja' => $kategoriMasaKerja,
                'gaji_harian' => round($gajiHarian),
                'insentif_jam_lebih' => round($insentifJamLebih),
                'insentif_lembur_resmi' => round($insentifLembur),
                'potongan_setengah' => round($potonganSetengah),
                'absensi' => $rincianAbsensi
            ]
        ];
    }

    /**
     * Generate atau update record KomponenGaji di database.
     */
    public function generate(Karyawan $karyawan, string $periode): KomponenGaji
    {
        return DB::transaction(function () use ($karyawan, $periode) {
            $data = $this->calculate($karyawan, $periode);

            return KomponenGaji::updateOrCreate(
                [
                    'id_karyawan' => $karyawan->id,
                    'periode' => $periode,
                ],
                [
                    'gaji_pokok' => $data['gaji_pokok'],
                    'tunjangan' => $data['tunjangan'],
                    'insentif_lembur' => $data['insentif_lembur'],
                    'potongan' => $data['potongan'],
                    'total_gaji' => $data['total_gaji'],
                    'hari_hadir' => $data['hari_hadir'],
                    'hari_setengah' => $data['hari_setengah'],
                    'jam_lebih' => $data['jam_lebih'],
                    'jam_lembur' => $data['jam_lembur'],
                    'rincian' => $data['rincian'],
                    'status' => 'draft',
                ]
            );
        });
    }
}
