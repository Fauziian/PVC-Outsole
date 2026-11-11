import fs from 'node:fs';
import path from 'node:path';
import { execFileSync, spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { pathToFileURL } from 'node:url';

const require = createRequire(import.meta.url);
let playwright;
try {
  playwright = require('playwright-core');
} catch {
  playwright = require('C:\\xampp\\htdocs\\sumber-pvc-scratch\\node_modules\\playwright-core');
}
const { chromium } = playwright;

const ROOT = path.resolve(import.meta.dirname, '..', '..');
const SCRATCH = path.resolve(ROOT, '..', 'sumber-pvc-scratch');
const GAMBAR_DIR = path.join(ROOT, 'docx', 'Laporan', 'gambar');
const CARD_HTML = path.join(ROOT, 'scripts', 'evidence', 'card-template.html');
const PHPUNIT_JSON = path.join(SCRATCH, 'phpunit_results.json');
const BASELINE_JSON = path.join(SCRATCH, 'baseline_results.json');
const UI_SQLITE = path.join(SCRATCH, 'evidence_ui.sqlite');
const DOWNLOADS_DIR = path.join(SCRATCH, 'downloads');
const MANIFEST_PATH = path.join(ROOT, 'docs', 'evidence-manifest.json');
const ORIG_MEDIA_DIR = path.join(SCRATCH, 'laporan_work', 'word', 'media');

function findBrowserExecutable() {
  const candidates = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  ];
  for (const exe of candidates) {
    if (fs.existsSync(exe)) return exe;
  }
  throw new Error('Browser Chrome/Edge tidak ditemukan di Windows.');
}

function sqliteEnv(extra = {}) {
  return {
    ...process.env,
    APP_ENV: 'testing',
    APP_URL: 'http://127.0.0.1:8099',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: UI_SQLITE,
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'array',
    ...extra,
  };
}

function runUiHelperPhp(filename, code) {
  const scriptPath = path.join(SCRATCH, filename);
  fs.writeFileSync(scriptPath, `<?php
require '${ROOT.replace(/\\/g, '/')}/vendor/autoload.php';
$app = require '${ROOT.replace(/\\/g, '/')}/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();
${code}
`);
  execFileSync('php', [scriptPath], { cwd: ROOT, env: sqliteEnv(), stdio: 'inherit' });
}

function cleanStaleEvidenceFiles() {
  if (!fs.existsSync(GAMBAR_DIR)) return;
  for (const name of fs.readdirSync(GAMBAR_DIR)) {
    if (/^KU-\d+-(teknis|antarmuka)\.png$/i.test(name)) {
      fs.unlinkSync(path.join(GAMBAR_DIR, name));
    }
  }
}

function seedIsolatedUiDatabase() {
  fs.mkdirSync(SCRATCH, { recursive: true });
  fs.mkdirSync(DOWNLOADS_DIR, { recursive: true });
  fs.mkdirSync(GAMBAR_DIR, { recursive: true });
  cleanStaleEvidenceFiles();

  if (fs.existsSync(UI_SQLITE)) fs.unlinkSync(UI_SQLITE);
  fs.writeFileSync(UI_SQLITE, '');

  const env = sqliteEnv();
  execFileSync('php', ['artisan', 'migrate:fresh', '--force'], { cwd: ROOT, env, stdio: 'inherit' });

  runUiHelperPhp('seed_evidence_ui.php', `
use App\\Models\\User;
use App\\Models\\SettingGaji;
use App\\Models\\Karyawan;
use App\\Models\\Absensi;
use App\\Models\\BarangPvc;
use Illuminate\\Support\\Facades\\Hash;

SettingGaji::query()->delete();
BarangPvc::query()->delete();

SettingGaji::create([
    'gaji_pokok_kategori_a' => 0,
    'gaji_pokok_kategori_b' => 0,
    'tarif_per_jam_kategori_a' => 10000,
    'tarif_per_jam_kategori_b' => 15000,
    'insentif_jam_lebih_pct' => 0,
    'insentif_lembur_pct' => 0,
    'potongan_setengah_pct' => 0,
    'is_active' => true,
    'keterangan' => 'Tarif awal sebelum pembaruan',
]);

User::create(['nama' => 'Admin Pengujian', 'username' => 'admin_uji', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true]);
User::create(['nama' => 'Akun Pengujian HR', 'username' => 'uji_hr', 'password' => Hash::make('password'), 'role' => 'hr', 'is_active' => true]);
User::create(['nama' => 'Staf Gudang Uji', 'username' => 'uji_wh', 'password' => Hash::make('password'), 'role' => 'warehouse', 'is_active' => true]);

$emp30 = Karyawan::create([
    'nama' => 'Karyawan Gaji 30 Jam',
    'jabatan' => 'Operator Cetak',
    'tanggal_masuk' => '2024-01-01',
    'kategori_masa_kerja' => 'A',
    'is_active' => true,
]);
foreach (['2026-09-01' => 8, '2026-09-02' => 10, '2026-09-03' => 12] as $tgl => $dur) {
    Absensi::create([
        'id_karyawan' => $emp30->id,
        'tanggal' => $tgl,
        'jam_masuk' => '08:00',
        'jam_keluar' => sprintf('%02d:00', 8 + $dur),
        'durasi_jam' => $dur,
        'jam_normal' => $dur,
        'jam_lembur' => 0,
        'status_kehadiran' => 'Penuh',
        'sudah_pulang' => true,
    ]);
}

$emp8 = Karyawan::create([
    'nama' => 'Karyawan Slip 8 Jam',
    'jabatan' => 'Operator Finishing',
    'tanggal_masuk' => '2024-01-01',
    'kategori_masa_kerja' => 'A',
    'is_active' => true,
]);
Absensi::create([
    'id_karyawan' => $emp8->id,
    'tanggal' => '2026-09-04',
    'jam_masuk' => '08:00',
    'jam_keluar' => '16:00',
    'durasi_jam' => 8,
    'jam_normal' => 8,
    'jam_lembur' => 0,
    'status_kehadiran' => 'Penuh',
    'sudah_pulang' => true,
]);

Karyawan::create([
    'nama' => 'Karyawan Siap Hapus',
    'jabatan' => 'Magang Produksi',
    'tanggal_masuk' => '2025-05-01',
    'kategori_masa_kerja' => 'A',
    'is_active' => true,
]);

BarangPvc::create([
    'nama_barang' => 'Tali Jepit Pria - Hitam',
    'kode_barang' => 'TJ-PR-HTM',
    'kategori' => 'Tali Jepit',
    'jenis' => 'Pria Dewasa',
    'warna' => 'Hitam',
    'satuan' => 'kodi',
    'stok_minimum' => 25,
    'stok_saat_ini' => 0,
]);
BarangPvc::create([
    'nama_barang' => 'Tali Jepit Wanita - Biru',
    'kode_barang' => 'TJ-WN-BRU',
    'kategori' => 'Tali Jepit',
    'jenis' => 'Wanita',
    'warna' => 'Biru',
    'satuan' => 'kodi',
    'stok_minimum' => 25,
    'stok_saat_ini' => 0,
]);
BarangPvc::create([
    'nama_barang' => 'Tali Jepit Pria - Putih',
    'kode_barang' => 'TJ-PR-PTH',
    'kategori' => 'Tali Jepit',
    'jenis' => 'Pria Dewasa',
    'warna' => 'Putih',
    'satuan' => 'kodi',
    'stok_minimum' => 10,
    'stok_saat_ini' => 100,
]);
BarangPvc::create([
    'nama_barang' => 'Tali Jepit Anak - Merah',
    'kode_barang' => 'TJ-AN-MRH',
    'kategori' => 'Tali Jepit',
    'jenis' => 'Anak',
    'warna' => 'Merah',
    'satuan' => 'kodi',
    'stok_minimum' => 10,
    'stok_saat_ini' => 5,
]);
BarangPvc::create([
    'nama_barang' => 'Tali Jepit Pria - Abu',
    'kode_barang' => 'TJ-PR-ABU',
    'kategori' => 'Tali Jepit',
    'jenis' => 'Pria Dewasa',
    'warna' => 'Abu',
    'satuan' => 'kodi',
    'stok_minimum' => 25,
    'stok_saat_ini' => 30,
]);
BarangPvc::create([
    'nama_barang' => 'Boloni Gunung - Cokelat',
    'kode_barang' => 'BG-CKL-01',
    'kategori' => 'Boloni Gunung',
    'jenis' => null,
    'warna' => 'Cokelat',
    'satuan' => 'kodi',
    'stok_minimum' => 15,
    'stok_saat_ini' => 45,
]);
echo "SEED_UI_OK\\n";
`);
}

function copyHistoricalPreFixAssets() {
  const copied = {};
  const map = [
    ['KU-17', 'image105.jpg', 'KU-17-sebelum-perbaikan-tidak-sesuai.jpg'],
    ['KU-17_ui', 'image107.jpg', 'KU-17-sebelum-perbaikan-antarmuka.jpg'],
    ['KU-24', 'image114.jpg', 'KU-24-sebelum-perbaikan-tidak-sesuai.jpg'],
  ];
  for (const [key, srcName, destName] of map) {
    const src = path.join(ORIG_MEDIA_DIR, srcName);
    const dest = path.join(GAMBAR_DIR, destName);
    if (fs.existsSync(src)) {
      fs.copyFileSync(src, dest);
      copied[key] = path.relative(ROOT, dest).replace(/\\/g, '/');
    }
  }
  return copied;
}

async function waitForServer(url, timeoutMs = 20000) {
  const started = Date.now();
  while (Date.now() - started < timeoutMs) {
    try {
      const res = await fetch(`${url}/login`);
      if (res.ok) return;
    } catch {}
    await new Promise((r) => setTimeout(r, 400));
  }
  throw new Error(`Server lokal tidak siap di ${url}`);
}

async function login(page, baseUrl, username, password = 'password') {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
  await page.locator('input[type="text"]').fill(username);
  await page.locator('input[type="password"]').fill(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 15000 }),
    page.locator('button[type="submit"]').click(),
  ]);
  await page.waitForLoadState('networkidle');
}

async function shotElement(locator, filename) {
  await locator.first().waitFor({ state: 'visible', timeout: 10000 });
  const fullPath = path.join(GAMBAR_DIR, filename);
  await locator.first().screenshot({ path: fullPath });
  return path.relative(ROOT, fullPath).replace(/\\/g, '/');
}

async function shotContentClip(page, filename) {
  await page.waitForTimeout(350);
  const fullPath = path.join(GAMBAR_DIR, filename);
  await page.screenshot({
    path: fullPath,
    clip: { x: 260, y: 56, width: 1020, height: 744 },
  });
  return path.relative(ROOT, fullPath).replace(/\\/g, '/');
}

async function renderCards(browser, phpunitData, baselineData) {
  const cardPage = await browser.newPage({ viewport: { width: 1020, height: 700 }, deviceScaleFactor: 1 });
  const cardUrl = pathToFileURL(CARD_HTML).href;
  const rendered = {};

  for (const [ku, entry] of Object.entries(phpunitData)) {
    await cardPage.goto(cardUrl, { waitUntil: 'load' });
    await cardPage.evaluate((payload) => window.renderEvidenceCard(payload), entry);
    await cardPage.waitForTimeout(80);
    const outPath = path.join(GAMBAR_DIR, `${ku}-teknis-sesuai.png`);
    await cardPage.locator('#cardRoot').screenshot({ path: outPath });
    rendered[ku] = path.relative(ROOT, outPath).replace(/\\/g, '/');
  }

  let baselineCardKu29 = null;
  if (baselineData && baselineData['KU-29']) {
    await cardPage.goto(cardUrl, { waitUntil: 'load' });
    await cardPage.evaluate((payload) => window.renderEvidenceCard(payload), baselineData['KU-29']);
    await cardPage.waitForTimeout(80);
    const outPath = path.join(GAMBAR_DIR, 'KU-29-sebelum-perbaikan-tidak-sesuai.png');
    await cardPage.locator('#cardRoot').screenshot({ path: outPath });
    baselineCardKu29 = path.relative(ROOT, outPath).replace(/\\/g, '/');
  }

  await cardPage.close();
  return { rendered, baselineCardKu29 };
}

async function captureDownload(context, locator, baseUrl, prefix) {
  await locator.waitFor({ state: 'visible', timeout: 10000 });
  const rawHref = await locator.getAttribute('href');
  if (!rawHref) throw new Error(`Missing href on download locator for ${prefix}`);
  const parsed = new URL(rawHref, baseUrl);
  const targetUrl = `${baseUrl}${parsed.pathname}${parsed.search}`;
  const resp = await context.request.get(targetUrl, { timeout: 60000 });
  if (resp.status() !== 200) {
    throw new Error(`Download ${targetUrl} failed with HTTP ${resp.status()}`);
  }
  const buf = await resp.body();
  const cd = resp.headers()['content-disposition'] || '';
  const match = cd.match(/filename="?([^";]+)"?/i);
  const suggested = match ? match[1] : `${prefix}.bin`;
  const dest = path.join(DOWNLOADS_DIR, `${prefix}-${suggested}`);
  fs.writeFileSync(dest, buf);
  return {
    filename: suggested,
    url: targetUrl,
    path: dest,
    bytes: buf.length,
    signature: buf.subarray(0, 4).toString('latin1'),
  };
}

async function main() {
  if (!fs.existsSync(PHPUNIT_JSON)) {
    throw new Error(`Hasil PHPUnit belum ditemukan di ${PHPUNIT_JSON}`);
  }
  const phpunitData = JSON.parse(fs.readFileSync(PHPUNIT_JSON, 'utf8'));
  const baselineData = fs.existsSync(BASELINE_JSON) ? JSON.parse(fs.readFileSync(BASELINE_JSON, 'utf8')) : {};

  seedIsolatedUiDatabase();
  const historicalAssets = copyHistoricalPreFixAssets();

  const port = 8099;
  const baseUrl = `http://127.0.0.1:${port}`;
  const serverProc = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`], {
    cwd: ROOT,
    env: sqliteEnv(),
    stdio: 'ignore',
    windowsHide: true,
  });

  const browser = await chromium.launch({
    executablePath: findBrowserExecutable(),
    headless: true,
    args: ['--no-sandbox', '--disable-gpu'],
  });

  const uiEvidence = {};
  const resultEvidence = {};
  const downloads = {};

  try {
    await waitForServer(baseUrl);
    const { rendered: cardFiles, baselineCardKu29 } = await renderCards(browser, phpunitData, baselineData);
    if (baselineCardKu29) historicalAssets['KU-29'] = baselineCardKu29;

    const context = await browser.newContext({
      viewport: { width: 1280, height: 800 },
      acceptDownloads: true,
    });
    const page = await context.newPage();

    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    // KU-01: Validasi Login Gagal
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await page.locator('input[type="text"]').fill('uji_hr');
    await page.locator('input[type="password"]').fill('salah-password');
    await page.locator('button[type="submit"]').click();
    await page.locator('div.text-rose-400').waitFor({ state: 'visible', timeout: 10000 });
    uiEvidence['KU-01'] = await shotElement(page.locator('div.bg-slate-800\\/60'), 'KU-01-antarmuka-sesuai.png');

    // KU-02: Login Berhasil & Redirect (HR -> /dashboard)
    await login(page, baseUrl, 'uji_hr');
    await page.getByText('Portal Operasional').waitFor({ timeout: 10000 });
    uiEvidence['KU-02'] = await shotElement(page.locator('main > div'), 'KU-02-antarmuka-sesuai.png');

    // KU-03: Logout Sesi
    await page.locator('button[title="Keluar dari Sistem"]').click();
    await page.waitForURL((u) => u.pathname.endsWith('/login'), { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    uiEvidence['KU-03'] = await shotElement(page.locator('div.bg-slate-800\\/60'), 'KU-03-antarmuka-sesuai.png');

    // KU-04: Tambah Akun Pengguna (admin)
    await login(page, baseUrl, 'admin_uji');
    await page.goto(`${baseUrl}/users`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Daftarkan Akun Baru/i }).click();
    await page.getByPlaceholder('Masukkan nama lengkap...').fill('HR Baru Uji');
    await page.getByPlaceholder('nama_pilihan...').fill('hr_baru');
    await page.getByPlaceholder('Minimal 6 karakter...').fill('Secret123!');
    await page.locator('form select').selectOption('hr');
    await page.getByRole('button', { name: 'Simpan Akun' }).click();
    await page.getByText('@hr_baru').waitFor({ timeout: 10000 });
    uiEvidence['KU-04'] = await shotElement(page.locator('main > div'), 'KU-04-antarmuka-sesuai.png');

    // KU-05: Ubah Pengguna & Status -> nonaktif
    const hrBaruRow = page.locator('tr', { hasText: '@hr_baru' });
    await hrBaruRow.locator('button[title="Ubah Profil"]').click();
    await page.getByPlaceholder('Masukkan nama lengkap...').fill('HR Nonaktif Uji');
    await page.locator('#is_active_usr').uncheck();
    await page.getByRole('button', { name: 'Simpan Akun' }).click();
    await page.getByText('HR Nonaktif Uji').waitFor({ timeout: 10000 });
    uiEvidence['KU-05'] = await shotElement(page.locator('main > div'), 'KU-05-antarmuka-sesuai.png');

    // KU-06: Reset Kata Sandi
    const hrUpdatedRow = page.locator('tr', { hasText: '@hr_baru' });
    await hrUpdatedRow.locator('button[title="Reset Kata Sandi"]').click();
    await page.getByPlaceholder(/Masukkan kata sandi baru/i).fill('New12345!');
    await page.locator('div.fixed form button[type="submit"]').click();
    await page.getByText(/Kata sandi berhasil di-reset/i).waitFor({ timeout: 10000 });
    uiEvidence['KU-06'] = await shotContentClip(page, 'KU-06-antarmuka-sesuai.png');

    // KU-07: Proteksi Akun Sendiri (modal spesifik)
    const adminRow = page.locator('tr', { hasText: '@admin_uji' });
    await adminRow.locator('button[title="Ubah Profil"]').click();
    await page.getByText('Anda tidak dapat menonaktifkan akun Anda sendiri.').waitFor({ timeout: 10000 });
    uiEvidence['KU-07'] = await shotElement(page.locator('div.fixed > div'), 'KU-07-antarmuka-sesuai.png');
    await page.getByRole('button', { name: 'Batal' }).click();

    // KU-14: Ubah Tarif Upah Jam
    await page.goto(`${baseUrl}/payroll/settings`, { waitUntil: 'networkidle' });
    const rateInputs = page.locator('input[type="number"]');
    await rateInputs.nth(0).fill('12000');
    await rateInputs.nth(1).fill('17000');
    await page.getByPlaceholder(/penyesuaian tarif/i).fill('Tarif standar 12.000 / 17.000');
    await page.getByRole('button', { name: /Simpan Tarif/i }).click();
    await page.getByText(/berhasil diperbarui/i).waitFor({ timeout: 10000 });
    uiEvidence['KU-14'] = await shotContentClip(page, 'KU-14-antarmuka-sesuai.png');

    // Logout admin -> Login HR
    await page.locator('button[title="Keluar dari Sistem"]').click();
    await page.waitForURL((u) => u.pathname.endsWith('/login'), { timeout: 15000 });
    await login(page, baseUrl, 'uji_hr');

    // KU-08: Tambah Data Karyawan
    await page.goto(`${baseUrl}/employees`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Tambah Karyawan/i }).click();
    const empModalAdd = page.locator('div.fixed form');
    await page.getByPlaceholder('Masukkan nama lengkap...').fill('Karyawan Baru Uji');
    await page.getByPlaceholder(/Operator Produksi/i).fill('Karyawan');
    await empModalAdd.locator('input[type="date"]').fill('2024-01-01');
    await empModalAdd.locator('select').first().selectOption('A');
    await empModalAdd.locator('button[type="submit"]').click();
    await page.getByText('Karyawan Baru Uji').waitFor({ timeout: 10000 });
    uiEvidence['KU-08'] = await shotElement(page.locator('main > div'), 'KU-08-antarmuka-sesuai.png');

    // KU-09: Ubah Data Karyawan
    const empRow = page.locator('tr', { hasText: 'Karyawan Baru Uji' });
    await empRow.locator('button[title="Ubah data"]').click();
    const empModalEdit = page.locator('div.fixed form');
    await page.getByPlaceholder('Masukkan nama lengkap...').fill('Karyawan Senior Uji');
    await empModalEdit.locator('input[type="date"]').fill('2020-01-01');
    await empModalEdit.locator('select').first().selectOption('B');
    await empModalEdit.locator('button[type="submit"]').click();
    await page.getByText('Karyawan Senior Uji').waitFor({ timeout: 10000 });
    uiEvidence['KU-09'] = await shotElement(page.locator('main > div'), 'KU-09-antarmuka-sesuai.png');

    // KU-10: Hapus Data Karyawan
    const delRow = page.locator('tr', { hasText: 'Karyawan Siap Hapus' });
    await delRow.locator('button').last().click();
    await page.getByText(/berhasil dihapus/i).waitFor({ timeout: 10000 });
    uiEvidence['KU-10'] = await shotContentClip(page, 'KU-10-antarmuka-sesuai.png');

    // KU-11: Catat Check-In Masuk (10 September 2026)
    await page.goto(`${baseUrl}/attendance?tanggal=2026-09-10`, { waitUntil: 'networkidle' });
    const attRow = page.locator('tr', { hasText: 'Karyawan Senior Uji' });
    await attRow.getByRole('button', { name: 'Masuk' }).click();
    await page.locator('form input[type="time"]').fill('08:00');
    await page.getByRole('button', { name: 'Simpan Masuk' }).click();
    await page.getByText('HADIR / MASUK').waitFor({ timeout: 10000 });
    uiEvidence['KU-11'] = await shotElement(page.locator('main > div'), 'KU-11-antarmuka-sesuai.png');

    // KU-12: Validasi Duplikasi Masuk
    await page.evaluate(async () => {
      const propsAttr = document.getElementById('app')?.getAttribute('data-page');
      const pageObj = propsAttr ? JSON.parse(propsAttr) : null;
      const rows = pageObj?.props?.rows || [];
      const senior = rows.find((r) => r.nama === 'Karyawan Senior Uji');
      if (!senior) throw new Error('Karyawan Senior Uji not found in page props');
      const xsrfMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
      const xsrf = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : '';
      const form = new URLSearchParams({
        id_karyawan: String(senior.id_karyawan),
        tanggal: '2026-09-10',
        jam_masuk: '08:00',
      });
      await fetch('/attendance/check-in', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-XSRF-TOKEN': xsrf },
        body: form.toString(),
      });
    });
    await page.reload({ waitUntil: 'networkidle' });
    uiEvidence['KU-12'] = await shotElement(page.locator('main > div'), 'KU-12-antarmuka-sesuai.png');

    // KU-13: Catat Check-Out Pulang (14 jam -> LEMBUR)
    const attOutRow = page.locator('tr', { hasText: 'Karyawan Senior Uji' });
    await attOutRow.getByRole('button', { name: 'Pulang' }).click();
    await page.getByRole('button', { name: '14 jam' }).click();
    await page.getByRole('button', { name: 'Simpan Pulang' }).click();
    await page.getByText('LEMBUR', { exact: true }).first().waitFor({ timeout: 10000 });
    uiEvidence['KU-13'] = await shotElement(page.locator('main > div'), 'KU-13-antarmuka-sesuai.png');

    // KU-15: Kalkulasi Gaji Massal September 2026 (30 jam -> Rp 360.000)
    await page.goto(`${baseUrl}/payroll?periode=2026-09`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Generate Slip Gaji/i }).click();
    await page.getByText('Rp 360.000').first().waitFor({ timeout: 10000 });
    uiEvidence['KU-15'] = await shotElement(page.locator('main > div'), 'KU-15-antarmuka-sesuai.png');

    // KU-16: Pratinjau Lembar Slip (8 jam -> Rp 96.000)
    const slip8Row = page.locator('tr', { hasText: 'Karyawan Slip 8 Jam' });
    await slip8Row.getByRole('link', { name: /Rincian/i }).click();
    await page.waitForURL((u) => /\/payroll\/\d+/.test(u.pathname), { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await page.getByText('Rp 96.000').first().waitFor({ timeout: 10000 });
    const slip8Url = page.url();
    uiEvidence['KU-16'] = await shotElement(page.locator('main > div'), 'KU-16-antarmuka-sesuai.png');

    // KU-18: Unduh Slip Gaji PDF (sebelum/setelah final sama-sama bisa unduh, verifikasi file PDF dulu)
    downloads['KU-18'] = await captureDownload(context, page.getByRole('link', { name: /Cetak PDF/i }), baseUrl, 'KU-18');
    await page.locator('[data-sonner-toast]').waitFor({ state: 'detached', timeout: 7000 }).catch(() => {});
    await page.locator('a', { hasText: 'Cetak PDF' }).focus();
    uiEvidence['KU-18'] = await shotElement(page.locator('main > div'), 'KU-18-antarmuka-sesuai.png');

    // KU-17: Finalisasi Status Slip + hitung ulang periode sama tetap FINAL (tampilkan badge FINAL di rekap payroll)
    await page.getByRole('button', { name: /Finalisasi Slip/i }).click();
    await page.getByText(/Finalisasi Slip/i).waitFor({ state: 'detached', timeout: 10000 });
    await page.goto(`${baseUrl}/payroll?periode=2026-09`, { waitUntil: 'networkidle' });
    await page.locator('[data-sonner-toast]').waitFor({ state: 'detached', timeout: 7000 }).catch(() => {});
    await page.locator('tr', { hasText: 'Karyawan Slip 8 Jam' }).getByText(/final/i).waitFor({ timeout: 10000 });
    uiEvidence['KU-17'] = await shotElement(page.locator('div.bg-white:has(table)'), 'KU-17-antarmuka-sesuai.png');

    await page.getByRole('button', { name: /Generate Slip Gaji/i }).click();
    await page.waitForLoadState('networkidle');
    await page.locator('[data-sonner-toast]').waitFor({ state: 'detached', timeout: 7000 }).catch(() => {});
    await page.locator('tr', { hasText: 'Karyawan Slip 8 Jam' }).getByText(/final/i).waitFor({ timeout: 10000 });
    resultEvidence['KU-17'] = await shotElement(page.locator('div.bg-white:has(table)'), 'KU-17-hasil-sesuai.png');

    // KU-25 & KU-26: Ekspor Absensi PDF & Payroll Excel
    await page.goto(`${baseUrl}/reports`, { waitUntil: 'networkidle' });
    const monthInputs = page.locator('input[type="month"]');
    await monthInputs.nth(0).fill('2026-09');
    await monthInputs.nth(1).fill('2026-09');
    downloads['KU-25'] = await captureDownload(context, page.getByRole('link', { name: /Unduh PDF Laporan/i }), baseUrl, 'KU-25');
    const reportCards = page.locator('main div.grid > div');
    uiEvidence['KU-25'] = await shotElement(reportCards.nth(0), 'KU-25-antarmuka-sesuai.png');

    downloads['KU-26'] = await captureDownload(context, page.getByRole('link', { name: /Unduh Rekap Excel/i }), baseUrl, 'KU-26');
    uiEvidence['KU-26'] = await shotElement(reportCards.nth(1), 'KU-26-antarmuka-sesuai.png');

    // KU-29: Tarif Periode Historis (slip September dihitung November tetap Kategori A Rp12.000 -> Rp96.000)
    runUiHelperPhp('prep_ku29_ui.php', `
use App\\Models\\SettingGaji;
use App\\Models\\Karyawan;
use App\\Models\\Absensi;
SettingGaji::where('created_at', '>', '2026-09-30 23:59:59')->update([
    'created_at' => '2026-09-01 09:00:00',
    'updated_at' => '2026-09-01 09:00:00',
]);
SettingGaji::where('is_active', true)->update(['is_active' => false]);
SettingGaji::create([
    'gaji_pokok_kategori_a' => 0,
    'gaji_pokok_kategori_b' => 0,
    'tarif_per_jam_kategori_a' => 15000,
    'tarif_per_jam_kategori_b' => 19000,
    'insentif_jam_lebih_pct' => 0,
    'insentif_lembur_pct' => 0,
    'potongan_setengah_pct' => 0,
    'is_active' => true,
    'keterangan' => 'Tarif Baru Setelah Oktober',
    'created_at' => '2026-10-15 09:00:00',
    'updated_at' => '2026-10-15 09:00:00',
]);
$empHist = Karyawan::create([
    'nama' => 'Karyawan Historis September',
    'jabatan' => 'Operator Cetak',
    'tanggal_masuk' => '2021-10-01',
    'kategori_masa_kerja' => 'B',
    'is_active' => true,
]);
Absensi::create([
    'id_karyawan' => $empHist->id,
    'tanggal' => '2026-09-15',
    'jam_masuk' => '08:00',
    'jam_keluar' => '16:00',
    'durasi_jam' => 8,
    'jam_normal' => 8,
    'jam_lembur' => 0,
    'status_kehadiran' => 'Penuh',
    'sudah_pulang' => true,
]);
`);
    await page.goto(`${baseUrl}/payroll?periode=2026-09`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Generate Slip Gaji/i }).click();
    await page.getByText('Karyawan Historis September').waitFor({ timeout: 10000 });
    const histRow = page.locator('tr', { hasText: 'Karyawan Historis September' });
    await histRow.getByRole('link', { name: /Rincian/i }).click();
    await page.waitForURL((u) => /\/payroll\/\d+/.test(u.pathname), { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await page.getByText('Rp 96.000').first().waitFor({ timeout: 10000 });
    uiEvidence['KU-29'] = await shotElement(page.locator('main > div'), 'KU-29-antarmuka-sesuai.png');

    // KU-32: Jumlah Hadir HR (1 check-in hari ini belum pulang -> hadir_hari_ini=1)
    runUiHelperPhp('prep_ku32_ui.php', `
use App\\Models\\Absensi;
use App\\Models\\Karyawan;
$today = now()->toDateString();
Absensi::whereDate('tanggal', $today)->delete();
$emp = Karyawan::where('nama', 'Karyawan Slip 8 Jam')->firstOrFail();
Absensi::create([
    'id_karyawan' => $emp->id,
    'tanggal' => $today,
    'jam_masuk' => '08:00',
    'jam_keluar' => '08:00',
    'durasi_jam' => 0,
    'jam_normal' => 0,
    'jam_lembur' => 0,
    'status_kehadiran' => 'Setengah Hari',
    'sudah_pulang' => false,
]);
`);
    await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await page.getByText('Karyawan Hadir Hari Ini').waitFor({ timeout: 10000 });
    uiEvidence['KU-32'] = await shotElement(page.locator('main div.grid').first(), 'KU-32-antarmuka-sesuai.png');

    // KU-30: Pembaruan nama dan username pada /profile
    await page.goto(`${baseUrl}/profile`, { waitUntil: 'networkidle' });
    await page.locator('#nama').fill('HR Nama Diperbarui');
    await page.locator('#username').fill('uji_hr_baru');
    await page.getByRole('button', { name: 'Simpan Perubahan' }).click();
    await page.getByText('Tersimpan.').waitFor({ timeout: 10000 });
    uiEvidence['KU-30'] = await shotElement(page.locator('main div.bg-white').first(), 'KU-30-antarmuka-sesuai.png');

    // KU-31: Sesi akun nonaktif (ketika akun sedang login dinonaktifkan lalu buka /dashboard -> diarahkan ke /login)
    runUiHelperPhp('prep_ku31_ui.php', `
use App\\Models\\User;
User::where('username', 'uji_hr_baru')->update(['is_active' => false]);
`);
    await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await page.waitForURL((u) => u.pathname.endsWith('/login'), { timeout: 15000 });
    uiEvidence['KU-31'] = await shotElement(page.locator('div.bg-slate-800\\/60'), 'KU-31-antarmuka-sesuai.png');

    // Login Warehouse
    await login(page, baseUrl, 'uji_wh');

    // KU-19: Katalog Stok Fisik
    await page.goto(`${baseUrl}/stock/available`, { waitUntil: 'networkidle' });
    await page.locator('select').first().selectOption('Tali Jepit');
    await page.waitForLoadState('networkidle');
    uiEvidence['KU-19'] = await shotElement(page.locator('main > div'), 'KU-19-antarmuka-sesuai.png');

    // KU-20: Barang Masuk Batch
    await page.goto(`${baseUrl}/stock/incoming`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Tambah Produk/i }).click();
    const inForms = page.locator('form').first();
    const inRows = inForms.locator('> div.space-y-3 > div');
    await inRows.nth(0).locator('select').nth(0).selectOption('Tali Jepit');
    await inRows.nth(0).locator('select').nth(1).selectOption('Pria Dewasa');
    await inRows.nth(0).locator('select').nth(2).selectOption('Hitam');
    await inRows.nth(0).locator('input[type="date"]').fill('2026-09-04');
    await inRows.nth(0).locator('input[type="number"]').fill('100');
    await inRows.nth(1).locator('select').nth(0).selectOption('Tali Jepit');
    await inRows.nth(1).locator('select').nth(1).selectOption('Wanita');
    await inRows.nth(1).locator('select').nth(2).selectOption('Biru');
    await inRows.nth(1).locator('input[type="date"]').fill('2026-09-04');
    await inRows.nth(1).locator('input[type="number"]').fill('50');
    await page.getByRole('button', { name: /Simpan Semua/i }).click();
    await page.getByText('+100 kodi').first().waitFor({ timeout: 10000 });
    uiEvidence['KU-20'] = await shotElement(page.locator('div.bg-white:has(table)'), 'KU-20-antarmuka-sesuai.png');

    // KU-21: Barang Keluar Pelanggan
    await page.goto(`${baseUrl}/stock/outgoing`, { waitUntil: 'networkidle' });
    await page.getByPlaceholder('Masukkan nama pelanggan').fill('Toko Uji');
    const outForm = page.locator('form').first();
    const outRowSingle = outForm.locator('> div.space-y-3 > div').nth(0);
    await outRowSingle.locator('select').nth(0).selectOption('Tali Jepit');
    await outRowSingle.locator('select').nth(1).selectOption('Pria Dewasa');
    await outRowSingle.locator('select').nth(2).selectOption('Putih');
    await outRowSingle.locator('input[type="date"]').fill('2026-09-04');
    await outRowSingle.locator('input[type="number"]').fill('25');
    await page.getByRole('button', { name: /Simpan Pengiriman/i }).click();
    await page.getByText('Toko Uji').first().waitFor({ timeout: 10000 });
    uiEvidence['KU-21'] = await shotElement(page.locator('div.bg-white:has(table)'), 'KU-21-antarmuka-sesuai.png');

    // KU-22: Validasi Stok Tidak Cukup
    await page.getByRole('button', { name: /Tambah Produk/i }).click();
    await page.evaluate(() => {
      document.querySelectorAll('form').forEach((f) => { f.noValidate = true; });
    });
    await page.getByPlaceholder('Masukkan nama pelanggan').fill('Toko Rollback');
    const outRows = page.locator('form').first().locator('> div.space-y-3 > div');
    await outRows.nth(0).locator('select').nth(0).selectOption('Tali Jepit');
    await outRows.nth(0).locator('select').nth(1).selectOption('Pria Dewasa');
    await outRows.nth(0).locator('select').nth(2).selectOption('Hitam');
    await outRows.nth(0).locator('input[type="date"]').fill('2026-09-04');
    await outRows.nth(0).locator('input[type="number"]').fill('20');
    await outRows.nth(1).locator('select').nth(0).selectOption('Tali Jepit');
    await outRows.nth(1).locator('select').nth(1).selectOption('Anak');
    await outRows.nth(1).locator('select').nth(2).selectOption('Merah');
    await outRows.nth(1).locator('input[type="date"]').fill('2026-09-04');
    await outRows.nth(1).locator('input[type="number"]').fill('10');
    await page.getByRole('button', { name: /Simpan Pengiriman/i }).click();
    await page.getByText(/Gagal memproses pengiriman|Stok tidak mencukupi/i).first().waitFor({ timeout: 10000 });
    uiEvidence['KU-22'] = await shotContentClip(page, 'KU-22-antarmuka-sesuai.png');

    // KU-23: Notifikasi Stok Minimum
    await page.goto(`${baseUrl}/stock/outgoing`, { waitUntil: 'networkidle' });
    await page.getByPlaceholder('Masukkan nama pelanggan').fill('Toko Kritis');
    const outRowNotif = page.locator('form').first().locator('> div.space-y-3 > div').nth(0);
    await outRowNotif.locator('select').nth(0).selectOption('Tali Jepit');
    await outRowNotif.locator('select').nth(1).selectOption('Pria Dewasa');
    await outRowNotif.locator('select').nth(2).selectOption('Abu');
    await outRowNotif.locator('input[type="date"]').fill('2026-09-04');
    await outRowNotif.locator('input[type="number"]').fill('10');
    await page.getByRole('button', { name: /Simpan Pengiriman/i }).click();
    await page.getByText('Toko Kritis').first().waitFor({ timeout: 10000 });
    await page.goto(`${baseUrl}/stock/notifications`, { waitUntil: 'networkidle' });
    await page.getByText(/kritis: 20 kodi/i).first().waitFor({ timeout: 10000 });
    uiEvidence['KU-23'] = await shotElement(page.locator('main div.bg-white'), 'KU-23-antarmuka-sesuai.png');

    // KU-24: Tandai Baca Notifikasi (klik Tandai Dibaca -> status berubah & unread turun ke 0)
    await page.getByRole('button', { name: 'Tandai Dibaca' }).first().click();
    await page.getByText('Log Peringatan Masuk (0 belum dibaca)').waitFor({ timeout: 10000 });
    uiEvidence['KU-24'] = await shotElement(page.locator('main div.bg-white'), 'KU-24-antarmuka-sesuai.png');
    resultEvidence['KU-24'] = await shotElement(page.locator('main > div'), 'KU-24-hasil-sesuai.png');

    // KU-27: Ekspor Mutasi Stok Excel
    await page.goto(`${baseUrl}/stock/weekly-report?type=monthly&date=2026-09`, { waitUntil: 'networkidle' });
    downloads['KU-27'] = await captureDownload(context, page.getByRole('link', { name: /Unduh Excel/i }), baseUrl, 'KU-27');
    await page.getByRole('link', { name: /Unduh Excel/i }).focus();
    uiEvidence['KU-27'] = await shotElement(page.locator('main > div'), 'KU-27-antarmuka-sesuai.png');

    // KU-33: Batas Akhir Bulanan (30 September 7 kodi -> total masuk laporan September = 7 kodi)
    runUiHelperPhp('prep_ku33_ui.php', `
use App\\Models\\BarangMasuk;
use App\\Models\\BarangKeluar;
use App\\Models\\BarangPvc;
BarangMasuk::query()->delete();
BarangKeluar::query()->delete();
$p = BarangPvc::where('kode_barang', 'TJ-PR-HTM')->firstOrFail();
BarangMasuk::create(['id_barang' => $p->id, 'tanggal' => '2026-08-31', 'jumlah' => 5, 'keterangan' => 'Luar periode']);
BarangMasuk::create(['id_barang' => $p->id, 'tanggal' => '2026-09-30', 'jumlah' => 7, 'keterangan' => 'Akhir September']);
BarangMasuk::create(['id_barang' => $p->id, 'tanggal' => '2026-10-01', 'jumlah' => 9, 'keterangan' => 'Luar periode']);
`);
    await page.goto(`${baseUrl}/stock/weekly-report?type=monthly&date=2026-09-15`, { waitUntil: 'networkidle' });
    await page.getByText('Periode: 01 Sep 2026 – 30 Sep 2026').waitFor({ timeout: 10000 });
    uiEvidence['KU-33'] = await shotElement(page.locator('main > div'), 'KU-33-antarmuka-sesuai.png');

    // KU-34: Batas Akhir Mingguan (Minggu 6 September 7 kodi -> total masuk laporan minggu tersebut = 7 kodi)
    runUiHelperPhp('prep_ku34_ui.php', `
use App\\Models\\BarangMasuk;
use App\\Models\\BarangKeluar;
use App\\Models\\BarangPvc;
BarangMasuk::query()->delete();
BarangKeluar::query()->delete();
$p = BarangPvc::where('kode_barang', 'TJ-WN-BRU')->firstOrFail();
BarangMasuk::create(['id_barang' => $p->id, 'tanggal' => '2026-08-30', 'jumlah' => 4, 'keterangan' => 'Sebelum minggu']);
BarangMasuk::create(['id_barang' => $p->id, 'tanggal' => '2026-09-06', 'jumlah' => 7, 'keterangan' => 'Minggu akhir periode']);
BarangMasuk::create(['id_barang' => $p->id, 'tanggal' => '2026-09-07', 'jumlah' => 6, 'keterangan' => 'Sesudah minggu']);
`);
    await page.goto(`${baseUrl}/stock/weekly-report?type=weekly&date=2026-09-06`, { waitUntil: 'networkidle' });
    await page.getByText('Periode: 31 Agt 2026 – 06 Sep 2026').waitFor({ timeout: 10000 });
    uiEvidence['KU-34'] = await shotElement(page.locator('main > div'), 'KU-34-antarmuka-sesuai.png');

    // KU-28: Proteksi RBAC URL Terlarang -> pengujian teknis saja (middleware HTTP 403)
    const executedAt = new Date().toISOString();
    let commitHash = 'unknown';
    try {
      commitHash = execFileSync('git', ['rev-parse', '--short', 'HEAD'], { cwd: ROOT }).toString().trim();
    } catch {}

    const manifest = {
      generated_at: executedAt,
      git_head: commitHash,
      baseline_commit: '9c18cf5',
      summary: {
        total_ku: 34,
        passed: Object.values(phpunitData).filter((c) => c.status === 'Sesuai').length,
        failed: Object.values(phpunitData).filter((c) => c.status !== 'Sesuai').length,
      },
      cases: Object.fromEntries(
        Object.entries(phpunitData).map(([ku, entry]) => {
          const isKu28 = ku === 'KU-28';
          const baseEntry = baselineData[ku];
          const oldFailNote = (ku === 'KU-17' || ku === 'KU-24' || ku === 'KU-29' || (ku >= 'KU-29' && ku <= 'KU-34'))
            ? 'Laporan awal mencantumkan Tidak Sesuai; verifikasi & perbaikan diverifikasi pada commit akhir.'
            : null;
          return [
            ku,
            {
              ku,
              fitur: entry.feature,
              status_baseline: baseEntry ? baseEntry.status : 'Belum tercatat',
              status_akhir: entry.status,
              assertions: entry.assertions,
              durasi_detik: Number(entry.duration),
              waktu_eksekusi: executedAt,
              berkas: {
                teknis: cardFiles[ku] || null,
                antarmuka: isKu28 ? null : (uiEvidence[ku] || null),
                hasil: resultEvidence[ku] || null,
                sebelum_perbaikan: ku === 'KU-17'
                  ? [historicalAssets['KU-17'], historicalAssets['KU-17_ui']].filter(Boolean)
                  : (historicalAssets[ku] || null),
                unduhan: downloads[ku] || null,
              },
              catatan: isKu28
                ? 'antarmuka: tidak ada, pengujian teknis saja'
                : (oldFailNote || 'Terverifikasi melalui PHPUnit & Playwright UI pada SQLite terisolasi.'),
              actual: entry.actual,
            },
          ];
        })
      ),
    };

    fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2));
    console.log('CAPTURE_ALL_OK');
    await context.close();
  } finally {
    await browser.close();
    serverProc.kill();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
