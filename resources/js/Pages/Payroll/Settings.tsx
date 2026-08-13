import React from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, useForm } from "@inertiajs/react";
import { Settings, Save, AlertCircle, Clock } from "lucide-react";

interface SettingRecord {
  id: number;
  gaji_pokok_kategori_a: number;
  gaji_pokok_kategori_b: number;
  insentif_jam_lebih_pct: number;
  insentif_lembur_pct: number;
  potongan_setengah_pct: number;
  is_active: boolean;
  keterangan?: string;
  created_at: string;
}

interface SettingsProps {
  settings: SettingRecord[];
  active_setting: SettingRecord;
}

export default function SettingsPage({ settings, active_setting }: SettingsProps) {
  const { data, setData, post, processing, errors } = useForm({
    gaji_pokok_kategori_a: active_setting?.gaji_pokok_kategori_a || 4500000,
    gaji_pokok_kategori_b: active_setting?.gaji_pokok_kategori_b || 6000000,
    insentif_jam_lebih_pct: active_setting?.insentif_jam_lebih_pct || 7,
    insentif_lembur_pct: active_setting?.insentif_lembur_pct || 17.5,
    potongan_setengah_pct: active_setting?.potongan_setengah_pct || 40,
    keterangan: "",
  });

  const idr = (val: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(val);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (confirm("Apakah Anda yakin ingin memperbarui parameter penggajian? Perubahan ini akan segera berlaku aktif untuk perhitungan periode penggajian berikutnya.")) {
      post(route("payroll.settings.update"), {
        onSuccess: () => {
          setData("keterangan", "");
        }
      });
    }
  };

  return (
    <SumberPvcLayout>
      <Head title="Pengaturan Gaji" />

      {/* Header bar */}
      <div>
        <h1 className="text-xl font-bold text-slate-800">Parameter & Pengaturan Gaji</h1>
        <p className="text-xs text-slate-400">Konfigurasi nominal Gaji Pokok Kategori A/B, insentif lembur, dan potongan kehadiran.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* FORM CONFIG (2 Kolom) */}
        <div className="lg:col-span-2 space-y-6">
          <form onSubmit={handleSubmit} className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
            
            <div className="flex items-center gap-2 border-b border-slate-100 pb-4">
              <Settings className="text-blue-500" size={18} />
              <h2 className="text-sm font-bold text-slate-800">Ubah Konfigurasi Parameter Aktif</h2>
            </div>

            {/* Warning info */}
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 text-xs text-amber-800">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              <div>
                <strong className="font-bold">Perhatian:</strong> Perubahan parameter di atas bersifat *configurable* dan akan mempengaruhi kalkulasi penggajian bulanan di masa depan. Data slip gaji masa lalu yang sudah berstatus *final* tetap disimpan aman.
              </div>
            </div>

            {/* Fields */}
            <div className="space-y-4">
              
              {/* Gaji Pokok */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Gaji Pokok Kategori A (&lt; 5 Tahun Masa Kerja)
                  </label>
                  <div className="relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                    <input
                      type="number"
                      required
                      value={data.gaji_pokok_kategori_a}
                      onChange={(e) => setData("gaji_pokok_kategori_a", Number(e.target.value))}
                      className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-slate-700"
                    />
                  </div>
                  {errors.gaji_pokok_kategori_a && <span className="text-[10px] text-red-500">{errors.gaji_pokok_kategori_a}</span>}
                </div>

                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Gaji Pokok Kategori B (&ge; 5 Tahun Masa Kerja)
                  </label>
                  <div className="relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                    <input
                      type="number"
                      required
                      value={data.gaji_pokok_kategori_b}
                      onChange={(e) => setData("gaji_pokok_kategori_b", Number(e.target.value))}
                      className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-slate-700"
                    />
                  </div>
                  {errors.gaji_pokok_kategori_b && <span className="text-[10px] text-red-500">{errors.gaji_pokok_kategori_b}</span>}
                </div>
              </div>

              {/* Insentif & Potongan Persentase */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Insentif Jam Lebih (8-12j)
                  </label>
                  <div className="relative">
                    <input
                      type="number"
                      step="0.01"
                      required
                      value={data.insentif_jam_lebih_pct}
                      onChange={(e) => setData("insentif_jam_lebih_pct", Number(e.target.value))}
                      className="w-full pl-3 pr-8 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-slate-700"
                    />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                  </div>
                  {errors.insentif_jam_lebih_pct && <span className="text-[10px] text-red-500">{errors.insentif_jam_lebih_pct}</span>}
                </div>

                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Insentif Lembur (&gt; 15j)
                  </label>
                  <div className="relative">
                    <input
                      type="number"
                      step="0.01"
                      required
                      value={data.insentif_lembur_pct}
                      onChange={(e) => setData("insentif_lembur_pct", Number(e.target.value))}
                      className="w-full pl-3 pr-8 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-slate-700"
                    />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                  </div>
                  {errors.insentif_lembur_pct && <span className="text-[10px] text-red-500">{errors.insentif_lembur_pct}</span>}
                </div>

                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Potongan Setengah Hari
                  </label>
                  <div className="relative">
                    <input
                      type="number"
                      step="0.01"
                      required
                      value={data.potongan_setengah_pct}
                      onChange={(e) => setData("potongan_setengah_pct", Number(e.target.value))}
                      className="w-full pl-3 pr-8 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-slate-700"
                    />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                  </div>
                  {errors.potongan_setengah_pct && <span className="text-[10px] text-red-500">{errors.potongan_setengah_pct}</span>}
                </div>
              </div>

              {/* Keterangan */}
              <div className="flex flex-col gap-1">
                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Keterangan / Alasan Revisi</label>
                <input
                  type="text"
                  placeholder="Opsional (misal: penyesuaian UMK baru, kesepakatan internal)..."
                  value={data.keterangan}
                  onChange={(e) => setData("keterangan", e.target.value)}
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none text-slate-700 font-semibold"
                />
              </div>

            </div>

            <div className="flex justify-end pt-4 border-t border-slate-100">
              <button
                type="submit"
                disabled={processing}
                className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-blue-500/10 transition-all cursor-pointer"
              >
                <Save size={14} />
                Simpan & Aktifkan Parameter
              </button>
            </div>

          </form>
        </div>

        {/* HISTORI REVISI (1 Kolom) */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 className="text-sm font-bold text-slate-800 flex items-center gap-1.5">
              <Clock size={16} className="text-slate-400" />
              Histori Revisi Konfigurasi
            </h3>

            <div className="space-y-3 max-h-[400px] overflow-y-auto pr-1" style={{ scrollbarWidth: "thin" }}>
              {settings.map((item) => (
                <div key={item.id} className={`p-3 rounded-xl border text-xs space-y-2 ${
                  item.is_active 
                    ? "bg-blue-50/50 border-blue-200" 
                    : "bg-slate-50/50 border-slate-100 text-slate-500"
                }`}>
                  <div className="flex justify-between items-center">
                    <span className="font-bold text-slate-700">Revisi #{item.id}</span>
                    {item.is_active && (
                      <span className="px-1.5 py-0.5 text-[9px] font-bold bg-green-50 text-green-700 border border-green-200 rounded">
                        AKTIF SAAT INI
                      </span>
                    )}
                  </div>

                  <div className="grid grid-cols-2 gap-2 text-[11px]">
                    <div>
                      <p className="text-[9px] text-slate-400 uppercase font-semibold">Gapok A</p>
                      <p className="font-bold">{idr(item.gaji_pokok_kategori_a)}</p>
                    </div>
                    <div>
                      <p className="text-[9px] text-slate-400 uppercase font-semibold">Gapok B</p>
                      <p className="font-bold">{idr(item.gaji_pokok_kategori_b)}</p>
                    </div>
                    <div>
                      <p className="text-[9px] text-slate-400 uppercase font-semibold">Insentif Lebih</p>
                      <p className="font-bold">{item.insentif_jam_lebih_pct}%</p>
                    </div>
                    <div>
                      <p className="text-[9px] text-slate-400 uppercase font-semibold">Lembur</p>
                      <p className="font-bold">{item.insentif_lembur_pct}%</p>
                    </div>
                  </div>

                  {item.keterangan && (
                    <p className="text-[10px] italic border-t border-slate-100 pt-1.5 text-slate-400">
                      &quot;{item.keterangan}&quot;
                    </p>
                  )}
                  <p className="text-[9px] text-slate-400 font-semibold mt-1">Dibuat: {new Date(item.created_at).toLocaleDateString('id-ID')}</p>
                </div>
              ))}
            </div>
          </div>
        </div>

      </div>

    </SumberPvcLayout>
  );
}
