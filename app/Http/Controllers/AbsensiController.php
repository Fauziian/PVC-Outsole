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
                    'shift' => $abs->shift,
                    'jam_normal' => floatval($abs->jam_normal),
                    'jam_lembur' => floatval($abs->jam_lembur),
                    'status_kehadiran' => $abs->status_kehadiran,
                    'keterangan' => $abs->keterangan,
                ] : null,
            ];
        }

        // Statistik Kehadiran Tanggal Terkait
        $stats = [
            'total' => count($karyawanList),
            'penuh' => Absensi::where('tanggal', $tanggal->toDateString())->where('jam_normal', '>=', 8)->count(),
            'jam_lebih' => Absensi::where('tanggal', $tanggal->toDateString())->where('jam_lembur', '>', 0)->count(),
            'lembur' => Absensi::where('tanggal', $tanggal->toDateString())->sum('jam_lembur'),
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
            'jam_keluar' => ['required', 'date_format:H:i'],
            'shift' => ['required', 'in:Pagi,Sore,Malam'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $tanggal = $validated['tanggal'];
        $masuk = Carbon::createFromFormat('Y-m-d H:i', "{$tanggal} {$validated['jam_masuk']}");
        $keluar = Carbon::createFromFormat('Y-m-d H:i', "{$tanggal} {$validated['jam_keluar']}");

        // Shift malam dapat berakhir pada hari berikutnya. Jam keluar yang sama
        // atau lebih awal dari jam masuk berarti melewati tengah malam.
        if ($keluar->lessThanOrEqualTo($masuk)) {
            $keluar->addDay();
        }

        // Hitung total jam kerja kotor (desimal)
        $diffMinutes = $masuk->diffInMinutes($keluar);
        $durasiJam = round($diffMinutes / 60, 2);

        if ($durasiJam > 15) {
            return redirect()->back()->withErrors([
                'jam_keluar' => 'Durasi kerja maksimal 15 jam per catatan. Pisahkan apabila ada kesalahan pencatatan.',
            ])->withInput();
        }

        // Delapan jam pertama selalu jam normal. Jam berikutnya selalu lembur,
        // termasuk pada shift sore/malam atau ketika melewati tengah malam.
        $jamNormal = min($durasiJam, 8);
        $jamLembur = max($durasiJam - 8, 0);
        $status = 'Penuh';
        if ($durasiJam < 8.0) {
            $status = 'Setengah Hari';
        } elseif ($jamLembur > 0) {
            $status = 'Jam Lebih';
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
                'shift' => $validated['shift'],
                'jam_normal' => $jamNormal,
                'jam_lembur' => $jamLembur,
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
