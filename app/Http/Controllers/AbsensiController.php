<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

class AbsensiController extends Controller
{
    /**
     * Tampilkan daftar kehadiran karyawan.
     */
    public function index(Request $request): Response
    {
        $tanggalStr = $request->input('tanggal', now()->toDateString());
        $tanggal = Carbon::parse($tanggalStr);

        // Ambil semua karyawan aktif
        $karyawanList = Karyawan::where('is_active', true)->orderBy('nama', 'asc')->get();

        // Ambil data absensi pada tanggal tersebut
        $absensi = Absensi::whereDate('tanggal', $tanggal->toDateString())
            ->get()
            ->keyBy('id_karyawan');

        // Satukan data untuk dikirim ke frontend
        $rows = [];
        foreach ($karyawanList as $k) {
            $abs = $absensi->get($k->id);
            $rows[] = [
                'id_karyawan' => $k->id,
                'nama' => $k->nama,
                'jabatan' => $k->jabatan,
                'absensi' => $abs ? [
                    'id' => $abs->id,
                    'jam_masuk' => substr($abs->jam_masuk, 0, 5),
                    'jam_keluar' => substr($abs->jam_keluar, 0, 5),
                    'durasi_jam' => floatval($abs->durasi_jam),
                    'shift' => $abs->shift,
                    'jam_normal' => floatval($abs->jam_normal),
                    'jam_lembur' => floatval($abs->jam_lembur),
                    'sudah_pulang' => $abs->sudah_pulang,
                    'status_kehadiran' => $abs->status_kehadiran,
                    'keterangan' => $abs->keterangan,
                ] : null,
            ];
        }

        // Statistik Kehadiran Tanggal Terkait
        $stats = [
            'total' => count($karyawanList),
            'penuh' => Absensi::whereDate('tanggal', $tanggal->toDateString())->where('sudah_pulang', true)->count(),
            'jam_lebih' => Absensi::whereDate('tanggal', $tanggal->toDateString())->where('status_kehadiran', 'Lembur')->count(),
            'lembur' => Absensi::whereDate('tanggal', $tanggal->toDateString())->sum('jam_lembur'),
            'setengah_hari' => 0,
            'absen' => count($karyawanList) - Absensi::whereDate('tanggal', $tanggal->toDateString())->count(),
        ];

        return Inertia::render('Attendance/Index', [
            'rows' => $rows,
            'stats' => $stats,
            'tanggal' => $tanggal->toDateString(),
            'formatted_tanggal' => $tanggal->isoFormat('D MMMM Y'),
        ]);
    }

    /** Catat waktu masuk sekarang; waktu pulang dipilih kemudian oleh HR. */
    public function checkIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_karyawan' => ['required', 'exists:karyawan,id'],
            'tanggal' => ['required', 'date'],
        ]);

        $jamMasuk = now()->format('H:i');
        $sudahAda = Absensi::where('id_karyawan', $validated['id_karyawan'])
            ->whereDate('tanggal', $validated['tanggal'])
            ->first();

        if ($sudahAda) {
            return redirect()->back()->with('success', 'Karyawan ini sudah tercatat hadir. Silakan klik Pulang untuk memilih jam kerja.');
        }

        $dataBaru = [
            'jam_masuk' => $jamMasuk,
            'jam_keluar' => $jamMasuk,
            'durasi_jam' => 0,
            'jam_normal' => 0,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Setengah Hari',
            'sudah_pulang' => false,
        ];

        try {
            $absensi = Absensi::create([
                'id_karyawan' => $validated['id_karyawan'],
                'tanggal' => $validated['tanggal'],
                ...$dataBaru,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dua klik yang sangat cepat dapat tiba bersamaan. Data pertama
            // sudah valid; ambil saja agar pengguna tidak menerima error 500.
            $absensi = Absensi::where('id_karyawan', $validated['id_karyawan'])
                ->whereDate('tanggal', $validated['tanggal'])
                ->firstOrFail();
        }

        $pesan = $absensi->wasRecentlyCreated
            ? 'Hadir / jam masuk berhasil dicatat. Klik Pulang saat pekerjaan selesai.'
            : 'Karyawan ini sudah tercatat hadir. Silakan klik Pulang untuk memilih jam kerja.';

        return redirect()->back()->with('success', $pesan);
    }

    /** Selesaikan absensi dengan memilih total durasi kerja 8--15 jam. */
    public function checkOut(Request $request, Absensi $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'durasi_jam' => ['required', 'integer', 'between:8,15'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($attendance->sudah_pulang) {
            return redirect()->back()->with('error', 'Jam pulang karyawan ini sudah dicatat.');
        }

        $durasiJam = (int) $validated['durasi_jam'];
        $jamMasuk = Carbon::createFromFormat('Y-m-d H:i', $attendance->tanggal->format('Y-m-d') . ' ' . substr($attendance->jam_masuk, 0, 5));
        $jamPulang = $jamMasuk->copy()->addHours($durasiJam);
        $lembur = $durasiJam >= 14;

        $attendance->update([
            'jam_keluar' => $jamPulang->format('H:i'),
            'durasi_jam' => $durasiJam,
            'jam_normal' => min($durasiJam, 13),
            'jam_lembur' => max($durasiJam - 13, 0),
            'status_kehadiran' => $lembur ? 'Lembur' : 'Penuh',
            'keterangan' => $validated['keterangan'] ?? null,
            'sudah_pulang' => true,
        ]);

        return redirect()->back()->with('success', $lembur ? 'Jam pulang dan lembur berhasil dicatat.' : 'Jam pulang berhasil dicatat.');
    }

    /**
     * Hapus record absensi karyawan pada tanggal tertentu.
     */
    public function destroy(Absensi $attendance): RedirectResponse
    {
        $attendance->delete();

        return redirect()->back()->with('success', 'Data kehadiran berhasil dihapus.');
    }
}
