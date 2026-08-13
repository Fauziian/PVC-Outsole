<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\KomponenGaji;
use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Tampilkan Dashboard utama berdasarkan Role Pengguna.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role;

        // Data yang dikirim disesuaikan dengan role
        $data = [
            'role' => $role,
            'stats' => [],
        ];

        // 1. Data Umum untuk Dashboard
        if ($role === 'admin') {
            $data['stats'] = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'recent_logins' => User::whereNotNull('last_login_at')
                    ->orderBy('last_login_at', 'desc')
                    ->take(5)
                    ->get(['nama', 'username', 'role', 'last_login_at']),
            ];
        } 
        
        elseif ($role === 'hr') {
            $currentMonth = Carbon::now()->format('Y-m');
            $data['stats'] = [
                'total_karyawan' => Karyawan::where('is_active', true)->count(),
                'karyawan_nonaktif' => Karyawan::where('is_active', false)->count(),
                'hadir_hari_ini' => Absensi::where('tanggal', now()->toDateString())
                    ->whereIn('status_kehadiran', ['Penuh', 'Jam Lebih', 'Lembur'])
                    ->count(),
                'setengah_hari_ini' => Absensi::where('tanggal', now()->toDateString())
                    ->where('status_kehadiran', 'Setengah Hari')
                    ->count(),
                'total_payroll_bulan_lalu' => KomponenGaji::where('periode', Carbon::now()->subMonth()->format('Y-m'))
                    ->sum('total_gaji'),
            ];

            // Rincian absensi 5 hari terakhir
            $data['recent_absensi'] = Absensi::with('karyawan:id,nama,jabatan')
                ->orderBy('tanggal', 'desc')
                ->take(5)
                ->get();
        } 
        
        elseif ($role === 'warehouse') {
            $data['stats'] = [
                'total_jenis_barang' => BarangPvc::count(),
                'stok_aman' => BarangPvc::whereRaw('stok_saat_ini > (stok_minimum * 1.3)')->count(),
                'stok_menipis' => BarangPvc::whereRaw('stok_saat_ini <= (stok_minimum * 1.3) AND stok_saat_ini > stok_minimum')->count(),
                'stok_kritis' => BarangPvc::whereRaw('stok_saat_ini <= stok_minimum')->count(),
            ];

            // Notifikasi Stok Kritis yang Belum Dibaca
            $data['notifikasi_kritis'] = NotifikasiStok::with('barangPvc')
                ->where('is_read', false)
                ->orderBy('tanggal', 'desc')
                ->take(5)
                ->get();

            // Riwayat transaksi gudang terbaru
            $data['recent_masuk'] = BarangMasuk::with('barangPvc')
                ->orderBy('tanggal', 'desc')
                ->take(3)
                ->get();
                
            $data['recent_keluar'] = BarangKeluar::with('barangPvc')
                ->orderBy('tanggal', 'desc')
                ->take(3)
                ->get();
        } 
        
        elseif ($role === 'management') {
            // Ringkasan Keuangan (Bulan Lalu)
            $bulanLalu = Carbon::now()->subMonth()->format('Y-m');
            $data['stats'] = [
                'total_karyawan_aktif' => Karyawan::where('is_active', true)->count(),
                'total_gaji_bulan_lalu' => KomponenGaji::where('periode', $bulanLalu)->sum('total_gaji'),
                'total_insentif_bulan_lalu' => KomponenGaji::where('periode', $bulanLalu)->sum('insentif_lembur'),
                'total_potongan_bulan_lalu' => KomponenGaji::where('periode', $bulanLalu)->sum('potongan'),
                'total_stok_pvc' => BarangPvc::sum('stok_saat_ini'),
                'total_stok_kritis' => BarangPvc::whereRaw('stok_saat_ini <= stok_minimum')->count(),
            ];

            // Grafik tren pengeluaran gaji (6 bulan terakhir)
            $trenGaji = DB::table('komponen_gaji')
                ->select('periode', DB::raw('SUM(total_gaji) as total'))
                ->groupBy('periode')
                ->orderBy('periode', 'desc')
                ->take(6)
                ->get()
                ->reverse()
                ->values();
                
            $data['tren_gaji'] = $trenGaji;

            // Grafik komposisi stok saat ini
            $data['stok_pvc'] = BarangPvc::orderBy('stok_saat_ini', 'desc')
                ->take(6)
                ->get(['nama_barang', 'kode_barang', 'stok_saat_ini', 'stok_minimum', 'satuan']);
        }

        // Shared logs aktivitas
        $data['aktifitas_terbaru'] = $this->getRecentActivities();

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Dapatkan log aktivitas terbaru secara global untuk feed dashboard.
     */
    private function getRecentActivities(): array
    {
        $activities = [];

        // 1. Aktivitas Barang Masuk
        $masuk = BarangMasuk::with('barangPvc')->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($masuk as $m) {
            $activities[] = [
                'id' => 'BM-' . $m->id,
                'type' => 'stock',
                'text' => "Penerimaan {$m->jumlah} {$m->barangPvc->satuan} {$m->barangPvc->nama_barang} dari {$m->pemasok}",
                'time' => $m->created_at->diffForHumans(),
                'timestamp' => $m->created_at->timestamp,
            ];
        }

        // 2. Aktivitas Barang Keluar
        $keluar = BarangKeluar::with('barangPvc')->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($keluar as $k) {
            $activities[] = [
                'id' => 'BK-' . $k->id,
                'type' => 'stock',
                'text' => "Pengeluaran {$k->jumlah} {$k->barangPvc->satuan} {$k->barangPvc->nama_barang} untuk {$k->tujuan_penggunaan}",
                'time' => $k->created_at->diffForHumans(),
                'timestamp' => $k->created_at->timestamp,
            ];
        }

        // 3. Aktivitas Karyawan Baru
        $karyawan = Karyawan::orderBy('created_at', 'desc')->take(2)->get();
        foreach ($karyawan as $emp) {
            $activities[] = [
                'id' => 'EMP-' . $emp->id,
                'type' => 'employee',
                'text' => "Pendaftaran Karyawan Baru: {$emp->nama} sebagai {$emp->jabatan}",
                'time' => $emp->created_at->diffForHumans(),
                'timestamp' => $emp->created_at->timestamp,
            ];
        }

        // 4. Notifikasi Stok Baru
        $notif = NotifikasiStok::with('barangPvc')->orderBy('created_at', 'desc')->take(2)->get();
        foreach ($notif as $n) {
            $activities[] = [
                'id' => 'NTF-' . $n->id,
                'type' => 'alert',
                'text' => $n->pesan,
                'time' => $n->created_at->diffForHumans(),
                'timestamp' => $n->created_at->timestamp,
            ];
        }

        // Urutkan berdasarkan waktu terbaru
        usort($activities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return array_slice($activities, 0, 6);
    }
}
