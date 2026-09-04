import React from "react";
import { Head, router } from "@inertiajs/react";
import { ArrowDownToLine, ArrowUpFromLine, CalendarDays, Scale } from "lucide-react";
import SumberPvcLayout from "@/Layouts/SumberPvcLayout";

interface Row { id:number; kategori:string; jenis:string; warna:string; masuk:number; keluar:number; selisih:number }
interface Props {
  period:{week:string; start:string; end:string};
  rows:Row[];
  totals:{masuk:number; keluar:number; selisih:number};
}

export default function WeeklyReport({ period, rows, totals }: Props) {
  const changeWeek = (week:string) => router.get(route("stock.weekly-report"), { week }, { preserveState:true, replace:true });
  const cards = [
    {label:"Barang Masuk", value:totals.masuk, icon:ArrowDownToLine, color:"text-green-600 bg-green-50"},
    {label:"Barang Keluar", value:totals.keluar, icon:ArrowUpFromLine, color:"text-red-600 bg-red-50"},
    {label:"Selisih Mingguan", value:totals.selisih, icon:Scale, color:"text-blue-600 bg-blue-50"},
  ];

  return <SumberPvcLayout>
    <Head title="Laporan Mingguan"/>
    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div><h1 className="text-xl font-bold text-slate-800">Laporan Mingguan Gudang</h1><p className="text-xs text-slate-400">Rekap barang masuk dan keluar dalam satuan kodi.</p></div>
      <label className="text-[10px] font-bold uppercase text-slate-500">Pilih Minggu
        <div className="relative mt-1"><CalendarDays size={14} className="absolute left-3 top-2.5 text-slate-400"/><input type="date" value={period.week} onChange={e=>changeWeek(e.target.value)} className="rounded-lg border-slate-200 pl-9 text-xs font-normal normal-case"/></div>
      </label>
    </div>

    <div className="rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3 text-sm font-semibold text-blue-900">Periode: {period.start} – {period.end}</div>
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">{cards.map(card=>{const Icon=card.icon;return <div key={card.label} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div className={`mb-3 flex h-9 w-9 items-center justify-center rounded-lg ${card.color}`}><Icon size={18}/></div><p className="text-xs text-slate-500">{card.label}</p><p className="mt-1 text-2xl font-bold text-slate-800">{card.value} <span className="text-xs font-medium text-slate-400">kodi</span></p></div>})}</div>

    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto"><table className="w-full text-xs"><thead className="bg-slate-50 text-[10px] uppercase text-slate-400"><tr><th className="p-4 text-left">Kategori</th><th className="p-4 text-left">Jenis</th><th className="p-4 text-left">Warna</th><th className="p-4 text-right">Masuk</th><th className="p-4 text-right">Keluar</th><th className="p-4 text-right">Selisih</th></tr></thead>
        <tbody className="divide-y">{rows.length ? rows.map(row=><tr key={row.id}><td className="p-4">{row.kategori}</td><td className="p-4 font-semibold">{row.jenis}</td><td className="p-4">{row.warna}</td><td className="p-4 text-right font-bold text-green-600">+{row.masuk}</td><td className="p-4 text-right font-bold text-red-600">-{row.keluar}</td><td className={`p-4 text-right font-bold ${row.selisih >= 0 ? "text-blue-600" : "text-orange-600"}`}>{row.selisih > 0 ? "+" : ""}{row.selisih} kodi</td></tr>) : <tr><td colSpan={6} className="p-10 text-center text-slate-400">Belum ada transaksi pada minggu ini.</td></tr>}</tbody>
      </table></div>
    </div>
  </SumberPvcLayout>;
}
