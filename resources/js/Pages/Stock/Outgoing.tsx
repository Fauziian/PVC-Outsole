import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm, router } from "@inertiajs/react";
import { Plus, Search, X, Check, ShoppingBag } from "lucide-react";

interface BarangPvc {
  id: number;
  nama_barang: string;
  kode_barang: string;
  satuan: string;
  stok_saat_ini: number;
}

interface Transaction {
  id: number;
  tanggal: string;
  jumlah: number;
  tujuan_penggunaan: string;
  keterangan?: string;
  barang_pvc: {
    nama_barang: string;
    kode_barang: string;
    satuan: string;
  };
}

interface OutgoingProps {
  transactions: {
    data: Transaction[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
  };
  barang_list: BarangPvc[];
  filters: {
    search?: string;
  };
}

export default function Outgoing({ transactions, barang_list, filters }: OutgoingProps) {
  const [search, setSearch] = useState(filters.search || "");
  const [modalOpen, setModalOpen] = useState(false);

  const { data, setData, post, errors, reset, processing } = useForm({
    id_barang: barang_list[0]?.id || "",
    tanggal: new Date().toISOString().split("T")[0],
    jumlah: 50,
    tujuan_penggunaan: "",
    keterangan: "",
  });

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(route("stock.outgoing"), { search }, { preserveState: true });
  };

  const openLogModal = () => {
    reset();
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route("stock.outgoing.store"), {
      onSuccess: () => {
        setModalOpen(false);
        reset();
      }
    });
  };

  // Cari stok barang yang sedang terpilih untuk memberi info batas max
  const selectedBarangInfo = barang_list.find(b => b.id === Number(data.id_barang));

  return (
    <SumberPvcLayout>
      <Head title="Pencatatan Barang Keluar" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Transaksi Barang Keluar (Produksi)</h1>
          <p className="text-xs text-slate-400">Catat pemakaian bahan baku PVC Compound untuk kebutuhan lini produksi cetak outsole jepit.</p>
        </div>
        <button
          onClick={openLogModal}
          disabled={barang_list.length === 0}
          className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all cursor-pointer self-start disabled:opacity-50"
        >
          <Plus size={14} />
          Catat Pengeluaran Bahan
        </button>
      </div>

      {/* SEARCH AND FILTER */}
      <div className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Cari lini produksi atau nama bahan..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
          />
        </form>
      </div>

      {/* TABLE */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                <th className="px-6 py-4">ID Transaksi</th>
                <th className="px-6 py-4">Bahan Baku</th>
                <th className="px-6 py-4">Tanggal Pakai</th>
                <th className="px-6 py-4">Jumlah Pengeluaran</th>
                <th className="px-6 py-4">Tujuan Penggunaan</th>
                <th className="px-6 py-4">Verifikator Gudang</th>
                <th className="px-6 py-4">Keterangan</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50 text-xs">
              {transactions.data.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center text-slate-400">
                    Tidak ada transaksi pengeluaran barang yang tercatat.
                  </td>
                </tr>
              ) : (
                transactions.data.map((t) => (
                  <tr key={t.id} className="hover:bg-slate-50/50">
                    <td className="px-6 py-4">
                      <span className="font-mono font-bold text-slate-700">
                        BK-{String(t.id).padStart(4, "0")}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <p className="font-semibold text-slate-800">{t.barang_pvc.nama_barang}</p>
                      <p className="text-[10px] text-slate-400 font-mono mt-0.5">{t.barang_pvc.kode_barang}</p>
                    </td>
                    <td className="px-6 py-4 text-slate-500 font-semibold">{t.tanggal}</td>
                    <td className="px-6 py-4 text-red-500 font-black">
                      -{t.jumlah} {t.barang_pvc.satuan}
                    </td>
                    <td className="px-6 py-4 text-slate-700 font-semibold">{t.tujuan_penggunaan}</td>
                    <td className="px-6 py-4">
                      <span className="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-200">
                        <Check size={10} /> SELESAI
                      </span>
                    </td>
                    <td className="px-6 py-4 text-slate-400 max-w-xs truncate">{t.keterangan || "-"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* PAGINATION */}
        {transactions.total > 10 && (
          <div className="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <span className="text-xs text-slate-400">
              Menampilkan {transactions.data.length} dari {transactions.total} transaksi
            </span>
            <div className="flex gap-2">
              {transactions.links.map((link: any, idx: number) => (
                <button
                  key={idx}
                  onClick={() => link.url && router.get(link.url)}
                  disabled={!link.url || link.active}
                  className={`px-3 py-1.5 text-xs font-bold rounded ${
                    link.active 
                      ? "bg-blue-600 text-white" 
                      : "border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 disabled:opacity-40"
                  }`}
                  dangerouslySetInnerHTML={{ __html: link.label }}
                />
              ))}
            </div>
          </div>
        )}
      </div>

      {/* FORM MODAL */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setModalOpen(false)} />
          <div className="relative bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-md flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
            
            <div className="flex items-center gap-2.5 px-6 py-4 border-b border-slate-100 text-slate-800">
              <ShoppingBag className="text-blue-600" size={18} />
              <h2 className="text-sm font-bold">Catat Pemakaian Bahan Baku</h2>
              <button onClick={() => setModalOpen(false)} className="ml-auto w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                <X size={15} />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4 text-slate-700">
              
              {/* Bahan baku */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Bahan Baku PVC</label>
                <select
                  value={data.id_barang}
                  onChange={(e) => setData("id_barang", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white font-semibold"
                >
                  {barang_list.map((b) => (
                    <option key={b.id} value={b.id}>
                      {b.nama_barang} ({b.kode_barang}) - Tersedia: {b.stok_saat_ini} {b.satuan}
                    </option>
                  ))}
                </select>
              </div>

              {/* Tanggal & Jumlah */}
              <div className="grid grid-cols-2 gap-4">
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tanggal Dipakai</label>
                  <input
                    type="date"
                    required
                    value={data.tanggal}
                    onChange={(e) => setData("tanggal", e.target.value)}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                  />
                </div>

                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kuantitas Keluar</label>
                  <input
                    type="number"
                    required
                    value={data.jumlah}
                    onChange={(e) => setData("jumlah", Number(e.target.value))}
                    max={selectedBarangInfo?.stok_saat_ini}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                  />
                  {selectedBarangInfo && (
                    <span className="text-[9px] text-slate-400 font-semibold">
                      Max: {selectedBarangInfo.stok_saat_ini} {selectedBarangInfo.satuan}
                    </span>
                  )}
                  {errors.jumlah && <span className="text-[10px] text-red-500">{errors.jumlah}</span>}
                </div>
              </div>

              {/* Lini Produksi */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tujuan Penggunaan (Lini/Mesin)</label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Lini Cetak Outsole A, Tali Jepit B..."
                  value={data.tujuan_penggunaan}
                  onChange={(e) => setData("tujuan_penggunaan", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold"
                />
                {errors.tujuan_penggunaan && <span className="text-[10px] text-red-500">{errors.tujuan_penggunaan}</span>}
              </div>

              {/* Keterangan */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Keterangan Tambahan</label>
                <textarea
                  placeholder="Opsional (misal: order cetakan tipe sandal X)..."
                  value={data.keterangan}
                  onChange={(e) => setData("keterangan", e.target.value)}
                  rows={2}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none resize-none font-medium"
                />
              </div>

              {/* Submit */}
              <div className="flex gap-3 justify-end pt-4 border-t border-slate-100">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="px-4 py-2 border border-slate-200 text-slate-600 text-xs font-bold rounded-lg hover:bg-slate-50 transition-colors"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={processing}
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors shadow-lg shadow-blue-600/10"
                >
                  Catat Pengeluaran
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

    </SumberPvcLayout>
  );
}
