import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, PackageSearch, Pencil, Tags, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import StatusBadge from '@/Components/StatusBadge';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import BrandDialog, { BLANK_BRAND, brandValues } from './BrandDialog';

/** One brand: every part it makes (live stock) with instant filter + category chips. */
export default function BrandShow({ brand, parts, partsTotal, crossRefs }) {
    const editor = useRecordEditor(BLANK_BRAND);
    const { confirmDelete, deleteDialog } = useDeleteRecord();
    const [q, setQ] = useState('');
    const [category, setCategory] = useState('');
    const [inStock, setInStock] = useState(false);

    const categories = useMemo(() => {
        const c = {};
        parts.forEach((p) => { const k = p.category ?? 'Uncategorised'; c[k] = (c[k] ?? 0) + 1; });
        return Object.entries(c).sort((a, b) => b[1] - a[1]);
    }, [parts]);

    const shown = useMemo(() => {
        const words = q.trim().toLowerCase().split(/\s+/).filter(Boolean);
        return parts.filter((p) => (!category || (p.category ?? 'Uncategorised') === category)
            && (!inStock || p.qty_available > 0)
            && words.every((w) => `${p.part_number} ${p.description}`.toLowerCase().includes(w)));
    }, [parts, q, category, inStock]);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={brand.name}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Brands', href: route('inventory.brands.index') },
                { label: brand.name },
            ]}
        >
            <Head title={brand.name} />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <Link href={route('inventory.brands.index')} className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600 mb-3">
                        <ArrowLeft className="w-4 h-4" /> All brands
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-orange-600 rounded-xl flex items-center justify-center"><Tags className="w-6 h-6 text-white" /></div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold text-slate-900">{brand.name}</h1>
                                    <span className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-500">{brand.code}</span>
                                    <StatusBadge status={brand.is_oem_brand ? 'posted' : 'draft'} label={brand.is_oem_brand ? 'OEM' : 'Aftermarket'} />
                                    {!brand.is_active && <StatusBadge status="inactive" />}
                                </div>
                                <p className="text-sm text-slate-500">{brand.country_of_origin ?? 'Country not set'}</p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('inventory.parts.index', { brand_id: brand.id })} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                <PackageSearch className="w-4 h-4" /> Open in Parts list
                            </Link>
                            <button onClick={() => editor.open(brand, brandValues(brand))} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <Pencil className="w-4 h-4" /> Edit
                            </button>
                            <button onClick={() => confirmDelete({ url: route('inventory.brands.destroy', brand.id), label: `brand ${brand.name}` })} className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                                <Trash2 className="w-4 h-4" /> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <StatChips items={[
                    { label: 'Parts', value: partsTotal.toLocaleString(), icon: PackageSearch },
                    { label: 'In stock', value: parts.filter((p) => p.qty_available > 0).length.toLocaleString(), tone: 'emerald' },
                    { label: 'Categories', value: categories.length },
                    { label: 'Cross-references', value: crossRefs },
                ]} />

                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Filter by part number or description…" aria-label="Filter parts" className="flex-1 min-w-[16rem] rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <label className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 cursor-pointer">
                            <input type="checkbox" checked={inStock} onChange={(e) => setInStock(e.target.checked)} className="rounded border-slate-300 text-orange-600" /> In stock only
                        </label>
                    </div>
                    {categories.length > 1 && (
                        <div className="flex flex-wrap gap-1.5">
                            <button onClick={() => setCategory('')} className={`rounded-full px-3 py-1 text-xs font-semibold ${!category ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600'}`}>All</button>
                            {categories.map(([c, n]) => (
                                <button key={c} onClick={() => setCategory(c === category ? '' : c)} className={`rounded-full px-3 py-1 text-xs font-semibold ${category === c ? 'bg-orange-600 text-white' : 'border border-slate-200 bg-white text-slate-600'}`}>
                                    {c} <span className="opacity-60">{n}</span>
                                </button>
                            ))}
                        </div>
                    )}
                    <DataTable
                        columns={[
                            { key: 'part_number', label: 'Part', render: (p) => <EntityLink type="part" id={p.id} label={p.part_number} mono className="font-semibold" /> },
                            { key: 'description', label: 'Description', render: (p) => <span className="text-slate-700">{p.description}</span> },
                            { key: 'category', label: 'Category', render: (p) => <EntityLink type="category" id={p.category_id} label={p.category} muted /> },
                            { key: 'stock', label: 'Available', align: 'right', render: (p) => p.qty_available > 0 ? <span className="font-semibold tabular-nums text-emerald-700">{p.qty_available}</span> : <span className="text-slate-400">{p.is_active ? '0' : <StatusBadge status="inactive" />}</span> },
                        ]}
                        rows={shown}
                        rowHref={(p) => route('inventory.parts.show', p.id)}
                        emptyIcon={PackageSearch}
                        emptyTitle={parts.length ? 'No parts match' : 'No parts for this brand yet'}
                    />
                    {partsTotal > parts.length && (
                        <p className="text-xs text-slate-400">Showing the first {parts.length} of {partsTotal.toLocaleString()} — <Link href={route('inventory.parts.index', { brand_id: brand.id })} className="text-orange-700 hover:underline">open the full list</Link>.</p>
                    )}
                </div>
            </div>

            <BrandDialog editor={editor} />
            {deleteDialog}
        </ModuleLayout>
    );
}
