import { Head, router, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

const NEXT = { draft: 'submitted', submitted: 'acknowledged', acknowledged: 'approved', approved: 'credited' };

const STATUS_PILLS = [
    { key: '', label: 'All' },
    { key: 'draft', label: 'Draft' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'acknowledged', label: 'Acknowledged' },
    { key: 'approved', label: 'Approved' },
    { key: 'credited', label: 'Credited' },
    { key: 'rejected', label: 'Rejected' },
];

export default function WarrantyIndex({ claims, suppliers, jobs, filters = {} }) {
    const { data, setData, post, processing, reset } = useForm({ supplier_id: '', job_card_id: '', fault: '', claim_amount: '' });
    const filter = useListFilter('workshop.warranty.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Warranty Claims" breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Warranty Claims' }]}>
            <Head title="Warranty Claims" />
            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Claim no, job, part or fault…"
                searchClass="w-64"
                dateRange
            >
                <select aria-label="Status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    {STATUS_PILLS.map((s) => <option key={s.key} value={s.key}>{s.label}</option>)}
                </select>
            </ListToolbar>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'claim_number', label: 'Claim', sortable: 'claim_number', render: (c) => (
                                <div><EntityLink type="warranty_claim" id={c.id} label={c.claim_number} mono className="font-semibold" /><span className="block font-sans text-xs text-slate-400 truncate max-w-[14rem]">{c.fault}</span></div>
                            ) },
                            { key: 'supplier', label: 'Supplier', render: (c) => <EntityLink type="supplier" id={c.supplier_id} label={c.supplier} /> },
                            { key: 'job', label: 'Job', render: (c) => <EntityLink type="job" id={c.job_card_id} label={c.job_number} mono muted /> },
                            { key: 'part', label: 'Part', render: (c) => <EntityLink type="part" id={c.part_id} label={c.part_number} mono muted /> },
                            { key: 'claim_amount', label: 'Claim', align: 'right', sortable: 'claim_amount', render: (c) => (
                                <div><MoneyDisplay amount={c.claim_amount} />{c.credit_amount > 0 && <span className="block text-xs text-emerald-600">+{c.credit_amount.toFixed(2)} credited</span>}</div>
                            ) },
                            { key: 'status', label: 'Status', sortable: 'status', render: (c) => <StatusBadge status={c.status === 'credited' ? 'complete' : c.status === 'rejected' ? 'rejected' : c.status === 'approved' ? 'approved' : 'submitted'} label={c.status} /> },
                            { key: 'actions', label: '', align: 'right', render: (c) => (
                                <div onClick={(e) => e.stopPropagation()}>
                                    {NEXT[c.status] && <button onClick={() => router.post(route('workshop.warranty.transition', c.id), { status: NEXT[c.status], credit_amount: c.claim_amount }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50 capitalize">→ {NEXT[c.status]}</button>}
                                    {c.status !== 'credited' && c.status !== 'rejected' && <button onClick={() => router.post(route('workshop.warranty.transition', c.id), { status: 'rejected' }, { preserveScroll: true })} className="ml-1 rounded border border-slate-300 px-2 py-1 text-xs text-red-500 hover:bg-red-50">Reject</button>}
                                </div>
                            ) },
                        ]}
                        rows={claims.data}
                        pagination={claims}
                        sorting={{ filters, onFilter: filter }}
                        rowHref={(c) => route('workshop.warranty.show', c.id)}
                        emptyTitle="No warranty claims yet"
                        emptyIcon={ShieldCheck}
                    />
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.warranty.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">Raise a claim</h2>
                    <select value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} className={input}><option value="">Supplier…</option>{suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
                    <select value={data.job_card_id} onChange={(e) => setData('job_card_id', e.target.value)} className={input}><option value="">Job (optional)…</option>{jobs.map((j) => <option key={j.id} value={j.id}>{j.job_number}</option>)}</select>
                    <textarea value={data.fault} onChange={(e) => setData('fault', e.target.value)} rows={3} placeholder="Fault / reason" className={input} />
                    <input type="number" step="0.01" value={data.claim_amount} onChange={(e) => setData('claim_amount', e.target.value)} placeholder="Claim amount" className={input} />
                    <button type="submit" disabled={processing || !data.fault || !data.claim_amount} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Raise claim</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
