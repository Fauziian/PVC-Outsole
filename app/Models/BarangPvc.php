<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: BarangPvc (BARANG_PVC)
 * Sesuai ERD Proposal: id_barang, nama_barang, satuan, stok_minimum, stok_saat_ini
 *
 * stok_saat_ini di-update otomatis via BarangPvcObserver setiap ada
 * transaksi BarangMasuk atau BarangKeluar.
 */
class BarangPvc extends Model
{
    use HasFactory;

    protected $table = 'barang_pvc';

    protected $fillable = [
        'nama_barang',
        'kode_barang',
        'kategori',
        'jenis',
        'warna',
        'satuan',
        'stok_minimum',
        'stok_saat_ini',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'stok_minimum'  => 'integer',
            'stok_saat_ini' => 'integer',
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────────

    /**
     * Dapatkan status stok: 'aman', 'menipis', atau 'kritis'.
     * - kritis  : stok_saat_ini <= stok_minimum
     * - menipis : stok_saat_ini <= stok_minimum * 1.3
     * - aman    : di atas threshold menipis
     */
    public function getStatusStokAttribute(): string
    {
        if ($this->stok_saat_ini <= $this->stok_minimum) {
            return 'kritis';
        }
        if ($this->stok_saat_ini <= ($this->stok_minimum * 1.3)) {
            return 'menipis';
        }
        return 'aman';
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    public function barangMasuk(): HasMany
    {
        return $this->hasMany(BarangMasuk::class, 'id_barang');
    }

    public function barangKeluar(): HasMany
    {
        return $this->hasMany(BarangKeluar::class, 'id_barang');
    }

    public function notifikasiStok(): HasMany
    {
        return $this->hasMany(NotifikasiStok::class, 'id_barang');
    }
}
