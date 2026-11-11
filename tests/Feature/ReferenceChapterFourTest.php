<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\BarangPvc;
use App\Models\Karyawan;
use App\Models\KomponenGaji;
use App\Models\NotifikasiStok;
use App\Models\SettingGaji;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReferenceChapterFourTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $username, bool $active = true, ?string $nama = null): User
    {
        return User::create([
            'nama' => $nama ?? match ($role) {
                'admin' => 'Admin Pengujian',
                'hr' => 'Akun Pengujian HR',
                default => 'Staf Gudang Pengujian',
            },
            'username' => $username,
            'password' => 'password',
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function seedDefaultTarif(int $a = 12000, int $b = 17000): SettingGaji
    {
        return SettingGaji::create([
            'gaji_pokok_kategori_a' => 0,
            'gaji_pokok_kategori_b' => 0,
            'tarif_per_jam_kategori_a' => $a,
            'tarif_per_jam_kategori_b' => $b,
            'insentif_jam_lebih_pct' => 0,
            'insentif_lembur_pct' => 0,
            'potongan_setengah_pct' => 0,
            'is_active' => true,
            'keterangan' => 'Tarif pengujian Bab IV',
        ]);
    }

    private function makeProduct(string $code, string $kategori, ?string $jenis, ?string $warna, int $stock = 0, int $min = 10): BarangPvc
    {
        return BarangPvc::create([
            'nama_barang' => trim(($jenis ?? $kategori) . ($warna ? " - {$warna}" : '')),
            'kode_barang' => $code,
            'kategori' => $kategori,
            'jenis' => $jenis,
            'warna' => $warna,
            'satuan' => 'kodi',
            'stok_minimum' => $min,
            'stok_saat_ini' => $stock,
        ]);
    }

    public static function casesProvider(): array
    {
        return [
            'KU-01' => ['KU-01', 'Validasi Login Gagal'],
            'KU-02' => ['KU-02', 'Login Berhasil & Redirect'],
            'KU-03' => ['KU-03', 'Logout Sesi'],
            'KU-04' => ['KU-04', 'Tambah Akun Pengguna'],
            'KU-05' => ['KU-05', 'Ubah Pengguna & Status'],
            'KU-06' => ['KU-06', 'Reset Kata Sandi'],
            'KU-07' => ['KU-07', 'Proteksi Akun Sendiri'],
            'KU-08' => ['KU-08', 'Tambah Data Karyawan'],
            'KU-09' => ['KU-09', 'Ubah Data Karyawan'],
            'KU-10' => ['KU-10', 'Hapus Data Karyawan'],
            'KU-11' => ['KU-11', 'Catat Check-In Masuk'],
            'KU-12' => ['KU-12', 'Validasi Duplikasi Masuk'],
            'KU-13' => ['KU-13', 'Catat Check-Out Pulang'],
            'KU-14' => ['KU-14', 'Ubah Tarif Upah Jam'],
            'KU-15' => ['KU-15', 'Kalkulasi Gaji Massal'],
            'KU-16' => ['KU-16', 'Pratinjau Lembar Slip'],
            'KU-17' => ['KU-17', 'Finalisasi Status Slip'],
            'KU-18' => ['KU-18', 'Unduh Slip Gaji PDF'],
            'KU-19' => ['KU-19', 'Katalog Stok Fisik'],
            'KU-20' => ['KU-20', 'Barang Masuk Batch'],
            'KU-21' => ['KU-21', 'Barang Keluar Pelanggan'],
            'KU-22' => ['KU-22', 'Validasi Stok Tidak Cukup'],
            'KU-23' => ['KU-23', 'Notifikasi Stok Minimum'],
            'KU-24' => ['KU-24', 'Tandai Baca Notifikasi'],
            'KU-25' => ['KU-25', 'Ekspor Absensi PDF'],
            'KU-26' => ['KU-26', 'Ekspor Payroll Excel'],
            'KU-27' => ['KU-27', 'Ekspor Mutasi Stok Excel'],
            'KU-28' => ['KU-28', 'Proteksi RBAC URL Terlarang'],
            'KU-29' => ['KU-29', 'Tarif Periode Historis'],
            'KU-30' => ['KU-30', 'Pembaruan Nama dan Username'],
            'KU-31' => ['KU-31', 'Sesi Akun Nonaktif'],
            'KU-32' => ['KU-32', 'Jumlah Hadir HR'],
            'KU-33' => ['KU-33', 'Batas Akhir Bulanan'],
            'KU-34' => ['KU-34', 'Batas Akhir Mingguan'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case(string $ku, string $feature): void
    {
        $startedAt = hrtime(true);
        $beforeAssertions = \PHPUnit\Framework\Assert::getCount();
        $actualPayload = [];
        $status = 'Sesuai';
        $failureMessage = null;

        try {
            $actualPayload = match ($ku) {
                'KU-01' => $this->runKu01(),
                'KU-02' => $this->runKu02(),
                'KU-03' => $this->runKu03(),
                'KU-04' => $this->runKu04(),
                'KU-05' => $this->runKu05(),
                'KU-06' => $this->runKu06(),
                'KU-07' => $this->runKu07(),
                'KU-08' => $this->runKu08(),
                'KU-09' => $this->runKu09(),
                'KU-10' => $this->runKu10(),
                'KU-11' => $this->runKu11(),
                'KU-12' => $this->runKu12(),
                'KU-13' => $this->runKu13(),
                'KU-14' => $this->runKu14(),
                'KU-15' => $this->runKu15(),
                'KU-16' => $this->runKu16(),
                'KU-17' => $this->runKu17(),
                'KU-18' => $this->runKu18(),
                'KU-19' => $this->runKu19(),
                'KU-20' => $this->runKu20(),
                'KU-21' => $this->runKu21(),
                'KU-22' => $this->runKu22(),
                'KU-23' => $this->runKu23(),
                'KU-24' => $this->runKu24(),
                'KU-25' => $this->runKu25(),
                'KU-26' => $this->runKu26(),
                'KU-27' => $this->runKu27(),
                'KU-28' => $this->runKu28(),
                'KU-29' => $this->runKu29(),
                'KU-30' => $this->runKu30(),
                'KU-31' => $this->runKu31(),
                'KU-32' => $this->runKu32(),
                'KU-33' => $this->runKu33(),
                'KU-34' => $this->runKu34(),
                default => throw new \InvalidArgumentException("Kasus uji {$ku} tidak dikenali."),
            };
        } catch (\Throwable $e) {
            $status = 'Tidak Sesuai';
            $failureMessage = $e->getMessage();
            $this->recordEvidence($ku, $feature, $status, \PHPUnit\Framework\Assert::getCount() - $beforeAssertions, (hrtime(true) - $startedAt) / 1e9, $actualPayload, $failureMessage);
            throw $e;
        }

        $elapsed = (hrtime(true) - $startedAt) / 1e9;
        $assertions = \PHPUnit\Framework\Assert::getCount() - $beforeAssertions;
        $this->recordEvidence($ku, $feature, $status, $assertions, $elapsed, $actualPayload, $failureMessage);
    }

    private function recordEvidence(
        string $ku,
        string $feature,
        string $status,
        int $assertions,
        float $durationSeconds,
        array $actualPayload,
        ?string $failureMessage
    ): void {
        $path = getenv('EVIDENCE_RESULTS_JSON') ?: '';
        if ($path === '') {
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $data = [];
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $data[$ku] = [
            'ku' => $ku,
            'feature' => $feature,
            'status' => $status,
            'assertions' => $assertions,
            'duration' => number_format($durationSeconds, 6, '.', ''),
            'php_version' => PHP_VERSION,
            'phpunit_version' => \PHPUnit\Runner\Version::id(),
            'actual' => $actualPayload,
            'phpunit_output' => $status === 'Sesuai'
                ? "PASS - seluruh assertion pada kasus ini terpenuhi."
                : ("FAIL - " . ($failureMessage ?? 'Assertion gagal.')),
        ];

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function runKu01(): array
    {
        $this->makeUser('hr', 'uji_hr');
        $response = $this->post('/login', [
            'username' => 'uji_hr',
            'password' => 'salah-total',
        ]);
        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();

        return [
            'errors' => ['username'],
            'guest' => true,
        ];
    }

    private function runKu02(): array
    {
        $this->makeUser('hr', 'uji_hr');
        $response = $this->post('/login', [
            'username' => 'uji_hr',
            'password' => 'password',
        ]);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        return [
            'status' => $response->getStatusCode(),
            'redirect' => '/dashboard',
            'authenticated' => true,
        ];
    }

    private function runKu03(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $logout = $this->actingAs($hr)->post('/logout');
        $logout->assertRedirect('/');
        $this->assertGuest();

        $root = $this->get('/');
        $root->assertRedirect('/login');

        return [
            'logout_status' => $logout->getStatusCode(),
            'logout_redirect' => '/',
            'guest' => true,
            'root_redirect' => '/login',
        ];
    }

    private function runKu04(): array
    {
        $admin = $this->makeUser('admin', 'admin_uji');
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'nama' => 'HR Baru Uji',
            'username' => 'hr_baru',
            'password' => 'Secret123!',
            'role' => 'hr',
            'is_active' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $created = User::where('username', 'hr_baru')->first();
        $this->assertNotNull($created);
        $this->assertTrue((bool) $created->is_active);
        $this->assertTrue(Hash::check('Secret123!', $created->password));

        return [
            'username' => $created->username,
            'role' => $created->role,
            'is_active' => (bool) $created->is_active,
            'password_hashed' => true,
        ];
    }

    private function runKu05(): array
    {
        $admin = $this->makeUser('admin', 'admin_uji');
        $hr = $this->makeUser('hr', 'hr_target', true, 'Nama Awal HR');
        $response = $this->actingAs($admin)->put(route('users.update', $hr->id), [
            'nama' => 'Nama Diubah HR',
            'username' => 'hr_target',
            'role' => 'hr',
            'is_active' => false,
        ]);
        $response->assertSessionHasNoErrors();
        $hr->refresh();
        $this->assertSame('Nama Diubah HR', $hr->nama);
        $this->assertFalse((bool) $hr->is_active);

        return [
            'nama' => $hr->nama,
            'is_active' => (bool) $hr->is_active,
        ];
    }

    private function runKu06(): array
    {
        $admin = $this->makeUser('admin', 'admin_uji');
        $hr = $this->makeUser('hr', 'hr_reset');
        $response = $this->actingAs($admin)->put(route('users.reset-password', $hr->id), [
            'password' => 'New12345!',
        ]);
        $response->assertSessionHasNoErrors();
        $hr->refresh();
        $this->assertTrue(Hash::check('New12345!', $hr->password));

        return [
            'username' => $hr->username,
            'new_password_verified' => true,
        ];
    }

    private function runKu07(): array
    {
        $admin = $this->makeUser('admin', 'admin_self');
        $deactivate = $this->actingAs($admin)->put(route('users.update', $admin->id), [
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'is_active' => false,
        ]);
        $deactivate->assertSessionHas('error');

        $delete = $this->actingAs($admin)->delete(route('users.destroy', $admin->id));
        $delete->assertSessionHas('error');

        $fresh = $admin->fresh();
        $this->assertNotNull($fresh);
        $this->assertTrue((bool) $fresh->is_active);

        return [
            'exists' => true,
            'is_active' => (bool) $fresh->is_active,
            'self_protection' => true,
        ];
    }

    private function runKu08(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $response = $this->actingAs($hr)->post(route('employees.store'), [
            'nama' => 'Karyawan Baru Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $emp = Karyawan::where('nama', 'Karyawan Baru Uji')->first();
        $this->assertNotNull($emp);
        $this->assertSame('A', $emp->kategori_masa_kerja);

        return [
            'nama' => $emp->nama,
            'jabatan' => $emp->jabatan,
            'kategori_masa_kerja' => $emp->kategori_masa_kerja,
        ];
    }

    private function runKu09(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Lama',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($hr)->put(route('employees.update', $emp->id), [
            'nama' => 'Karyawan Senior Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2020-01-01',
            'kategori_masa_kerja' => 'B',
            'is_active' => true,
        ]);
        $response->assertSessionHasNoErrors();
        $emp->refresh();
        $this->assertSame('Karyawan Senior Uji', $emp->nama);
        $this->assertSame('2020-01-01', $emp->tanggal_masuk->format('Y-m-d'));
        $this->assertSame('B', $emp->kategori_masa_kerja);

        return [
            'nama' => $emp->nama,
            'tanggal_masuk' => $emp->tanggal_masuk->format('Y-m-d'),
            'kategori_masa_kerja' => $emp->kategori_masa_kerja,
        ];
    }

    private function runKu10(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Hapus Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);
        $id = $emp->id;
        $response = $this->actingAs($hr)->delete(route('employees.destroy', $id));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('karyawan', ['id' => $id]);

        return [
            'deleted_id' => $id,
            'exists_in_db' => false,
        ];
    }

    private function runKu11(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Absen Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
        ]);
        $response->assertSessionHasNoErrors();
        $absensi = Absensi::where('id_karyawan', $emp->id)->whereDate('tanggal', '2026-09-10')->get();
        $this->assertCount(1, $absensi);
        $this->assertSame('08:00', substr($absensi->first()->jam_masuk, 0, 5));
        $this->assertFalse((bool) $absensi->first()->sudah_pulang);

        return [
            'count' => 1,
            'jam_masuk' => '08:00',
            'sudah_pulang' => false,
        ];
    }

    private function runKu12(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Duplikasi',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        $r1 = $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
        ]);
        $r2 = $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
        ]);

        $r1->assertSessionHasNoErrors();
        $r2->assertSessionHasNoErrors();
        $this->assertLessThan(500, $r2->getStatusCode());
        $count = Absensi::where('id_karyawan', $emp->id)->whereDate('tanggal', '2026-09-10')->count();
        $this->assertSame(1, $count);

        return [
            'http_status' => $r2->getStatusCode(),
            'attendance_count' => $count,
        ];
    }

    private function runKu13(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Pulang Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        $absensi = Absensi::create([
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '08:00',
            'durasi_jam' => 0,
            'jam_normal' => 0,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Setengah Hari',
            'sudah_pulang' => false,
        ]);

        $response = $this->actingAs($hr)->put(route('attendance.check-out', $absensi->id), [
            'durasi_jam' => 14,
        ]);
        $response->assertSessionHasNoErrors();
        $absensi->refresh();
        $this->assertSame('22:00', substr($absensi->jam_keluar, 0, 5));
        $this->assertEquals(13, (float) $absensi->jam_normal);
        $this->assertEquals(1, (float) $absensi->jam_lembur);
        $this->assertTrue((bool) $absensi->sudah_pulang);

        return [
            'jam_keluar' => substr($absensi->jam_keluar, 0, 5),
            'jam_normal' => (float) $absensi->jam_normal,
            'jam_lembur' => (float) $absensi->jam_lembur,
            'status_kehadiran' => $absensi->status_kehadiran,
        ];
    }

    private function runKu14(): array
    {
        $admin = $this->makeUser('admin', 'admin_uji');
        $this->seedDefaultTarif(10000, 15000);

        $response = $this->actingAs($admin)->post(route('payroll.settings.update'), [
            'tarif_per_jam_kategori_a' => 12000,
            'tarif_per_jam_kategori_b' => 17000,
            'keterangan' => 'Tarif standar baru',
        ]);
        $response->assertSessionHasNoErrors();
        $activeCount = SettingGaji::where('is_active', true)->count();
        $active = SettingGaji::aktif();
        $this->assertSame(1, $activeCount);
        $this->assertSame(12000, $active->tarif_per_jam_kategori_a);
        $this->assertSame(17000, $active->tarif_per_jam_kategori_b);

        return [
            'active_count' => $activeCount,
            'tarif_a' => $active->tarif_per_jam_kategori_a,
            'tarif_b' => $active->tarif_per_jam_kategori_b,
        ];
    }

    private function runKu15(): array
    {
        $this->seedDefaultTarif(12000, 17000);
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Gaji Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        foreach (['2026-09-10' => 8, '2026-09-11' => 10, '2026-09-12' => 12] as $tgl => $durasi) {
            Absensi::create([
                'id_karyawan' => $emp->id,
                'tanggal' => $tgl,
                'jam_masuk' => '08:00',
                'jam_keluar' => sprintf('%02d:00', 8 + $durasi),
                'durasi_jam' => $durasi,
                'jam_normal' => $durasi,
                'jam_lembur' => 0,
                'status_kehadiran' => 'Penuh',
                'sudah_pulang' => true,
            ]);
        }

        $response = $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => '2026-09']);
        $response->assertSessionHasNoErrors();
        $slip = KomponenGaji::where('id_karyawan', $emp->id)->where('periode', '2026-09')->first();
        $this->assertNotNull($slip);
        $this->assertEquals(30, (float) $slip->total_jam_normal);
        $this->assertSame(360000, $slip->total_gaji);

        return [
            'total_jam_normal' => (float) $slip->total_jam_normal,
            'total_gaji' => $slip->total_gaji,
        ];
    }

    private function runKu16(): array
    {
        $this->seedDefaultTarif(12000, 17000);
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Slip Show',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        Absensi::create([
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'durasi_jam' => 8,
            'jam_normal' => 8,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Penuh',
            'sudah_pulang' => true,
        ]);

        $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => '2026-09']);
        $slip = KomponenGaji::where('id_karyawan', $emp->id)->where('periode', '2026-09')->firstOrFail();

        $show = $this->actingAs($hr)->get(route('payroll.show', $slip->id));
        $show->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Payroll/Show')
            ->where('payroll.total_gaji', 96000)
        );

        return [
            'http_status' => 200,
            'component' => 'Payroll/Show',
            'total_gaji' => 96000,
        ];
    }

    private function runKu17(): array
    {
        $this->seedDefaultTarif(12000, 17000);
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Uji',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        Absensi::create([
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'durasi_jam' => 8,
            'jam_normal' => 8,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Penuh',
            'sudah_pulang' => true,
        ]);

        $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => '2026-09']);
        $slip = KomponenGaji::where('id_karyawan', $emp->id)->where('periode', '2026-09')->firstOrFail();
        $this->actingAs($hr)->put(route('payroll.finalize', $slip->id))->assertSessionHasNoErrors();
        $slip->refresh();
        $this->assertSame('final', $slip->status);

        $recalc = $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => '2026-09']);
        $slip->refresh();
        $this->assertSame('final', $slip->status);

        return [
            'status_sebelum_hitung_ulang' => 'final',
            'status_setelah_hitung_ulang' => $slip->status,
            'redirect_status' => $recalc->getStatusCode(),
        ];
    }

    private function runKu18(): array
    {
        $this->seedDefaultTarif(12000, 17000);
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Slip PDF',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);
        Absensi::create([
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'durasi_jam' => 8,
            'jam_normal' => 8,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Penuh',
            'sudah_pulang' => true,
        ]);
        $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => '2026-09']);
        $slip = KomponenGaji::where('id_karyawan', $emp->id)->where('periode', '2026-09')->firstOrFail();

        $response = $this->actingAs($hr)->get(route('payroll.pdf', $slip->id));
        $response->assertOk();
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('%PDF', $content);

        return [
            'http_status' => 200,
            'bytes' => strlen($content),
            'header' => substr($content, 0, 8),
        ];
    }

    private function runKu19(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $this->makeProduct('TJ-FIL-1', 'Tali Jepit', 'Pria Dewasa', 'Hitam', 50);
        $this->makeProduct('BG-FIL-1', 'Boloni Gunung', null, 'Merah', 30);

        $response = $this->actingAs($wh)->get(route('stock.available', ['kategori' => 'Tali Jepit']));
        $returnedCategories = [];
        $response->assertOk()->assertInertia(function (Assert $page) use (&$returnedCategories) {
            $page->component('Stock/Available')
                ->where('items.data', function ($items) use (&$returnedCategories) {
                    $arr = is_array($items) ? $items : $items->toArray();
                    $returnedCategories = array_values(array_unique(array_column($arr, 'kategori')));
                    return count($arr) > 0 && $returnedCategories === ['Tali Jepit'];
                });
        });

        return [
            'http_status' => 200,
            'filter_kategori' => 'Tali Jepit',
            'kategori_hasil' => $returnedCategories,
        ];
    }

    private function runKu20(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $p1 = $this->makeProduct('TJ-IN-1', 'Tali Jepit', 'Pria Dewasa', 'Hitam', 0);
        $p2 = $this->makeProduct('TJ-IN-2', 'Tali Jepit', 'Pria Dewasa', 'Biru', 0);

        $response = $this->actingAs($wh)->post(route('stock.incoming.store'), [
            'items' => [
                ['id_barang' => $p1->id, 'tanggal' => '2026-09-04', 'jumlah' => 100, 'keterangan' => 'Batch A'],
                ['id_barang' => $p2->id, 'tanggal' => '2026-09-04', 'jumlah' => 50, 'keterangan' => 'Batch B'],
            ],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertSame(100, $p1->fresh()->stok_saat_ini);
        $this->assertSame(50, $p2->fresh()->stok_saat_ini);

        return [
            'stok_p1' => $p1->fresh()->stok_saat_ini,
            'stok_p2' => $p2->fresh()->stok_saat_ini,
        ];
    }

    private function runKu21(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $p1 = $this->makeProduct('TJ-OUT-1', 'Tali Jepit', 'Pria Dewasa', 'Putih', 100);

        $response = $this->actingAs($wh)->post(route('stock.outgoing.store'), [
            'pelanggan' => 'Toko Uji',
            'items' => [
                ['id_barang' => $p1->id, 'tanggal' => '2026-09-04', 'jumlah' => 25, 'keterangan' => null],
            ],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertSame(75, $p1->fresh()->stok_saat_ini);
        $this->assertDatabaseHas('barang_keluar', ['tujuan_penggunaan' => 'Toko Uji']);

        return [
            'stok_akhir' => $p1->fresh()->stok_saat_ini,
            'pelanggan' => 'Toko Uji',
        ];
    }

    private function runKu22(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $p1 = $this->makeProduct('TJ-ROL-1', 'Tali Jepit', 'Pria Dewasa', 'Cokelat', 100);
        $p2 = $this->makeProduct('TJ-ROL-2', 'Tali Jepit', 'Wanita', 'Pink', 5);

        $response = $this->actingAs($wh)->post(route('stock.outgoing.store'), [
            'pelanggan' => 'Toko Rollback',
            'items' => [
                ['id_barang' => $p1->id, 'tanggal' => '2026-09-04', 'jumlah' => 20, 'keterangan' => null],
                ['id_barang' => $p2->id, 'tanggal' => '2026-09-04', 'jumlah' => 10, 'keterangan' => null],
            ],
        ]);
        $response->assertSessionHas('error');
        $this->assertSame(100, $p1->fresh()->stok_saat_ini);
        $this->assertSame(5, $p2->fresh()->stok_saat_ini);

        return [
            'batch_rolled_back' => true,
            'stok_p1' => $p1->fresh()->stok_saat_ini,
            'stok_p2' => $p2->fresh()->stok_saat_ini,
        ];
    }

    private function runKu23(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $p1 = $this->makeProduct('TJ-NTF-1', 'Tali Jepit', 'Pria Dewasa', 'Abu', 30, 25);

        $response = $this->actingAs($wh)->post(route('stock.outgoing.store'), [
            'pelanggan' => 'Toko Kritis',
            'items' => [
                ['id_barang' => $p1->id, 'tanggal' => '2026-09-04', 'jumlah' => 10, 'keterangan' => null],
            ],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertSame(20, $p1->fresh()->stok_saat_ini);
        $notifCount = NotifikasiStok::where('id_barang', $p1->id)->count();
        $this->assertSame(1, $notifCount);

        return [
            'stok_akhir' => $p1->fresh()->stok_saat_ini,
            'notifications_created' => $notifCount,
        ];
    }

    private function runKu24(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $p1 = $this->makeProduct('TJ-READ-1', 'Tali Jepit', 'Pria Dewasa', 'Merah', 10, 25);
        $notif = NotifikasiStok::create([
            'id_barang' => $p1->id,
            'pesan' => 'Stok kritis uji tandai dibaca',
            'tanggal' => '2026-09-04',
            'is_read' => false,
        ]);

        $readRoutes = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name && str_contains($name, 'notifications.read')) {
                $readRoutes[] = implode('|', $route->methods()) . ' ' . $route->uri() . " ({$name})";
            }
        }

        $this->assertNotEmpty($readRoutes, 'Rute penandaan notifikasi dibaca harus tersedia.');
        $response = $this->actingAs($wh)->put(route('stock.notifications.read', $notif->id));
        $response->assertSessionHasNoErrors();
        $notif->refresh();
        $this->assertTrue((bool) $notif->is_read);

        return [
            'registered_read_routes' => $readRoutes,
            'is_read' => (bool) $notif->is_read,
        ];
    }

    private function runKu25(): array
    {
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Ekspor Absensi',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);
        Absensi::create([
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'durasi_jam' => 8,
            'jam_normal' => 8,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Penuh',
            'sudah_pulang' => true,
        ]);

        $response = $this->actingAs($hr)->get(route('reports.attendance.pdf', ['bulan' => '2026-09']));
        $response->assertOk();
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('%PDF', $content);

        return [
            'http_status' => 200,
            'bytes' => strlen($content),
            'header' => substr($content, 0, 8),
        ];
    }

    private function runKu26(): array
    {
        $this->seedDefaultTarif(12000, 17000);
        $hr = $this->makeUser('hr', 'uji_hr');
        $emp = Karyawan::create([
            'nama' => 'Karyawan Ekspor Payroll',
            'jabatan' => 'Karyawan',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);
        Absensi::create([
            'id_karyawan' => $emp->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'durasi_jam' => 8,
            'jam_normal' => 8,
            'jam_lembur' => 0,
            'status_kehadiran' => 'Penuh',
            'sudah_pulang' => true,
        ]);
        $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => '2026-09']);

        $response = $this->actingAs($hr)->get(route('reports.payroll.excel', ['periode' => '2026-09']));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('PK', $content);

        return [
            'http_status' => 200,
            'bytes' => strlen($content),
            'format' => 'XLSX (PK)',
        ];
    }

    private function runKu27(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $p1 = $this->makeProduct('TJ-XLS-1', 'Tali Jepit', 'Pria Dewasa', 'Kuning', 0);
        $this->actingAs($wh)->post(route('stock.incoming.store'), [
            'items' => [
                ['id_barang' => $p1->id, 'tanggal' => '2026-09-04', 'jumlah' => 20, 'keterangan' => null],
            ],
        ]);

        $response = $this->actingAs($wh)->get(route('reports.stock.excel', ['bulan' => '2026-09']));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('PK', $content);

        return [
            'http_status' => 200,
            'bytes' => strlen($content),
            'format' => 'XLSX (PK)',
        ];
    }

    private function runKu28(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh');
        $response = $this->actingAs($wh)->getJson('/users');
        $response->assertForbidden();

        return [
            'http_status' => $response->getStatusCode(),
            'role' => 'warehouse',
            'blocked_url' => '/users',
        ];
    }

    private function runKu29(): array
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 1, 9, 0, 0));
        try {
            $settingSept = SettingGaji::create([
                'gaji_pokok_kategori_a' => 0,
                'gaji_pokok_kategori_b' => 0,
                'tarif_per_jam_kategori_a' => 12000,
                'tarif_per_jam_kategori_b' => 17000,
                'insentif_jam_lebih_pct' => 0,
                'insentif_lembur_pct' => 0,
                'potongan_setengah_pct' => 0,
                'is_active' => true,
                'keterangan' => 'Tarif September 2026',
            ]);

            $hr = $this->makeUser('hr', 'uji_hr');
            $emp = Karyawan::create([
                'nama' => 'Karyawan Historis September',
                'jabatan' => 'Operator Produksi',
                'tanggal_masuk' => '2021-10-01',
                'kategori_masa_kerja' => 'A',
                'is_active' => true,
            ]);

            Absensi::create([
                'id_karyawan' => $emp->id,
                'tanggal' => '2026-09-15',
                'jam_masuk' => '08:00',
                'jam_keluar' => '16:00',
                'durasi_jam' => 8,
                'jam_normal' => 8,
                'jam_lembur' => 0,
                'status_kehadiran' => 'Penuh',
                'sudah_pulang' => true,
            ]);

            // Setelah ulang tahun kerja kelima (1 Okt) & kenaikan tarif November
            Carbon::setTestNow(Carbon::create(2026, 10, 15, 9, 0, 0));
            $emp->update(['kategori_masa_kerja' => 'B']);
            $settingSept->update(['is_active' => false]);
            SettingGaji::create([
                'gaji_pokok_kategori_a' => 0,
                'gaji_pokok_kategori_b' => 0,
                'tarif_per_jam_kategori_a' => 15000,
                'tarif_per_jam_kategori_b' => 19000,
                'insentif_jam_lebih_pct' => 0,
                'insentif_lembur_pct' => 0,
                'potongan_setengah_pct' => 0,
                'is_active' => true,
                'keterangan' => 'Tarif Baru Oktober/November',
            ]);

            Carbon::setTestNow(Carbon::create(2026, 11, 4, 10, 0, 0));
            $response = $this->actingAs($hr)->post(route('payroll.calculate'), [
                'periode' => '2026-09',
            ]);
            $response->assertRedirect();

            $slip = KomponenGaji::where('id_karyawan', $emp->id)
                ->where('periode', '2026-09')
                ->firstOrFail();

            $this->assertSame('A', $slip->rincian['kategori_masa_kerja'] ?? null);
            $this->assertSame(12000, (int) $slip->tarif_per_jam);
            $this->assertSame(96000, (int) $slip->total_gaji);

            return [
                'periode' => '2026-09',
                'tanggal_hitung' => '2026-11-04',
                'kategori_masa_kerja' => $slip->rincian['kategori_masa_kerja'] ?? null,
                'tarif_per_jam' => (int) $slip->tarif_per_jam,
                'total_gaji' => (int) $slip->total_gaji,
            ];
        } finally {
            Carbon::setTestNow();
        }
    }

    private function runKu30(): array
    {
        $user = $this->makeUser('hr', 'profil_lama', true, 'Nama Lama HR');

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'nama' => 'Nama Baru HR',
                'username' => 'profil_baru',
            ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-updated');
        $response->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('Nama Baru HR', $fresh->nama);
        $this->assertSame('profil_baru', $fresh->username);

        return [
            'nama' => $fresh->nama,
            'username' => $fresh->username,
            'session_status' => 'profile-updated',
        ];
    }

    private function runKu31(): array
    {
        $admin = $this->makeUser('admin', 'admin_nonaktif');
        $target = $this->makeUser('hr', 'hr_nonaktif', true, 'HR Dinonaktifkan');

        // Sebelum dinonaktifkan: dashboard bisa diakses
        $this->actingAs($target)->get(route('dashboard'))->assertOk();

        // Admin menonaktifkan akun melalui modul manajemen pengguna
        $this->actingAs($admin)->put(route('users.update', $target->id), [
            'nama' => $target->nama,
            'username' => $target->username,
            'role' => 'hr',
            'is_active' => false,
        ])->assertRedirect();

        // Simulasikan pengguna masih memegang sesi lama dan membuka /dashboard
        $this->actingAs($target->fresh());
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        return [
            'http_status' => $response->getStatusCode(),
            'redirect_to' => route('login', [], false),
            'guest' => true,
        ];
    }

    private function runKu32(): array
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 10, 9, 0, 0));
        try {
            $hr = $this->makeUser('hr', 'uji_hr_hadir');
            $emp = Karyawan::create([
                'nama' => 'Karyawan Hadir Belum Pulang',
                'jabatan' => 'Operator',
                'tanggal_masuk' => '2024-01-01',
                'kategori_masa_kerja' => 'A',
                'is_active' => true,
            ]);

            $this->actingAs($hr)->post(route('attendance.check-in'), [
                'id_karyawan' => $emp->id,
                'tanggal' => '2026-09-10',
                'jam_masuk' => '08:00',
            ])->assertRedirect();

            $response = $this->actingAs($hr)->get(route('dashboard'));
            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.hadir_hari_ini', 1)
                ->where('stats.sedang_bekerja', 1)
            );

            return [
                'hadir_hari_ini' => 1,
                'sedang_bekerja' => 1,
                'tanggal' => '2026-09-10',
            ];
        } finally {
            Carbon::setTestNow();
        }
    }

    private function runKu33(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh_bulanan');
        $product = $this->makeProduct('TJ-BLN-01', 'Tali Jepit', 'Pria Dewasa', 'Merah', 0);

        \App\Models\BarangMasuk::create([
            'id_barang' => $product->id,
            'tanggal' => '2026-08-31',
            'jumlah' => 5,
            'keterangan' => 'Luar periode Agustus',
        ]);
        \App\Models\BarangMasuk::create([
            'id_barang' => $product->id,
            'tanggal' => '2026-09-30',
            'jumlah' => 7,
            'keterangan' => 'Masuk akhir bulan September',
        ]);
        \App\Models\BarangMasuk::create([
            'id_barang' => $product->id,
            'tanggal' => '2026-10-01',
            'jumlah' => 9,
            'keterangan' => 'Luar periode Oktober',
        ]);

        $response = $this->actingAs($wh)->get(route('stock.weekly-report', [
            'type' => 'monthly',
            'date' => '2026-09-15',
        ]));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Stock/WeeklyReport')
            ->where('period.key', '2026-09')
            ->where('period.start', '01 Sep 2026')
            ->where('period.end', '30 Sep 2026')
            ->where('totals.masuk', 7)
        );

        return [
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'totals_masuk' => 7,
        ];
    }

    private function runKu34(): array
    {
        $wh = $this->makeUser('warehouse', 'uji_wh_mingguan');
        $product = $this->makeProduct('TJ-MGG-01', 'Tali Jepit', 'Pria Dewasa', 'Biru', 0);

        // Minggu 31 Agustus - 6 September 2026 (Minggu = 2026-09-06)
        \App\Models\BarangMasuk::create([
            'id_barang' => $product->id,
            'tanggal' => '2026-08-30',
            'jumlah' => 4,
            'keterangan' => 'Sebelum minggu',
        ]);
        \App\Models\BarangMasuk::create([
            'id_barang' => $product->id,
            'tanggal' => '2026-09-06',
            'jumlah' => 7,
            'keterangan' => 'Minggu akhir periode',
        ]);
        \App\Models\BarangMasuk::create([
            'id_barang' => $product->id,
            'tanggal' => '2026-09-07',
            'jumlah' => 6,
            'keterangan' => 'Sesudah minggu',
        ]);

        $response = $this->actingAs($wh)->get(route('stock.weekly-report', [
            'type' => 'weekly',
            'date' => '2026-09-06',
        ]));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Stock/WeeklyReport')
            ->where('period.key', '2026-08-31')
            ->where('period.start', '31 Agt 2026')
            ->where('period.end', '06 Sep 2026')
            ->where('totals.masuk', 7)
        );

        return [
            'period_start' => '2026-08-31',
            'period_end' => '2026-09-06',
            'totals_masuk' => 7,
        ];
    }
}
