import React from "react";

export interface StockProduct {
  id: number;
  kategori: string;
  jenis: string;
  warna: string;
  stok_saat_ini?: number;
}

interface Props {
  products: StockProduct[];
  value: number | string;
  onChange: (id: number) => void;
  showStock?: boolean;
}

const unique = (values: string[]) => [...new Set(values)];

export default function ProductVariantFields({ products, value, onChange, showStock = false }: Props) {
  const selected = products.find(product => product.id === Number(value)) ?? products[0];
  const categories = unique(products.map(product => product.kategori));
  const types = unique(products.filter(product => product.kategori === selected?.kategori).map(product => product.jenis));
  const colors = unique(products.filter(product => product.kategori === selected?.kategori && product.jenis === selected?.jenis).map(product => product.warna));

  const choose = (kategori: string, jenis?: string, warna?: string) => {
    const candidates = products.filter(product => product.kategori === kategori);
    const next = candidates.find(product =>
      (!jenis || product.jenis === jenis) && (!warna || product.warna === warna)
    ) ?? candidates[0];
    if (next) onChange(next.id);
  };

  return <>
    <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">
      Kategori Barang
      <select required value={selected?.kategori ?? ""} onChange={event => choose(event.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case">
        {categories.map(category => <option key={category}>{category}</option>)}
      </select>
    </label>
    <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">
      {selected?.kategori === "Tali Jepit" ? "Jenis Jepit" : "Jenis Outsole"}
      <select required value={selected?.jenis ?? ""} onChange={event => choose(selected.kategori, event.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case">
        {types.map(type => <option key={type}>{type}</option>)}
      </select>
    </label>
    <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">
      Warna
      <select required value={selected?.warna ?? ""} onChange={event => choose(selected.kategori, selected.jenis, event.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case">
        {colors.map(color => <option key={color}>{color}</option>)}
      </select>
      {showStock && <span className="block text-[9px] font-normal normal-case text-slate-400">Stok: {selected?.stok_saat_ini ?? 0} kodi</span>}
    </label>
  </>;
}
