<?php

namespace App\Http\Controllers;

use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Exception;
use Carbon\Carbon;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Tampilkan katalog dan stok barang jadi.
     */
    public function index(Request $request): Response
    {
        $query = BarangPvc::query();

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('jenis', 'like', "%{$search}%")
                  ->orWhere('warna', 'like', "%{$search}%");
            });
        }

        // Filter status stok (kritis, menipis, aman)
        if ($status = $request->input('status')) {
            if ($status === 'kritis') {
                $query->whereRaw('stok_saat_ini <= stok_minimum');
            } elseif ($status === 'menipis') {
                $query->whereRaw('stok_saat_ini <= (stok_minimum * 1.3) AND stok_saat_ini > stok_minimum');
            } elseif ($status === 'aman') {
                $query->whereRaw('stok_saat_ini > (stok_minimum * 1.3)');
            }
        }

        $items = $query->orderBy('nama_barang', 'asc')->paginate(10)->withQueryString();

        // Tambahkan computed status_stok ke paginated items
        $items->getCollection()->transform(function($item) {
            $item->status_stok = $item->status_stok; // triggers accessor
            return $item;
        });

        return Inertia::render('Stock/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Daftarkan master barang PVC baru.
     */
    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kode_barang' => ['required', 'string', 'unique:barang_pvc,kode_barang', 'max:50'],
            'kategori' => ['required', 'in:Tali Jepit,Outsole'],
            'jenis' => ['required', 'string', 'max:100'],
            'warna' => ['required', 'string', 'max:100'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'stok_saat_ini' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['satuan'] = 'kodi';
        BarangPvc::create($validated);

        return redirect()->back()->with('success', 'Produk jadi berhasil didaftarkan.');
    }

    /**
     * Update data master barang PVC.
     */
    public function updateItem(Request $request, BarangPvc $item): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kode_barang' => ['required', 'string', 'max:50', 'unique:barang_pvc,kode_barang,' . $item->id],
            'kategori' => ['required', 'in:Tali Jepit,Outsole'],
            'jenis' => ['required', 'string', 'max:100'],
            'warna' => ['required', 'string', 'max:100'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($validated);

        return redirect()->back()->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * Hapus barang PVC.
     */
    public function destroyItem(BarangPvc $item): RedirectResponse
    {
        // Cek jika barang sudah memiliki riwayat transaksi
        if ($item->barangMasuk()->exists() || $item->barangKeluar()->exists()) {
            return redirect()->back()->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }

        $item->delete();
        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Tampilkan riwayat barang masuk.
     */
    public function incoming(Request $request): Response
    {
        $query = BarangMasuk::with('barangPvc');

        if ($search = $request->input('search')) {
            $query->whereHas('barangPvc', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $barangList = BarangPvc::orderBy('kategori')->orderBy('jenis')->orderBy('warna')
            ->get(['id', 'nama_barang', 'kode_barang', 'kategori', 'jenis', 'warna', 'satuan']);

        return Inertia::render('Stock/Incoming', [
            'transactions' => $transactions,
            'barang_list' => $barangList,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Simpan transaksi barang masuk (Restock).
     */
    public function storeIncoming(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'distinct', 'exists:barang_pvc,id'],
            'items.*.tanggal' => ['required', 'date'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->stockService->recordIncomingBatch($validated['items']);

        return redirect()->back()->with('success', count($validated['items']).' hasil cetak berhasil disimpan ke stok.');
    }

    /**
     * Tampilkan riwayat barang keluar.
     */
    public function outgoing(Request $request): Response
    {
        $query = BarangKeluar::with('barangPvc');

        if ($search = $request->input('search')) {
            $query->whereHas('barangPvc', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            })->orWhere('tujuan_penggunaan', 'like', "%{$search}%");
        }

        $transactions = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $barangList = BarangPvc::orderBy('kategori')->orderBy('jenis')->orderBy('warna')
            ->get(['id', 'nama_barang', 'kode_barang', 'kategori', 'jenis', 'warna', 'satuan', 'stok_saat_ini']);

        return Inertia::render('Stock/Outgoing', [
            'transactions' => $transactions,
            'barang_list' => $barangList,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Simpan transaksi barang keluar (Usage / Production).
     */
    public function storeOutgoing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pelanggan' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'distinct', 'exists:barang_pvc,id'],
            'items.*.tanggal' => ['required', 'date'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $items = array_map(fn ($item) => [
                ...$item,
                'tujuan_penggunaan' => $validated['pelanggan'],
            ], $validated['items']);
            $this->stockService->recordOutgoingBatch($items);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', count($validated['items']).' produk berhasil dikirim ke pelanggan.');
    }

    /**
     * Ringkasan mutasi stok per minggu untuk staf gudang.
     */
    public function weeklyReport(Request $request): Response
    {
        $validated = $request->validate(['week' => ['nullable', 'date']]);
        $requestedDate = $validated['week'] ?? now()->toDateString();
        $start = Carbon::parse($requestedDate)->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        $incoming = BarangMasuk::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('id_barang, SUM(jumlah) as total')
            ->groupBy('id_barang')
            ->pluck('total', 'id_barang');

        $outgoing = BarangKeluar::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('id_barang, SUM(jumlah) as total')
            ->groupBy('id_barang')
            ->pluck('total', 'id_barang');

        $productIds = $incoming->keys()->merge($outgoing->keys())->unique();
        $rows = BarangPvc::query()
            ->whereIn('id', $productIds)
            ->orderBy('kategori')->orderBy('jenis')->orderBy('warna')
            ->get(['id', 'kategori', 'jenis', 'warna'])
            ->map(fn (BarangPvc $product) => [
                'id' => $product->id,
                'kategori' => $product->kategori,
                'jenis' => $product->jenis,
                'warna' => $product->warna,
                'masuk' => (int) ($incoming[$product->id] ?? 0),
                'keluar' => (int) ($outgoing[$product->id] ?? 0),
                'selisih' => (int) ($incoming[$product->id] ?? 0) - (int) ($outgoing[$product->id] ?? 0),
            ])->values();

        return Inertia::render('Stock/WeeklyReport', [
            'period' => [
                'week' => $start->toDateString(),
                'start' => $start->translatedFormat('d M Y'),
                'end' => $end->translatedFormat('d M Y'),
            ],
            'rows' => $rows,
            'totals' => [
                'masuk' => (int) $incoming->sum(),
                'keluar' => (int) $outgoing->sum(),
                'selisih' => (int) $incoming->sum() - (int) $outgoing->sum(),
            ],
        ]);
    }

    /**
     * Tampilkan daftar notifikasi stok kritis.
     */
    public function notifications(): Response
    {
        $notifications = NotifikasiStok::with('barangPvc')
            ->orderBy('is_read', 'asc')
            ->orderBy('tanggal', 'desc')
            ->get();

        return Inertia::render('Stock/Notifications', [
            'notifications' => $notifications,
            'unread_count' => NotifikasiStok::where('is_read', false)->count(),
        ]);
    }

    /**
     * Tandai notifikasi stok telah dibaca.
     */
    public function markAsRead(NotifikasiStok $notification): RedirectResponse
    {
        $notification->update(['is_read' => true]);
        return redirect()->back()->with('success', 'Notifikasi ditandai dibaca.');
    }

    /**
     * Tandai semua notifikasi dibaca.
     */
    public function readAllNotifications(): RedirectResponse
    {
        NotifikasiStok::where('is_read', false)->update(['is_read' => true]);
        return redirect()->back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }
}
