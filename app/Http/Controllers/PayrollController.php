<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\KomponenGaji;
use App\Models\SettingGaji;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PayrollController extends Controller
{
    protected PayrollCalculationService $payrollService;

    public function __construct(PayrollCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * Tampilkan riwayat rekap penggajian bulanan.
     */
    public function index(Request $request): Response
    {
        // Ambil daftar periode yang ada
        $periodes = KomponenGaji::select('periode')
            ->groupBy('periode')
            ->orderBy('periode', 'desc')
            ->pluck('periode')
            ->toArray();

        $selectedPeriode = $request->input('periode', !empty($periodes) ? $periodes[0] : now()->format('Y-m'));

        // Query komponen gaji pada periode terpilih
        $payrollList = KomponenGaji::with('karyawan')
            ->where('periode', $selectedPeriode)
            ->get();

        $totalGajiClean = $payrollList->sum('total_gaji');
        $totalInsentif = $payrollList->sum('insentif_lembur');
        $totalPotongan = $payrollList->sum('potongan');

        return Inertia::render('Payroll/Index', [
            'periodes' => $periodes,
            'selected_periode' => $selectedPeriode,
            'payroll_list' => $payrollList,
            'stats' => [
                'total_bersih' => $totalGajiClean,
                'total_insentif' => $totalInsentif,
                'total_potongan' => $totalPotongan,
                'count' => $payrollList->count(),
            ],
        ]);
    }

    /**
     * Proses/kalkulasi ulang gaji massal untuk seluruh karyawan pada periode tertentu.
     */
    public function calculatePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode' => ['required', 'regex:/^\d{4}-\d{2}$/'], // format YYYY-MM
        ]);

        $periode = $validated['periode'];

        // Ambil semua karyawan aktif
        $karyawans = Karyawan::where('is_active', true)->get();

        if ($karyawans->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada karyawan aktif yang terdaftar.');
        }

        foreach ($karyawans as $emp) {
            $this->payrollService->generate($emp, $periode);
        }

        return redirect()->route('payroll.index', ['periode' => $periode])
            ->with('success', "Proses penggajian periode {$periode} berhasil digenerate.");
    }

    /**
     * Tampilkan detail preview slip gaji karyawan tertentu.
     */
    public function show(KomponenGaji $payroll): Response
    {
        $payroll->load('karyawan');
        $setting = SettingGaji::aktif();

        return Inertia::render('Payroll/Show', [
            'payroll' => $payroll,
            'setting' => $setting,
        ]);
    }

    /**
     * Finalisasi status slip gaji (draft -> final).
     */
    public function finalize(KomponenGaji $payroll): RedirectResponse
    {
        $payroll->update(['status' => 'final']);

        return redirect()->back()->with('success', 'Status slip gaji berhasil diselesaikan (Final).');
    }

    /**
     * Download Slip Gaji PDF secara live.
     */
    public function downloadPdf(KomponenGaji $payroll)
    {
        $payroll->load('karyawan');
        $setting = SettingGaji::aktif();

        $pdf = Pdf::loadView('pdf.salary-slip', [
            'payroll' => $payroll,
            'setting' => $setting,
            'tanggal_cetak' => now()->isoFormat('D MMMM Y'),
        ]);

        // Kustomisasi layout slip kertas kecil landscape
        $pdf->setPaper([0, 0, 612, 396], 'landscape'); // Ukuran kustom slip gaji

        return $pdf->download("Slip-Gaji-{$payroll->karyawan->nama}-{$payroll->periode}.pdf");
    }

    /**
     * Tampilkan halaman Pengaturan Gaji (Konfigurasi).
     */
    public function settings(): Response
    {
        $settings = SettingGaji::orderBy('created_at', 'desc')->get();
        $activeSetting = SettingGaji::aktif();

        return Inertia::render('Payroll/Settings', [
            'settings' => $settings,
            'active_setting' => $activeSetting,
        ]);
    }

    /**
     * Update atau buat konfigurasi setting gaji baru.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gaji_pokok_kategori_a' => ['required', 'integer', 'min:0'],
            'gaji_pokok_kategori_b' => ['required', 'integer', 'min:0'],
            'insentif_jam_lebih_pct' => ['required', 'numeric', 'between:0,100'],
            'insentif_lembur_pct' => ['required', 'numeric', 'between:0,100'],
            'potongan_setengah_pct' => ['required', 'numeric', 'between:0,100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        // Nonaktifkan config yang lama
        SettingGaji::where('is_active', true)->update(['is_active' => false]);

        // Buat config baru yang aktif
        $validated['is_active'] = true;
        SettingGaji::create($validated);

        return redirect()->back()
            ->with('success', 'Konfigurasi parameter penggajian berhasil diperbarui.');
    }
}
