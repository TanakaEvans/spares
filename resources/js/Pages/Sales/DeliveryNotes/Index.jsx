import { Head, router, useForm } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import FormField from '@/Components/FormField';
import { useListFilter } from '@/lib/listFilters';

const docType = (t) => (t === 'order' ? 'order' : t === 'quotation' ? 'quote' : t === 'credit_note' ? 'credit_note' : 'invoice');

const STATUS_PILLS = [
    { key: '', label: 'All' },
    { key: 'dispatched', label: 'Out for delivery' },
    { key: 'delivered', label: 'Delivered' },
];

export default function DeliveryNotesIndex({ notes, customers, branches, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({ customer_id: '', branch_id: branches[0]?.id ?? '', delivery_date: new Date().toISOString().slice(0, 10), driver: '', vehicle_reg: '', address: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const filter = useListFilter('sales.delivery-notes.index', filters ?? {});

    const outForDelivery = notes.data.filter((d) => d.status === 'dispatched').length;

    return (
        <ModuleLayout navConfig={navConfig} title="Delivery Notes" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Delivery Notes' }]}>
            <Head title="Delivery Notes" />
            <div className="space-y-4">
                <StatChips items={[
                    { label: 'Out for delivery', value: outForDelivery, tone: outForDelivery > 0 ? 'amber' : 'slate', icon: Truck },
                    { label: 'Delivered (this page)', value: notes.data.filter((d) => d.status === 'delivered').length, tone: 'emerald' },
                ]} />
                <ListToolbar
                    filters={filters ?? {}}
                    onFilter={filter}
                    searchPlaceholder="Delivery no, customer or driver…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="sales.delivery-notes.export"
                >
                    <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1" role="tablist" aria-label="Status filter">
                        {STATUS_PILLS.map((s) => (
                            <button key={s.key} role="tab" aria-selected={(filters?.status ?? '') === s.key} onClick={() => filter({ status: s.key })}
                                className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${(filters?.status ?? '') === s.key ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
                                {s.label}
                            </button>
                        ))}
                    </div>
                </ListToolbar>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                    <div className="lg:col-span-2 h-fit">
                        <DataTable
                            columns={[
                                { key: 'delivery_number', label: 'Delivery', sortable: 'delivery_number', render: (d) => <EntityLink type="delivery_note" id={d.id} label={d.delivery_number} mono className="font-semibold" /> },
                                { key: 'customer', label: 'Customer', render: (d) => <EntityLink type="customer" id={d.customer_id} label={d.customer} /> },
                                { key: 'source', label: 'Source', render: (d) => d.document ? <EntityLink type={docType(d.document.document_type)} id={d.document.id} label={d.document.document_number} mono muted /> : <span className="text-slate-300">—</span> },
                                { key: 'delivery_date', label: 'Date', sortable: 'delivery_date', render: (d) => <span className="tabular-nums text-slate-500">{d.delivery_date}</span> },
                                { key: 'driver', label: 'Driver', sortable: 'driver', render: (d) => <span className="text-slate-500">{d.driver ?? '—'}{d.vehicle_reg ? ` · ${d.vehicle_reg}` : ''}</span> },
                                { key: 'status', label: 'Status', sortable: 'status', render: (d) => <StatusBadge status={d.status === 'dispatched' ? 'in_transit' : 'complete'} label={d.status === 'dispatched' ? 'out for delivery' : 'delivered'} /> },
                                { key: 'actions', label: '', render: (d) => (
                                    <div onClick={(e) => e.stopPropagation()} className="text-right">
                                        {d.status === 'dispatched' && <button onClick={() => router.post(route('sales.delivery-notes.transition', d.id), { status: 'delivered' }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Mark delivered</button>}
                                    </div>
                                ) },
                            ]}
                            rows={notes.data}
                            pagination={notes}
                            sorting={{ filters: filters ?? {}, onFilter: filter }}
                            rowHref={(d) => route('sales.delivery-notes.show', d.id)}
                            emptyTitle="No delivery notes yet"
                            emptyMessage="Create one when goods leave with a driver — the customer signs for receipt."
                            emptyIcon={Truck}
                        />
                    </div>
                    <form onSubmit={(e) => { e.preventDefault(); post(route('sales.delivery-notes.store'), { onSuccess: () => reset('driver', 'vehicle_reg', 'address') }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                        <h2 className="text-sm font-semibold text-slate-700">New delivery note</h2>
                        <FormField label="Customer" error={errors.customer_id}>
                            <select value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} className={input}><option value="">Customer…</option>{customers.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
                        </FormField>
                        <div className="grid grid-cols-2 gap-2">
                            <FormField label="Branch">
                                <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>{branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select>
                            </FormField>
                            <FormField label="Delivery date" error={errors.delivery_date}>
                                <input type="date" value={data.delivery_date} onChange={(e) => setData('delivery_date', e.target.value)} className={input} />
                            </FormField>
                        </div>
                        <FormField label="Driver"><input value={data.driver} onChange={(e) => setData('driver', e.target.value)} placeholder="Who's driving" className={input} /></FormField>
                        <FormField label="Vehicle reg"><input value={data.vehicle_reg} onChange={(e) => setData('vehicle_reg', e.target.value)} placeholder="ABC 123 L" className={input} /></FormField>
                        <FormField label="Delivery address"><input value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Where it's going" className={input} /></FormField>
                        <button type="submit" disabled={processing || !data.customer_id} className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create delivery note</button>
                    </form>
                </div>
            </div>
        </ModuleLayout>
    );
}
