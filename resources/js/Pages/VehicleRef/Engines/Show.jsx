import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Car, Cog, PackageSearch, Pencil, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import StatusBadge from '@/Components/StatusBadge';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import EngineDialog, { ASPIRATIONS, BLANK_ENGINE, engineValues } from './EngineDialog';

/** One engine code: specs, every vehicle variant carrying it, every part fitted to it (with stock). */
export default function EngineShow({ engine, variants, parts, partsTotal, makes }) {
    const editor = useRecordEditor(BLANK_ENGINE);
    const { confirmDelete, deleteDialog } = useDeleteRecord();
    const [q, setQ] = useState('');
    const [category, setCategory] = useState('');
    const [inStock, setInStock] = useState(false);

    const categories = useMemo(() => {
        const counts = {};
        parts.forEach((p) => { counts[p.category ?? 'Uncategorised'] = (counts[p.category ?? 'Uncategorised'] ?? 0) + 1; });
        return Object.entries(counts).sort((a, b) => b[1] - a[1]);
    }, [parts]);

    const shown = useMemo(() => {
        const t = q.trim().toLowerCase();
        return parts.filter((p) => (!category || (p.category ?? 'Uncategorised') === category)
            && (!inStock || p.qty_available > 0)
            && (!t || `${p.part_number} ${p.description} ${p.brand ?? ''}`.toLowerCase().includes(t)));
    }, [parts, q, category, inStock]);

    const inUse = variants.length > 0 || partsTotal > 0;
    const spec = [
        engine.capacity_cc && `${engine.capacity_cc.toLocaleString()} cc`,
        engine.fuel_type,
        engine.aspiration && ASPIRATIONS[engine.aspiration],
        engine.cylinders && `${engine.cylinders} cyl`,
    ].filter(Boolean).join(' · ');

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Engine ${engine.code}`}
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Engine Codes', href: route('vehicle-ref.engines.index') },
                { label: engine.code },
            ]}
        >
            <Head title={`Engine ${engine.code}`} />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <Link href={route('vehicle-ref.engines.index')} className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600 mb-3">
                        <ArrowLeft className="w-4 h-4" /> All engine codes
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center"><Cog className="w-6 h-6 text-white" /></div>
                            <div>
                                <h1 className="text-2xl font-bold font-mono text-slate-900">{engine.code}</h1>
                                <p className="text-sm text-slate-500">
                                    {engine.make ? <EntityLink type="make" id={engine.make.id} label={engine.make.name} className="font-medium" /> : 'Shared / any make'}
                                    {engine.description && <> · {engine.description}</>}
                                </p>
                                {spec && <p className="text-xs text-slate-400 capitalize mt-0.5">{spec}</p>}
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <button onClick={() => editor.open(engine, engineValues(engine))} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <Pencil className="w-4 h-4" /> Edit
                            </button>
                            <button
                                disabled={inUse}
                                title={inUse ? 'In use by vehicles or part fitments — rename it via Edit instead.' : undefined}
                                onClick={() => confirmDelete({ url: route('vehicle-ref.engines.destroy', engine.id), label: `engine ${engine.code}`, message: 'This engine code is not used anywhere and will be removed.' })}
                                className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <Trash2 className="w-4 h-4" /> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <StatChips items={[
                    { label: 'Vehicle variants', value: variants.length, icon: Car },
                    { label: 'Parts fitted', value: partsTotal.toLocaleString(), icon: PackageSearch, tone: partsTotal ? 'emerald' : 'slate' },
                    { label: 'In stock now', value: parts.filter((p) => p.qty_available > 0).length, tone: 'orange' },
                ]} />

                <div className="space-y-3">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Vehicles with this engine</h2>
                    <DataTable
                        columns={[
                            { key: 'model', label: 'Model', render: (v) => <EntityLink type="model" id={v.model_id} label={v.model} className="font-semibold" /> },
                            { key: 'name', label: 'Variant', render: (v) => <span className="text-slate-700">{v.name}</span> },
                            { key: 'years', label: 'Years', render: (v) => <span className="tabular-nums text-slate-500">{v.years ?? '—'}</span> },
                            { key: 'fuel_type', label: 'Fuel', render: (v) => <span className="capitalize text-slate-500">{v.fuel_type}</span> },
                            { key: 'transmission', label: 'Trans', render: (v) => <span className="capitalize text-slate-500">{v.transmission}</span> },
                            { key: 'drive', label: 'Drive', render: (v) => <span className="uppercase text-slate-500">{v.drive}</span> },
                        ]}
                        rows={variants}
                        rowHref={(v) => route('vehicle-ref.models.show', v.model_id)}
                        emptyIcon={Car}
                        emptyTitle="No vehicle variants list this engine yet"
                        emptyMessage="Add it as the engine code on a model's variant."
                    />
                </div>

                <div className="space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">
                            Parts that fit {engine.code} ({shown.length}{shown.length !== partsTotal ? ` of ${partsTotal}` : ''})
                        </h2>
                        <div className="flex flex-wrap items-center gap-2">
                            <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Part number, description, brand…" aria-label="Filter parts" className="w-64 rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                            <label className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 cursor-pointer">
                                <input type="checkbox" checked={inStock} onChange={(e) => setInStock(e.target.checked)} className="rounded border-slate-300 text-orange-600" /> In stock only
                            </label>
                        </div>
                    </div>
                    {categories.length > 1 && (
                        <div className="flex flex-wrap gap-1.5">
                            <button onClick={() => setCategory('')} className={`rounded-full px-3 py-1 text-xs font-semibold ${!category ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300'}`}>All</button>
                            {categories.map(([c, n]) => (
                                <button key={c} onClick={() => setCategory(c === category ? '' : c)} className={`rounded-full px-3 py-1 text-xs font-semibold ${category === c ? 'bg-orange-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300'}`}>
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
                            { key: 'brand', label: 'Brand', render: (p) => <EntityLink type="brand" id={p.brand_id} label={p.brand} muted /> },
                            { key: 'stock', label: 'Stock', align: 'right', render: (p) => (
                                p.qty_available > 0
                                    ? <span className="font-semibold tabular-nums text-emerald-700">{p.qty_available} in stock</span>
                                    : <span className="text-slate-400">{p.is_active ? 'out of stock' : <StatusBadge status="inactive" />}</span>
                            ) },
                        ]}
                        rows={shown}
                        rowHref={(p) => route('inventory.parts.show', p.id)}
                        emptyIcon={PackageSearch}
                        emptyTitle={parts.length ? 'No parts match the filters' : 'No parts fitted to this engine yet'}
                        emptyMessage={parts.length ? 'Clear the search, category or stock filter.' : 'Fitments come from the catalogue import or each part\u2019s Fitments tab.'}
                    />
                    {partsTotal > parts.length && <p className="text-xs text-slate-400">Showing the first {parts.length} of {partsTotal} parts.</p>}
                </div>
            </div>

            <EngineDialog editor={editor} makes={makes} />
            {deleteDialog}
        </ModuleLayout>
    );
}
