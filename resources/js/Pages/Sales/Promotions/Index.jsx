import { Head, router, useForm } from '@inertiajs/react';
import { Tag } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import FormField from '@/Components/FormField';
import ListToolbar from '@/Components/ListToolbar';
import { useListFilter } from '@/lib/listFilters';
import { fmt } from '@/Components/Pos/posMath';

/** Lifecycle from the dates: scheduled before start, expired after end. */
function lifecycle(p) {
    const today = new Date().toISOString().slice(0, 10);
    if (!p.is_active) return 'inactive';
    if (p.starts_at && p.starts_at > today) return 'pending';
    if (p.ends_at && p.ends_at < today) return 'expired';
    return 'active';
}

const statusLabel = { active: 'live', pending: 'scheduled', expired: 'ended', inactive: 'disabled' };

const LIFECYCLE_PILLS = [
    { key: '', label: 'All' },
    { key: 'live', label: 'Live' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'ended', label: 'Ended' },
    { key: 'disabled', label: 'Disabled' },
];

export default function PromotionsIndex({ promotions, categories, filters = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', type: 'percent', value: '', applies_to: 'all', category_id: '', starts_at: '', ends_at: '' });
    const filter = useListFilter('sales.promotions.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Promotions & Discounts" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Promotions & Discounts' }]}>
            <Head title="Promotions & Discounts" />
            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Promotion name…"
                searchClass="w-64"
            >
                <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1" role="tablist" aria-label="Lifecycle filter">
                    {LIFECYCLE_PILLS.map((s) => (
                        <button key={s.key} role="tab" aria-selected={(filters.lifecycle ?? '') === s.key} onClick={() => filter({ lifecycle: s.key })}
                            className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${(filters.lifecycle ?? '') === s.key ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
                            {s.label}
                        </button>
                    ))}
                </div>
            </ListToolbar>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl mt-4">
                <div className="lg:col-span-2 h-fit">
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Promotion', sortable: 'name', render: (p) => <span className="font-medium text-slate-800">{p.name}</span> },
                            { key: 'value', label: 'Discount', sortable: 'value', render: (p) => <span className="text-slate-600 font-semibold tabular-nums">{p.type === 'percent' ? `${p.value}%` : `R${fmt(p.value)}`}</span> },
                            { key: 'scope', label: 'Scope', render: (p) => p.applies_to === 'all' ? <span className="text-slate-500">All parts</span> : <EntityLink type="category" id={p.category_id} label={p.category ?? 'Category'} muted /> },
                            { key: 'window', label: 'Window', sortable: 'starts_at', render: (p) => <span className="tabular-nums text-slate-400">{p.starts_at ?? '…'} → {p.ends_at ?? 'open'}</span> },
                            { key: 'status', label: 'Status', render: (p) => { const s = lifecycle(p); return <StatusBadge status={s} label={statusLabel[s]} />; } },
                            { key: 'actions', label: '', align: 'right', render: (p) => <button onClick={(e) => { e.stopPropagation(); router.post(route('sales.promotions.toggle', p.id), {}, { preserveScroll: true }); }} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">{p.is_active ? 'Disable' : 'Enable'}</button> },
                        ]}
                        rows={promotions.data}
                        pagination={promotions}
                        sorting={{ filters, onFilter: filter }}
                        emptyTitle="No promotions yet"
                        emptyMessage="Create a percent or rand discount — for all parts or one category."
                        emptyIcon={Tag}
                    />
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('sales.promotions.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New promotion</h2>
                    <FormField label="Name" error={errors.name}>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g. October filter special" className={input} />
                    </FormField>
                    <div className="grid grid-cols-2 gap-2">
                        <FormField label="Type">
                            <select value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}><option value="percent">Percent %</option><option value="fixed">Rand off</option></select>
                        </FormField>
                        <FormField label="Value" error={errors.value}>
                            <input type="number" step="0.01" min="0" value={data.value} onChange={(e) => setData('value', e.target.value)} placeholder={data.type === 'percent' ? '10' : '25.00'} className={`${input} text-right tabular-nums`} />
                        </FormField>
                    </div>
                    <FormField label="Applies to">
                        <select value={data.applies_to} onChange={(e) => setData('applies_to', e.target.value)} className={input}><option value="all">All parts</option><option value="category">A category</option></select>
                    </FormField>
                    {data.applies_to === 'category' && (
                        <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} aria-label="Category" className={input}>
                            <option value="">Category…</option>
                            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    )}
                    <div className="grid grid-cols-2 gap-2">
                        <FormField label="Starts"><input type="date" value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} className={input} /></FormField>
                        <FormField label="Ends"><input type="date" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} className={input} /></FormField>
                    </div>
                    <button type="submit" disabled={processing || !data.name || !data.value} className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create promotion</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
