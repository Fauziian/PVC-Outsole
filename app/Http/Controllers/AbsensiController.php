<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

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
        $absensi = Absensi::where('tanggal', $tanggal->toDateString())
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
                    'status_kehadiran' => $abs->status_kehadiran,
                    'keterangan' => $abs->keterangan,
                ] : null,
            ];
        }

        // Statistik Kehadiran Tanggal Terkait
        $stats = [
            'total' => count($karyawanList),
            'penuh' => Absensi::where('tanggal', $tanggal->toDateString())->where('status_kehadiran', 'Penuh')->count(),
            'jam_lebih' => Absensi::where('tanggal', $tanggal->toDateString())->where('status_kehadiran', 'Jam Lebih')->count(),
            'lembur' => Absensi::where('tanggal', $tanggal->toDateString())->where('status_kehadiran', 'Lembur')->count(),
            'setengah_hari' => Absensi::where('tanggal', $tanggal->toDateString())->where('status_kehadiran', 'Setengah Hari')->count(),
            'absen' => count($karyawanList) - Absensi::where('tanggal', $tanggal->toDateString())->count(),
        ];

        return Inertia::render('Attendance/Index', [
            'rows' => $rows,
            'stats' => $stats,
            'tanggal' => $tanggal->toDateString(),
            'formatted_tanggal' => $tanggal->isoFormat('D MMMM Y'),
        ]);
    }

    /**
     * Simpan atau update data kehadiran harian karyawan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_karyawan' => ['required', 'exists:karyawan,id'],
            'tanggal' => ['required', 'date'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_keluar' => ['required', 'date_format:H:i', 'after:jam_masuk'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $tanggal = $validated['tanggal'];
        $masuk = Carbon::parse($validated['jam_masuk']);
        $keluar = Carbon::parse($validated['jam_keluar']);

        // Hitung total jam kerja kotor (desimal)
        $diffMinutes = $masuk->diffInMinutes($keluar);
        $durasiJam = round($diffMinutes / 60, 2);

        // Pengurangan jam istirahat otomatis (misal dikurangi 1 jam jika melewati jam makan siang)
        // Demi kesederhanaan, mari gunakan durasi riil kerja yang terhitung.
        // Tentukan status kehadiran otomatis sesuai aturan bisnis proposal:
        // - durasi_jam < 8 → Setengah Hari
        // - durasi_jam = 8 → Penuh (Normal)
        // - 8 < durasi_jam <= 12 → Jam Lebih
        // - durasi_jam > 15 → Lembur resmi (Shift ganda / ekstra)
        // - di antara 12 dan 15 jam dianggap Penuh / Jam Lebih maksimal
        $status = 'Penuh';
        if ($durasiJam < 8.0) {
            $status = 'Setengah Hari';
        } elseif ($durasiJam > 8.0 && $durasiJam <= 12.0) {
            $status = 'Jam Lebih';
        } elseif ($durasiJam > 15.0) {
            $status = 'Lembur';
        } else {
            $status = 'Penuh';
        }

        Absensi::updateOrCreate(
            [
                'id_karyawan' => $validated['id_karyawan'],
                'tanggal' => $tanggal,
            ],
            [
                'jam_masuk' => $validated['jam_masuk'],
                'jam_keluar' => $validated['jam_keluar'],
                'durasi_jam' => $durasiJam,
                'status_kehadiran' => $status,
                'keterangan' => $validated['keterangan'] ?? null,
            ]
        );

        return redirect()->back()->with('success', 'Data kehadiran berhasil dicatat.');
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
