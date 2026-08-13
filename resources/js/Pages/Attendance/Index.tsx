import React, { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm, router } from "@inertiajs/react";
import { Clock, Plus, Trash2, Calendar, Check, AlertCircle, RefreshCw, X } from "lucide-react";

interface AttendanceRow {
  id_karyawan: number;
  nama: string;
  jabatan: string;
  absensi?: {
    id: number;
    jam_masuk: string;
    jam_keluar: string;
    durasi_jam: number;
    status_kehadiran: string;
    keterangan?: string;
  } | null;
}

interface IndexProps {
  rows: AttendanceRow[];
  stats: {
    total: number;
    penuh: number;
    jam_lebih: number;
    lembur: number;
    setengah_hari: number;
    absen: number;
  };
  tanggal: string;
  formatted_tanggal: string;
}

export default function Index({ rows, stats, tanggal, formatted_tanggal }: IndexProps) {
  const [selectedTanggal, setSelectedTanggal] = useState(tanggal);
  const [modalOpen, setModalOpen] = useState(false);
  const [activeKaryawan, setActiveKaryawan] = useState<AttendanceRow | null>(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    id_karyawan: "",
    tanggal: tanggal,
    jam_masuk: "08:00",
    jam_keluar: "17:00",
    keterangan: "",
  });

  const handleTanggalChange = (val: string) => {
    setSelectedTanggal(val);
    router.get(route("attendance.index"), { tanggal: val }, { preserveState: true });
  };

  const openLogModal = (karyawan: AttendanceRow) => {
    setActiveKaryawan(karyawan);
    setData({
      id_karyawan: String(karyawan.id_karyawan),
      tanggal: selectedTanggal,
      jam_masuk: karyawan.absensi?.jam_masuk || "08:00",
      jam_keluar: karyawan.absensi?.jam_keluar || "17:00",
      keterangan: karyawan.absensi?.keterangan || "",
    });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route("attendance.store"), {
      onSuccess: () => {
        setModalOpen(false);
        reset();
      }
    });
  };

  const handleDelete = (id: number) => {
    if (confirm("Apakah Anda yakin ingin menghapus data kehadiran karyawan ini untuk tanggal hari ini?")) {
      router.delete(route("attendance.destroy", id));
    }
  };

  return (
    <SumberPvcLayout>
      <Head title="Kehadiran & Jam Kerja" />

      {/* Header bar */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Kehadiran & Jam Kerja Karyawan</h1>
          <p className="text-xs text-slate-400">Verifikasi jam masuk/keluar harian untuk kalkulasi potongan kehadiran dan insentif lembur.</p>
        </div>

        {/* Date picker */}
        <div className="flex items-center gap-2 bg-white px-3 py-1.5 border border-slate-200 rounded-lg shadow-sm">
          <Calendar size={14} className="text-slate-400" />
          <input
            type="date"
            value={selectedTanggal}
            onChange={(e) => handleTanggalChange(e.target.value)}
            className="text-xs font-semibold text-slate-700 focus:outline-none bg-transparent"
          />
        </div>
      </div>

      {/* STATS KEHADIRAN HARIAN */}
      <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
        <MiniCard label="Total Karyawan" value={stats.total} color="border-blue-200" textClr="text-blue-600" />
        <MiniCard label="Hadir Penuh" value={stats.penuh} color="border-emerald-200" textClr="text-emerald-600" />
        <MiniCard label="Jam Lebih (8-12j)" value={stats.jam_lebih} color="border-indigo-200" textClr="text-indigo-600" />
        <MiniCard label="Lembur (>15j)" value={stats.lembur} color="border-purple-200" textClr="text-purple-600" />
        <MiniCard label="Setengah Hari" value={stats.setengah_hari} color="border-amber-200" textClr="text-amber-600" />
        <MiniCard label="Absen/Belum Input" value={stats.absen} color="border-slate-200" textClr="text-slate-500" />
      </div>

      {/* MAIN DATA BLOCK */}
      <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div className="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
          <span className="text-xs font-bold text-slate-600">
            Daftar Kehadiran Tanggal: <strong className="text-slate-800">{formatted_tanggal}</strong>
          </span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                <th className="px-6 py-4">Karyawan</th>
                <th className="px-6 py-4">Jabatan</th>
                <th className="px-6 py-4">Jam Masuk</th>
                <th className="px-6 py-4">Jam Keluar</th>
                <th className="px-6 py-4">Durasi Bersih</th>
                <th className="px-6 py-4">Status Kehadiran</th>
                <th className="px-6 py-4">Keterangan</th>
                <th className="px-6 py-4 text-right">Verifikasi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50 text-xs">
              {rows.map((row) => (
                <tr key={row.id_karyawan} className="hover:bg-slate-50/50">
                  <td className="px-6 py-4 font-semibold text-slate-800">{row.nama}</td>
                  <td className="px-6 py-4 text-slate-500 font-medium">{row.jabatan}</td>
                  
                  {/* Jam Masuk */}
                  <td className="px-6 py-4 text-slate-700 font-semibold">
                    {row.absensi ? row.absensi.jam_masuk : <span className="text-slate-300 font-normal">-</span>}
                  </td>
                  
                  {/* Jam Keluar */}
                  <td className="px-6 py-4 text-slate-700 font-semibold">
                    {row.absensi ? row.absensi.jam_keluar : <span className="text-slate-300 font-normal">-</span>}
                  </td>

                  {/* Durasi Jam */}
                  <td className="px-6 py-4 font-bold text-slate-800">
                    {row.absensi ? `${row.absensi.durasi_jam} jam` : <span className="text-slate-300 font-normal">-</span>}
                  </td>

                  {/* Status Kehadiran */}
                  <td className="px-6 py-4">
                    {row.absensi ? (
                      <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-bold border ${
                        row.absensi.status_kehadiran === "Penuh" ? "bg-green-50 text-green-700 border-green-200" :
                        row.absensi.status_kehadiran === "Lembur" ? "bg-purple-50 text-purple-700 border-purple-200" :
                        row.absensi.status_kehadiran === "Jam Lebih" ? "bg-indigo-50 text-indigo-700 border-indigo-200" :
                        "bg-amber-50 text-amber-700 border-amber-200"
                      }`}>
                        {row.absensi.status_kehadiran.toUpperCase()}
                      </span>
                    ) : (
                      <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-400 border border-slate-200">
                        BELUM MENGISI
                      </span>
                    )}
                  </td>

                  {/* Keterangan */}
                  <td className="px-6 py-4 text-slate-400 max-w-xs truncate">
                    {row.absensi?.keterangan || "-"}
                  </td>

                  {/* Aksi */}
                  <td className="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                    <button
                      onClick={() => openLogModal(row)}
                      className="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold rounded transition-all cursor-pointer"
                    >
                      {row.absensi ? "Ubah Jam" : "Input Jam"}
                    </button>
                    {row.absensi && (
                      <button
                        onClick={() => handleDelete(row.absensi!.id)}
                        className="p-1.5 hover:bg-red-50 text-slate-400 hover:text-red-600 rounded transition-colors"
                        title="Hapus Absensi"
                      >
                        <Trash2 size={13} />
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* LOG INPUT MODAL */}
      {modalOpen && activeKaryawan && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setModalOpen(false)} />
          <div className="relative bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-sm flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
            
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <div>
                <h2 className="text-xs font-bold text-slate-800">Catat Kehadiran Karyawan</h2>
                <p className="text-[10px] text-slate-400 mt-0.5">{activeKaryawan.nama}</p>
              </div>
              <button onClick={() => setModalOpen(false)} className="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
                <X size={15} />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <input type="hidden" value={data.id_karyawan} />
              <input type="hidden" value={data.tanggal} />

              <div className="grid grid-cols-2 gap-4">
                {/* Jam Masuk */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jam Masuk</label>
                  <input
                    type="time"
                    required
                    value={data.jam_masuk}
                    onChange={(e) => setData("jam_masuk", e.target.value)}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                  />
                </div>

                {/* Jam Keluar */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jam Keluar</label>
                  <input
                    type="time"
                    required
                    value={data.jam_keluar}
                    onChange={(e) => setData("jam_keluar", e.target.value)}
                    className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                  />
                </div>
              </div>
              {errors.jam_keluar && <span className="text-[10px] text-red-500">{errors.jam_keluar}</span>}

              {/* Keterangan */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Catatan / Keterangan</label>
                <textarea
                  placeholder="Opsional (misal: lembur mesin, izin terlambat, dll)..."
                  value={data.keterangan}
                  onChange={(e) => setData("keterangan", e.target.value)}
                  rows={2}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none resize-none"
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
                  Verifikasi Jam
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

    </SumberPvcLayout>
  );
}

function MiniCard({ label, value, color, textClr }: { label: string, value: number, color: string, textClr: string }) {
  return (
    <div className={`bg-white rounded-xl border-t-4 p-4 shadow-sm text-center ${color}`}>
      <span className="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">{label}</span>
      <span className={`text-xl font-black mt-1 block ${textClr}`}>{value}</span>
    </div>
  );
}
