<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\KomponenGaji;
use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Tampilkan halaman filter laporan.
     */
    public function index(Request $request): Response
    {
        $role = $request->user()->role;
        
        $periodes = KomponenGaji::select('periode')
            ->groupBy('periode')
            ->orderBy('periode', 'desc')
            ->pluck('periode')
            ->toArray();

        return Inertia::render('Reports/Index', [
            'periodes' => $periodes,
            'role' => $role,
        ]);
    }

    /**
     * Export Laporan Penggajian Bulanan ke Excel (Format XLSX).
     */
    public function exportPayrollExcel(Request $request): StreamedResponse
    {
        $periode = $request->input('periode', now()->format('Y-m'));

        $payroll = KomponenGaji::with('karyawan')
            ->where('periode', $periode)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Penggajian');

        // Header
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI PENGGAJIAN SUMBER PVC OUTSOLE TALI JEPIT');
        $sheet->setCellValue('A2', 'PERIODE: ' . $periode);
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        // Kolom
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Nama Karyawan');
        $sheet->setCellValue('C4', 'Jabatan');
        $sheet->setCellValue('D4', 'Masa Kerja');
        $sheet->setCellValue('E4', 'Tarif / Jam');
        $sheet->setCellValue('F4', 'Jam Normal');
        $sheet->setCellValue('G4', 'Upah Normal');
        $sheet->setCellValue('H4', 'Jam Lembur');
        $sheet->setCellValue('I4', 'Upah Lembur');
        $sheet->setCellValue('J4', 'Total Gaji');

        $rowNum = 5;
        foreach ($payroll as $index => $p) {
            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $p->karyawan->nama);
            $sheet->setCellValue('C' . $rowNum, $p->karyawan->jabatan);
            $sheet->setCellValue('D' . $rowNum, $p->karyawan->kategori_masa_kerja === 'B' ? '>= 5 Tahun' : '< 5 Tahun');
            $sheet->setCellValue('E' . $rowNum, $p->tarif_per_jam);
            $sheet->setCellValue('F' . $rowNum, $p->total_jam_normal);
            $sheet->setCellValue('G' . $rowNum, $p->gaji_pokok);
            $sheet->setCellValue('H' . $rowNum, $p->jam_lembur);
            $sheet->setCellValue('I' . $rowNum, $p->insentif_lembur);
            $sheet->setCellValue('J' . $rowNum, $p->total_gaji);
            $rowNum++;
        }

        // Total
        $sheet->setCellValue('D' . $rowNum, 'TOTAL');
        $sheet->setCellValue('F' . $rowNum, "=SUM(F5:F" . ($rowNum - 1) . ")");
        $sheet->setCellValue('G' . $rowNum, "=SUM(G5:G" . ($rowNum - 1) . ")");
        $sheet->setCellValue('H' . $rowNum, "=SUM(H5:H" . ($rowNum - 1) . ")");
        $sheet->setCellValue('I' . $rowNum, "=SUM(I5:I" . ($rowNum - 1) . ")");
        $sheet->setCellValue('J' . $rowNum, "=SUM(J5:J" . ($rowNum - 1) . ")");

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Laporan-Gaji-' . $periode . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Laporan Stok Gudang Bulanan ke Excel.
     */
    public function exportStockExcel(Request $request): StreamedResponse
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $barangs = BarangPvc::all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Stok');

        $sheet->setCellValue('A1', 'LAPORAN MUTASI STOK BAHAN BAKU SUMBER PVC OUTSOLE TALI JEPIT');
        $sheet->setCellValue('A2', 'BULAN: ' . $bulan);
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Kode Barang');
        $sheet->setCellValue('C4', 'Nama Bahan Baku');
        $sheet->setCellValue('D4', 'Stok Minimum');
        $sheet->setCellValue('E4', 'Total Masuk');
        $sheet->setCellValue('F4', 'Total Keluar');
        $sheet->setCellValue('G4', 'Stok Saat Ini');

        $rowNum = 5;
        foreach ($barangs as $index => $b) {
            // Hitung mutasi masuk / keluar untuk bulan terkait
            $totalMasuk = BarangMasuk::where('id_barang', $b->id)
                ->whereYear('tanggal', substr($bulan, 0, 4))
                ->whereMonth('tanggal', substr($bulan, 5, 2))
                ->sum('jumlah');

            $totalKeluar = BarangKeluar::where('id_barang', $b->id)
                ->whereYear('tanggal', substr($bulan, 0, 4))
                ->whereMonth('tanggal', substr($bulan, 5, 2))
                ->sum('jumlah');

            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $b->kode_barang);
            $sheet->setCellValue('C' . $rowNum, $b->nama_barang);
            $sheet->setCellValue('D' . $rowNum, $b->stok_minimum);
            $sheet->setCellValue('E' . $rowNum, $totalMasuk);
            $sheet->setCellValue('F' . $rowNum, $totalKeluar);
            $sheet->setCellValue('G' . $rowNum, $b->stok_saat_ini);
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Laporan-Stok-' . $bulan . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Download Laporan Rekap Kehadiran PDF.
     */
    public function downloadAttendancePdf(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $karyawans = Karyawan::where('is_active', true)->get();

        $rekap = [];
        foreach ($karyawans as $emp) {
            $abs = Absensi::where('id_karyawan', $emp->id)
                ->whereYear('tanggal', substr($bulan, 0, 4))
                ->whereMonth('tanggal', substr($bulan, 5, 2))
                ->get();

            $rekap[] = [
                'nama' => $emp->nama,
                'jabatan' => $emp->jabatan,
                'penuh' => $abs->where('status_kehadiran', 'Penuh')->count(),
                'jam_lebih' => $abs->where('status_kehadiran', 'Jam Lebih')->count(),
                'lembur' => $abs->where('status_kehadiran', 'Lembur')->count(),
                'setengah_hari' => $abs->where('status_kehadiran', 'Setengah Hari')->count(),
                'total_hadir' => $abs->count(),
            ];
        }

        $pdf = Pdf::loadView('pdf.attendance-report', [
            'rekap' => $rekap,
            'bulan' => Carbon::parse($bulan . '-01')->isoFormat('MMMM Y'),
            'tanggal_cetak' => now()->isoFormat('D MMMM Y'),
        ]);

        return $pdf->download("Laporan-Kehadiran-{$bulan}.pdf");
    }
}
