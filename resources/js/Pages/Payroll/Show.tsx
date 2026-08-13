import React from "react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";
import { Head, Link, router } from "@inertiajs/react";
import { ArrowLeft, Printer, CheckCircle, HelpCircle } from "lucide-react";

interface PayrollSlip {
  id: number;
  periode: string;
  gaji_pokok: number;
  tunjangan: number;
  insentif_lembur: number;
  potongan: number;
  total_gaji: number;
  hari_hadir: number;
  hari_setengah: number;
  jam_lebih: number;
  jam_lembur: number;
  status: "draft" | "final";
  rincian: {
    kategori_masa_kerja: "A" | "B";
    gaji_harian: number;
    insentif_jam_lebih: number;
    insentif_lembur_resmi: number;
    potongan_setengah: number;
    absensi: {
      tanggal: string;
      durasi: number;
      status: string;
    }[];
  };
  karyawan: {
    nama: string;
    jabatan: string;
    tanggal_masuk: string;
  };
}

interface ShowProps {
  payroll: PayrollSlip;
  setting: any;
}

export default function Show({ payroll, setting }: ShowProps) {
  // Format IDR
  const idr = (val: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(val);
  };

  const handleFinalize = () => {
    if (confirm("Apakah Anda yakin ingin memfinalisasi slip gaji ini? Status slip gaji tidak dapat dikembalikan menjadi DRAFT setelah diselesaikan.")) {
      router.put(route("payroll.finalize", payroll.id));
    }
  };

  return (
    <SumberPvcLayout>
      <Head title={`Slip Gaji - ${payroll.karyawan.nama}`} />

      {/* Back & Action Buttons */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <Link
          href={route("payroll.index", { periode: payroll.periode })}
          className="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors"
        >
          <ArrowLeft size={14} />
          Kembali ke Rekap Periode
        </Link>

        <div className="flex gap-2">
          {payroll.status === "draft" && (
            <button
              onClick={handleFinalize}
              className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-lg shadow-emerald-500/10 transition-all cursor-pointer"
            >
              <CheckCircle size={14} />
              Finalisasi Slip
            </button>
          )}
          <a
            href={route("payroll.pdf", payroll.id)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg bg-white shadow-sm transition-all"
          >
            <Printer size={14} />
            Cetak PDF
          </a>
        </div>
      </div>

      {/* SLIP CONTENT GRID */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* DETAIL KALKULASI GAJI (2 Kolom) */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden p-6 space-y-6">
            
            {/* Header info */}
            <div className="flex flex-col sm:flex-row justify-between border-b border-slate-100 pb-5 gap-4">
              <div>
                <span className="text-[9px] font-bold text-slate-400 uppercase tracking-widest bg-slate-100 px-2 py-0.5 rounded">
                  Sumber PVC Outsole Tali Jepit
                </span>
                <h2 className="text-lg font-black text-slate-800 mt-2">Slip Gaji Karyawan</h2>
                <p className="text-xs text-slate-400 font-semibold mt-0.5">Periode Bulan: {payroll.periode}</p>
              </div>

              <div className="text-left sm:text-right">
                <p className="text-xs font-bold text-slate-800">{payroll.karyawan.nama}</p>
                <p className="text-[11px] text-slate-400 font-semibold mt-0.5">{payroll.karyawan.jabatan}</p>
                <p className="text-[10px] text-slate-400 mt-0.5">
                  Masa Kerja: {payroll.rincian.kategori_masa_kerja === "B" ? "≥ 5 Tahun (Kat B)" : "< 5 Tahun (Kat A)"}
                </p>
              </div>
            </div>

            {/* Rincian Komponen */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* PENDAPATAN */}
              <div className="space-y-4">
                <h3 className="text-xs font-black text-slate-800 border-b border-slate-100 pb-2 uppercase tracking-wider text-blue-600">
                  Komponen Pendapatan
                </h3>
                
                <div className="space-y-3 text-xs">
                  <ItemRow label="Gaji Pokok" val={idr(payroll.gaji_pokok)} />
                  <ItemRow label="Tunjangan (Jabatan, Makan, Transp)" val={idr(payroll.tunjangan)} />
                  
                  {/* Break down insentif jam lebih & lembur */}
                  <ItemRow 
                    label={`Insentif Jam Lebih (${payroll.jam_lebih} jam)`} 
                    val={idr(payroll.rincian.insentif_jam_lebih)} 
                    sub="Beban kerja tambahan harian"
                  />
                  <ItemRow 
                    label={`Insentif Lembur Resmi (${payroll.jam_lembur} jam)`} 
                    val={idr(payroll.rincian.insentif_lembur_resmi)} 
                    sub="Kehadiran >15 jam"
                  />

                  <div className="border-t border-slate-100 pt-3 flex justify-between font-bold text-slate-800">
                    <span>Total Pendapatan Kotor</span>
                    <span>{idr(payroll.gaji_pokok + payroll.tunjangan + payroll.insentif_lembur)}</span>
                  </div>
                </div>
              </div>

              {/* POTONGAN */}
              <div className="space-y-4">
                <h3 className="text-xs font-black text-slate-800 border-b border-slate-100 pb-2 uppercase tracking-wider text-rose-600">
                  Komponen Potongan
                </h3>

                <div className="space-y-3 text-xs">
                  <ItemRow 
                    label={`Potongan Setengah Hari (${payroll.hari_setengah} kali)`} 
                    val={idr(payroll.rincian.potongan_setengah)} 
                    sub="Durasi kehadiran < 8 jam"
                  />
                  
                  <div className="border-t border-slate-100 pt-3 flex justify-between font-bold text-slate-800">
                    <span>Total Potongan</span>
                    <span className="text-red-500">-{idr(payroll.potongan)}</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Total Bersih */}
            <div className="bg-blue-50/60 rounded-xl border border-blue-100 p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
              <div>
                <h4 className="text-xs font-extrabold text-blue-900 uppercase tracking-wider">Gaji Bersih Yang Diterima (Netto)</h4>
                <p className="text-[10px] text-slate-400 font-semibold mt-0.5">Sudah disesuaikan dengan rekap log absensi</p>
              </div>
              <span className="text-2xl font-black text-blue-700">{idr(payroll.total_gaji)}</span>
            </div>

          </div>
        </div>

        {/* LOG ABSENSI YANG MEWAKILI (1 Kolom) */}
        <div className="space-y-6">
          <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 className="text-sm font-bold text-slate-800">Log Rekap Absensi Terhitung</h3>
            
            <div className="grid grid-cols-2 gap-4 text-center">
              <div className="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                <span className="text-[9px] font-bold text-slate-400 uppercase">Hadir &gt;= 8j</span>
                <span className="text-base font-black text-slate-800 block mt-0.5">{payroll.hari_hadir} Hari</span>
              </div>
              <div className="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                <span className="text-[9px] font-bold text-slate-400 uppercase">Setengah Hari</span>
                <span className="text-base font-black text-amber-600 block mt-0.5">{payroll.hari_setengah} Hari</span>
              </div>
            </div>

            {/* List log absensi detail */}
            <div className="space-y-2 max-h-80 overflow-y-auto pr-1" style={{ scrollbarWidth: "thin" }}>
              {payroll.rincian.absensi.length === 0 ? (
                <p className="text-center text-xs text-slate-400 py-4">Belum ada absensi tercatat pada bulan ini.</p>
              ) : (
                payroll.rincian.absensi.map((abs, idx) => (
                  <div key={idx} className="flex justify-between items-center bg-slate-50/50 hover:bg-slate-50 p-2 rounded border border-slate-100/50 text-xs">
                    <div>
                      <p className="font-semibold text-slate-700">{abs.tanggal}</p>
                      <p className="text-[10px] text-slate-400 font-medium">{abs.durasi} jam kerja</p>
                    </div>
                    <span className={`px-1.5 py-0.5 rounded text-[9px] font-bold border ${
                      abs.status === "Penuh" ? "bg-green-50 text-green-700 border-green-100" :
                      abs.status === "Lembur" ? "bg-purple-50 text-purple-700 border-purple-100" :
                      abs.status === "Jam Lebih" ? "bg-indigo-50 text-indigo-700 border-indigo-100" :
                      "bg-amber-50 text-amber-700 border-amber-100"
                    }`}>
                      {abs.status.toUpperCase()}
                    </span>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

      </div>

    </SumberPvcLayout>
  );
}

function ItemRow({ label, val, sub }: { label: string, val: string, sub?: string }) {
  return (
    <div className="flex justify-between items-start gap-4">
      <div>
        <p className="font-semibold text-slate-700">{label}</p>
        {sub && <p className="text-[10px] text-slate-400 font-semibold">{sub}</p>}
      </div>
      <span className="font-bold text-slate-800 whitespace-nowrap">{val}</span>
    </div>
  );
}
