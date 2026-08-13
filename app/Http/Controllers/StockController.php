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

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Tampilkan data master stok barang PVC.
     */
    public function index(Request $request): Response
    {
        $query = BarangPvc::query();

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
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
            'satuan' => ['required', 'string', 'max:20'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'stok_saat_ini' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        BarangPvc::create($validated);

        return redirect()->back()->with('success', 'Bahan baku PVC berhasil didaftarkan.');
    }

    /**
     * Update data master barang PVC.
     */
    public function updateItem(Request $request, BarangPvc $item): RedirectResponse
    {
        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kode_barang' => ['required', 'string', 'max:50', 'unique:barang_pvc,kode_barang,' . $item->id],
            'satuan' => ['required', 'string', 'max:20'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($validated);

        return redirect()->back()->with('success', 'Data bahan baku PVC berhasil diperbarui.');
    }

    /**
     * Hapus barang PVC.
     */
    public function destroyItem(BarangPvc $item): RedirectResponse
    {
        // Cek jika barang sudah memiliki riwayat transaksi
        if ($item->barangMasuk()->exists() || $item->barangKeluar()->exists()) {
            return redirect()->back()->with('error', 'Bahan baku tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }

        $item->delete();
        return redirect()->back()->with('success', 'Bahan baku PVC berhasil dihapus.');
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
            })->orWhere('pemasok', 'like', "%{$search}%");
        }

        $transactions = $query->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $barangList = BarangPvc::orderBy('nama_barang', 'asc')->get(['id', 'nama_barang', 'kode_barang', 'satuan']);

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
            'id_barang' => ['required', 'exists:barang_pvc,id'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'pemasok' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->stockService->recordIncoming($validated);

        return redirect()->back()->with('success', 'Transaksi barang masuk berhasil dicatat. Stok ditambahkan.');
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

        $barangList = BarangPvc::where('stok_saat_ini', '>', 0)
            ->orderBy('nama_barang', 'asc')
            ->get(['id', 'nama_barang', 'kode_barang', 'satuan', 'stok_saat_ini']);

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
            'id_barang' => ['required', 'exists:barang_pvc,id'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tujuan_penggunaan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->stockService->recordOutgoing($validated);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Transaksi barang keluar berhasil dicatat. Stok dikurangi.');
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
