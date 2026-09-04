import { useState } from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, router, useForm } from "@inertiajs/react";
import { Calendar, Clock, Moon, Trash2, X } from "lucide-react";

type Shift = "Pagi" | "Sore" | "Malam";

interface Attendance {
  id: number;
  jam_masuk: string;
  jam_keluar: string;
  durasi_jam: number;
  shift: Shift | null;
  jam_normal: number;
  jam_lembur: number;
  status_kehadiran: string;
  keterangan?: string;
}

interface AttendanceRow {
  id_karyawan: number;
  nama: string;
  jabatan: string;
  absensi?: Attendance | null;
}

interface IndexProps {
  rows: AttendanceRow[];
  stats: { total: number; penuh: number; jam_lebih: number; lembur: number; setengah_hari: number; absen: number };
  tanggal: string;
  formatted_tanggal: string;
}

const defaultJam: Record<Shift, { masuk: string; keluar: string }> = {
  Pagi: { masuk: "08:00", keluar: "16:00" },
  Sore: { masuk: "16:00", keluar: "00:00" },
  Malam: { masuk: "00:00", keluar: "08:00" },
};

export default function Index({ rows, stats, tanggal, formatted_tanggal }: IndexProps) {
  const [selectedTanggal, setSelectedTanggal] = useState(tanggal);
  const [modalOpen, setModalOpen] = useState(false);
  const [activeKaryawan, setActiveKaryawan] = useState<AttendanceRow | null>(null);
  const { data, setData, post, processing, errors, reset } = useForm({
    id_karyawan: "",
    tanggal,
    shift: "Pagi" as Shift,
    jam_masuk: "08:00",
    jam_keluar: "16:00",
    keterangan: "",
  });

  const openLogModal = (karyawan: AttendanceRow) => {
    const shift = karyawan.absensi?.shift || "Pagi";
    setActiveKaryawan(karyawan);
    setData({
      id_karyawan: String(karyawan.id_karyawan),
      tanggal: selectedTanggal,
      shift,
      jam_masuk: karyawan.absensi?.jam_masuk || defaultJam[shift].masuk,
      jam_keluar: karyawan.absensi?.jam_keluar || defaultJam[shift].keluar,
      keterangan: karyawan.absensi?.keterangan || "",
    });
    setModalOpen(true);
  };

  const changeShift = (shift: Shift) => {
    setData((current) => ({ ...current, shift, jam_masuk: defaultJam[shift].masuk, jam_keluar: defaultJam[shift].keluar }));
  };

  const submit = (event: React.FormEvent) => {
    event.preventDefault();
    post(route("attendance.store"), {
      onSuccess: () => { setModalOpen(false); reset(); },
    });
  };

  const remove = (id: number) => {
    if (confirm("Hapus catatan absensi ini?")) router.delete(route("attendance.destroy", id));
  };

  return (
    <SumberPvcLayout>
      <Head title="Absensi & Shift" />
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-bold text-slate-800">Absensi & Shift Karyawan</h1>
          <p className="text-xs text-slate-500">Catat jam aktual. Delapan jam pertama adalah jam normal; selebihnya otomatis lembur.</p>
        </div>
        <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
          <Calendar size={14} className="text-slate-400" />
          <input type="date" value={selectedTanggal} onChange={(e) => {
            setSelectedTanggal(e.target.value);
            router.get(route("attendance.index"), { tanggal: e.target.value }, { preserveState: true });
          }} className="bg-transparent text-xs font-semibold text-slate-700 outline-none" />
        </label>
      </div>

      <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
        <Card label="Karyawan Aktif" value={stats.total} color="text-blue-600" />
        <Card label="Sudah Tercatat" value={stats.total - stats.absen} color="text-emerald-600" />
        <Card label="Ada Lembur" value={stats.jam_lebih} color="text-violet-600" />
        <Card label="Total Lembur" value={`${stats.lembur} jam`} color="text-amber-600" />
        <Card label="Belum Diisi" value={stats.absen} color="text-slate-500" />
      </div>

      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-100 bg-slate-50 px-6 py-4">
          <p className="text-xs font-bold text-slate-700">Absensi tanggal {formatted_tanggal}</p>
          <p className="mt-1 text-[11px] text-slate-400">Shift malam yang pulang keesokan hari tetap dihitung pada tanggal mulai shift.</p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px] text-left text-xs">
            <thead className="border-b border-slate-100 text-[10px] uppercase tracking-wider text-slate-400">
              <tr>
                <th className="px-5 py-4">Karyawan</th><th className="px-5 py-4">Shift</th><th className="px-5 py-4">Masuk</th><th className="px-5 py-4">Pulang</th><th className="px-5 py-4">Total</th><th className="px-5 py-4">Normal</th><th className="px-5 py-4">Lembur</th><th className="px-5 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {rows.map((row) => {
                const absensi = row.absensi;
                return <tr key={row.id_karyawan} className="hover:bg-slate-50/70">
                  <td className="px-5 py-4"><p className="font-bold text-slate-800">{row.nama}</p><p className="mt-0.5 text-[10px] text-slate-400">{row.jabatan}</p></td>
                  <td className="px-5 py-4">{absensi ? <ShiftBadge shift={absensi.shift} /> : <span className="text-slate-300">—</span>}</td>
                  <td className="px-5 py-4 font-semibold text-slate-700">{absensi?.jam_masuk || "—"}</td>
                  <td className="px-5 py-4 font-semibold text-slate-700">{absensi?.jam_keluar || "—"}</td>
                  <td className="px-5 py-4 font-bold text-slate-800">{absensi ? `${absensi.durasi_jam} jam` : "—"}</td>
                  <td className="px-5 py-4"><span className="font-bold text-emerald-600">{absensi ? `${absensi.jam_normal} jam` : "—"}</span></td>
                  <td className="px-5 py-4"><span className={absensi?.jam_lembur ? "font-bold text-violet-600" : "text-slate-400"}>{absensi ? `${absensi.jam_lembur} jam` : "—"}</span></td>
                  <td className="px-5 py-4 text-right"><button onClick={() => openLogModal(row)} className="rounded-lg bg-slate-100 px-3 py-1.5 text-[10px] font-bold text-slate-700 hover:bg-slate-200">{absensi ? "Ubah" : "Catat"}</button>{absensi && <button onClick={() => remove(absensi.id)} className="ml-1.5 rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Hapus"><Trash2 size={14} /></button>}</td>
                </tr>;
              })}
            </tbody>
          </table>
        </div>
      </div>

      {modalOpen && activeKaryawan && <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <button className="absolute inset-0 bg-slate-900/40" onClick={() => setModalOpen(false)} aria-label="Tutup" />
        <div className="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
          <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4"><div><h2 className="text-sm font-bold text-slate-800">Catat Absensi</h2><p className="text-xs text-slate-400">{activeKaryawan.nama} · {selectedTanggal}</p></div><button onClick={() => setModalOpen(false)} className="text-slate-400"><X size={18} /></button></div>
          <form onSubmit={submit} className="space-y-4 p-6">
            <div><label className="text-[10px] font-bold uppercase tracking-wider text-slate-400">Shift</label><div className="mt-2 grid grid-cols-3 gap-2">{(["Pagi", "Sore", "Malam"] as Shift[]).map((shift) => <button type="button" key={shift} onClick={() => changeShift(shift)} className={`rounded-lg border px-2 py-2 text-xs font-bold ${data.shift === shift ? "border-blue-600 bg-blue-600 text-white" : "border-slate-200 text-slate-600"}`}>{shift}</button>)}</div></div>
            <div className="grid grid-cols-2 gap-3"><Field label="Jam Masuk"><input type="time" required value={data.jam_masuk} onChange={(e) => setData("jam_masuk", e.target.value)} className="input-time" /></Field><Field label="Jam Pulang"><input type="time" required value={data.jam_keluar} onChange={(e) => setData("jam_keluar", e.target.value)} className="input-time" /></Field></div>
            <div className="rounded-lg bg-blue-50 p-3 text-[11px] leading-relaxed text-blue-700"><Clock size={13} className="mr-1 inline" />Jika jam pulang lebih awal atau sama dengan jam masuk, sistem menganggap pulang pada hari berikutnya (shift lintas tengah malam).</div>
            {errors.jam_keluar && <p className="text-xs text-red-600">{errors.jam_keluar}</p>}
            <Field label="Keterangan (opsional)"><textarea rows={2} value={data.keterangan} onChange={(e) => setData("keterangan", e.target.value)} placeholder="Contoh: lembur pesanan pelanggan" className="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs outline-none focus:border-blue-500" /></Field>
            <div className="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onClick={() => setModalOpen(false)} className="rounded-lg px-4 py-2 text-xs font-bold text-slate-500">Batal</button><button disabled={processing} className="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white disabled:opacity-50">Simpan Absensi</button></div>
          </form>
        </div>
      </div>}
    </SumberPvcLayout>
  );
}

function Card({ label, value, color }: { label: string; value: string | number; color: string }) { return <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p className="text-[10px] font-bold uppercase tracking-wider text-slate-400">{label}</p><p className={`mt-1 text-xl font-black ${color}`}>{value}</p></div>; }
function Field({ label, children }: { label: string; children: React.ReactNode }) { return <label className="block text-[10px] font-bold uppercase tracking-wider text-slate-400">{label}<span className="mt-1.5 block">{children}</span></label>; }
function ShiftBadge({ shift }: { shift: Shift | null }) { const icon = shift === "Malam" ? <Moon size={12} /> : <Clock size={12} />; return <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">{icon}{shift || "—"}</span>; }
