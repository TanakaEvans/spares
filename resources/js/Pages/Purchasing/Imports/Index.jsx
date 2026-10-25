import { Head, router, useForm } from '@inertiajs/react';
import { Ship } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import ListToolbar from '@/Components/ListToolbar';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import StatChips from '@/Components/StatChips';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';
import { fmt } from '@/Components/Pos/posMath';

const NEXT = { ordered: 'in_transit', in_transit: 'customs', customs: 'cleared', cleared: 'received' };

const STATUS_PILLS = [
    { key: '', label: 'All' },
    { key: 'ordered', label: 'Ordered' },
    { key: 'in_transit', label: 'In transit' },
    { key: 'customs', label: 'Customs' },
    { key: 'cleared', label: 'Cleared' },
    { key: 'received', label: 'Received' },
];

export default function ImportsIndex({ shipments, suppliers, stats, filters = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({ shipment_ref: '', supplier_id: '', origin_country: '', eta: '', goods_value: '', freight: '', duty: '', notes: '' });
    const filter = useListFilter('purchasing.imports.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Import Management" breadcrumbs={[{ label: 'Purchasing', href: route('modules.show', 'purchasing') }, { label: 'Import Management' }]}>
            <Head title="Import Management" />
            <div className="space-y-4">
                <StatChips
                    items={[
                        { label: 'Shipments on the water', value: stats.active, tone: stats.active > 0 ? 'orange' : 'slate' },
                        { label: 'Landed value outstanding', value: `R${fmt(stats.in_transit_value)}` },
                    ]}
                />
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Shipment ref, origin or supplier…"
                    searchClass="w-64"
                    dateRange
                >
                    <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1" role="tablist" aria-label="Status filter">
                        {STATUS_PILLS.map((s) => (
                            <button key={s.key} role="tab" aria-selected={(filters.status ?? '') === s.key} onClick={() => filter({ status: s.key })}
                                className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${(filters.status ?? '') === s.key ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
                                {s.label}
                            </button>
                        ))}
                    </div>
                </ListToolbar>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div className="lg:col-span-2">
                        <DataTable
                            columns={[
                                { key: 'shipment_ref', label: 'Shipment', sortable: 'shipment_ref', render: (s) => (
                                    <div><EntityLink type="import_shipment" id={s.id} label={s.shipment_ref} mono className="font-semibold" /><span className="block text-xs text-slate-400">{s.origin_country}</span></div>
                                ) },
                                { key: 'supplier', label: 'Supplier', render: (s) => <EntityLink type="supplier" id={s.supplier_id} label={s.supplier ?? '—'} /> },
                                { key: 'eta', label: 'ETA', sortable: 'eta', render: (s) => <span className="tabular-nums text-slate-500">{s.eta ?? '—'}</span> },
                                { key: 'landed', label: 'Landed', align: 'right', sortable: 'goods_value', render: (s) => <MoneyDisplay amount={s.landed} /> },
                                { key: 'status', label: 'Status', sortable: 'status', render: (s) => <StatusBadge status={s.status === 'received' ? 'received' : s.status === 'cleared' ? 'confirmed' : 'in_transit'} label={s.status.replace('_', ' ')} /> },
                                { key: 'actions', label: '', align: 'right', render: (s) => NEXT[s.status] && <button onClick={(e) => { e.stopPropagation(); router.post(route('purchasing.imports.transition', s.id), { status: NEXT[s.status] }, { preserveScroll: true }); }} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50 capitalize">→ {NEXT[s.status].replace('_', ' ')}</button> },
                            ]}
                            rows={shipments.data}
                            pagination={shipments}
                            sorting={{ filters, onFilter: filter }}
                            emptyTitle="No import shipments yet"
                            emptyIcon={Ship}
                        />
                    </div>
                    <form onSubmit={(e) => { e.preventDefault(); post(route('purchasing.imports.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                        <h2 className="text-sm font-semibold text-slate-700">New shipment</h2>
                        <FormField label="Shipment reference" htmlFor="imp-ref" required error={errors.shipment_ref}>
                            <input id="imp-ref" value={data.shipment_ref} onChange={(e) => setData('shipment_ref', e.target.value)} placeholder="e.g. CN-2026-031" className={input} />
                        </FormField>
                        <FormField label="Supplier" htmlFor="imp-sup" error={errors.supplier_id}>
                            <select id="imp-sup" value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} className={input}><option value="">—</option>{suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
                        </FormField>
                        <div className="grid grid-cols-2 gap-2">
                            <FormField label="Origin country" htmlFor="imp-origin" error={errors.origin_country}>
                                <input id="imp-origin" value={data.origin_country} onChange={(e) => setData('origin_country', e.target.value)} className={input} />
                            </FormField>
                            <FormField label="ETA" htmlFor="imp-eta" error={errors.eta}>
                                <input id="imp-eta" type="date" value={data.eta} onChange={(e) => setData('eta', e.target.value)} className={input} />
                            </FormField>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <FormField label="Goods" htmlFor="imp-goods" error={errors.goods_value}>
                                <input id="imp-goods" type="number" step="0.01" value={data.goods_value} onChange={(e) => setData('goods_value', e.target.value)} className={`${input} text-right`} />
                            </FormField>
                            <FormField label="Freight" htmlFor="imp-freight" error={errors.freight}>
                                <input id="imp-freight" type="number" step="0.01" value={data.freight} onChange={(e) => setData('freight', e.target.value)} className={`${input} text-right`} />
                            </FormField>
                            <FormField label="Duty" htmlFor="imp-duty" error={errors.duty}>
                                <input id="imp-duty" type="number" step="0.01" value={data.duty} onChange={(e) => setData('duty', e.target.value)} className={`${input} text-right`} />
                            </FormField>
                        </div>
                        <button type="submit" disabled={processing || !data.shipment_ref} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create shipment</button>
                    </form>
                </div>
            </div>
        </ModuleLayout>
    );
}
