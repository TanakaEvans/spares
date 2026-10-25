import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Car, Cog, Factory, Pencil, Search, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import StatusBadge from '@/Components/StatusBadge';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import MakeDialog, { BLANK_MAKE, makeValues } from './MakeDialog';

/** One make, everything about it: models (with fitted-part counts), engines, bulletins. */
export default function MakeShow({ make, stats, models, engines, bulletins }) {
    const editor = useRecordEditor(BLANK_MAKE);
    const { confirmDelete, deleteDialog } = useDeleteRecord();
    const [q, setQ] = useState('');

    const shownModels = useMemo(() => {
        const t = q.trim().toLowerCase();
        return t ? models.filter((m) => `${m.name} ${m.body_type ?? ''}`.toLowerCase().includes(t)) : models;
    }, [q, models]);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={make.name}
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Makes', href: route('vehicle-ref.makes.index') },
                { label: make.name },
            ]}
        >
            <Head title={make.name} />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <Link href={route('vehicle-ref.makes.index')} className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600 mb-3">
                        <ArrowLeft className="w-4 h-4" /> All makes
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center"><Factory className="w-6 h-6 text-white" /></div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold text-slate-900">{make.name}</h1>
                                    <span className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-500">{make.code}</span>
                                    <StatusBadge status={make.is_active ? 'active' : 'inactive'} />
                                </div>
                                <p className="text-sm text-slate-500">{make.country_of_origin ?? 'Country not set'}</p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('vehicle-ref.fitment', { make_id: make.id })} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                <Search className="w-4 h-4" /> Parts that fit a {make.name}
                            </Link>
                            <button onClick={() => editor.open(make, makeValues(make))} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <Pencil className="w-4 h-4" /> Edit
                            </button>
                            <button onClick={() => confirmDelete({ url: route('vehicle-ref.makes.destroy', make.id), label: make.name })} className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                                <Trash2 className="w-4 h-4" /> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <StatChips items={[
                    { label: 'Models', value: stats.models, icon: Car },
                    { label: 'Engine codes', value: stats.engines, icon: Cog },
                    { label: 'Parts fitted', value: stats.parts.toLocaleString(), tone: stats.parts ? 'emerald' : 'slate', hint: stats.make_wide_parts ? `${stats.make_wide_parts.toLocaleString()} fit every ${make.name}` : null },
                    { label: 'Customer vehicles', value: stats.customer_vehicles },
                ]} />

                <div className="space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Models ({models.length})</h2>
                        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Filter models…" aria-label="Filter models" className="w-56 rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                    </div>
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Model', render: (m) => <EntityLink type="model" id={m.id} label={m.name} className="font-semibold" /> },
                            { key: 'body_type', label: 'Body', render: (m) => <span className="capitalize text-slate-500">{m.body_type ?? '—'}</span> },
                            { key: 'years', label: 'Years', render: (m) => <span className="tabular-nums text-slate-500">{m.year_from ?? '?'}–{m.year_to ?? 'now'}</span> },
                            { key: 'variants_count', label: 'Variants', align: 'right' },
                            { key: 'parts_count', label: 'Parts fitted', align: 'right', render: (m) => (
                                m.parts_count > 0
                                    ? <Link href={route('vehicle-ref.fitment', { make_id: make.id, model_id: m.id })} onClick={(e) => e.stopPropagation()} className="font-semibold tabular-nums text-orange-700 hover:underline">{m.parts_count.toLocaleString()}</Link>
                                    : <span className="text-slate-300">0</span>
                            ) },
                            { key: 'is_active', label: '', render: (m) => !m.is_active && <StatusBadge status="inactive" /> },
                        ]}
                        rows={shownModels}
                        rowHref={(m) => route('vehicle-ref.models.show', m.id)}
                        emptyIcon={Car}
                        emptyTitle={q ? 'No models match' : 'No models yet'}
                        emptyMessage={q ? 'Clear the filter.' : 'Add models under Models & Variants.'}
                    />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div className="space-y-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Engine codes ({engines.length})</h2>
                        <DataTable
                            columns={[
                                { key: 'code', label: 'Code', render: (e) => <EntityLink type="engine" id={e.id} label={e.code} mono className="font-semibold" /> },
                                { key: 'description', label: 'Description', render: (e) => <span className="text-slate-600">{e.description}</span> },
                                { key: 'fuel_type', label: 'Fuel', render: (e) => <span className="capitalize text-slate-500">{e.fuel_type}</span> },
                            ]}
                            rows={engines}
                            rowHref={(e) => route('vehicle-ref.engines.show', e.id)}
                            emptyIcon={Cog}
                            emptyTitle="No engine codes for this make"
                        />
                    </div>
                    <div className="space-y-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Technical bulletins</h2>
                        <div className="bg-white rounded-xl shadow-sm border border-slate-100 divide-y divide-slate-50">
                            {bulletins.length === 0 ? (
                                <p className="px-5 py-8 text-center text-sm text-slate-400">No bulletins for {make.name}.</p>
                            ) : bulletins.map((b) => (
                                <Link key={b.id} href={route('vehicle-ref.bulletins.index', { search: b.title })} className="flex items-start gap-3 px-5 py-3 hover:bg-slate-50">
                                    <AlertTriangle className="w-4 h-4 mt-0.5 text-amber-500 shrink-0" />
                                    <span className="flex-1 min-w-0">
                                        <span className="block text-sm font-medium text-slate-800 truncate">{b.title}</span>
                                        <span className="block text-xs text-slate-400 capitalize">{b.category}</span>
                                    </span>
                                    {!b.is_active && <StatusBadge status="inactive" />}
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            <MakeDialog editor={editor} />
            {deleteDialog}
        </ModuleLayout>
    );
}
