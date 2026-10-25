import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import FormField from '@/Components/FormField';
import { fmt } from '@/Components/Pos/posMath';
import { useListFilter } from '@/lib/listFilters';

const STATUS_PILLS = [
    { key: '', label: 'All' },
    { key: 'active', label: 'Paying off' },
    { key: 'completed', label: 'Completed' },
    { key: 'cancelled', label: 'Cancelled' },
];

export default function LaybysIndex({ laybys, customers, branches, minDepositPct, stats, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({ customer_id: '', branch_id: branches[0]?.id ?? '', total: '', deposit: '', notes: '' });
    const [payFor, setPayFor] = useState(null);
    const [payAmt, setPayAmt] = useState('');
    const [payMethod, setPayMethod] = useState('cash');
    const filter = useListFilter('sales.laybys.index', filters ?? {});
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const minDep = data.total ? (Number(data.total) * minDepositPct / 100).toFixed(2) : '0.00';

    function pay(id) {
        router.post(route('sales.laybys.payment', id), { amount: Number(payAmt), method: payMethod }, { preserveScroll: true, onSuccess: () => { setPayFor(null); setPayAmt(''); } });
    }

    const progress = (l) => Math.min(100, Math.round((l.deposit_paid / l.total) * 100));

    return (
        <ModuleLayout navConfig={navConfig} title="Lay-by Management" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Lay-by Management' }]}>
            <Head title="Lay-by Management" />
            <div className="space-y-4">
                <StatChips items={[
                    { label: 'Active lay-bys', value: stats.active, icon: CalendarClock },
                    { label: 'Still to collect', value: `R${fmt(stats.outstanding)}`, tone: stats.outstanding > 0 ? 'amber' : 'slate' },
                ]} />

                <ListToolbar
                    filters={filters ?? {}}
                    onFilter={filter}
                    searchPlaceholder="Lay-by number or customer…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="sales.laybys.export"
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
                                { key: 'layby_number', label: 'Lay-by', sortable: 'layby_number', render: (l) => (
                                    <Link href={route('sales.laybys.show', l.id)} className="font-mono font-semibold text-slate-800 hover:text-orange-600 hover:underline underline-offset-2 decoration-orange-300">{l.layby_number}</Link>
                                ) },
                                { key: 'customer', label: 'Customer', render: (l) => <EntityLink type="customer" id={l.customer_id} label={l.customer} /> },
                                { key: 'paid', label: 'Paid', render: (l) => (
                                    <div className="flex items-center gap-2">
                                        <div className="h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden">
                                            <div className="h-full rounded-full bg-emerald-500" style={{ width: `${progress(l)}%` }} />
                                        </div>
                                        <span className="text-[11px] tabular-nums text-slate-400">{progress(l)}%</span>
                                    </div>
                                ) },
                                { key: 'balance', label: 'Balance', align: 'right', render: (l) => <MoneyDisplay amount={l.balance} /> },
                                { key: 'status', label: 'Status', sortable: 'status', render: (l) => <StatusBadge status={l.status === 'active' ? 'in_progress' : l.status} label={l.status === 'active' ? 'paying off' : l.status} /> },
                                { key: 'actions', label: '', render: (l) => (
                                    <div onClick={(e) => e.stopPropagation()} className="text-right">
                                        {l.status === 'active' && (payFor === l.id ? (
                                            <span className="inline-flex items-center gap-1">
                                                <select value={payMethod} onChange={(e) => setPayMethod(e.target.value)} aria-label="Method" className="rounded border border-slate-300 px-1.5 py-1 text-xs"><option value="cash">Cash</option><option value="card">Card</option><option value="eft">EFT</option></select>
                                                <input type="number" value={payAmt} onChange={(e) => setPayAmt(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && payAmt && pay(l.id)} className="w-20 rounded border border-slate-300 px-2 py-1 text-xs text-right" placeholder="Amt" autoFocus />
                                                <button onClick={() => pay(l.id)} disabled={!payAmt} className="rounded bg-orange-600 px-2 py-1 text-xs text-white disabled:opacity-40">Pay</button>
                                            </span>
                                        ) : <button onClick={() => { setPayFor(l.id); setPayAmt(String(l.balance.toFixed(2))); }} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Payment</button>)}
                                    </div>
                                ) },
                            ]}
                            rows={laybys.data}
                            pagination={laybys}
                            sorting={{ filters: filters ?? {}, onFilter: filter }}
                            rowHref={(l) => route('sales.laybys.show', l.id)}
                            emptyTitle="No lay-bys yet"
                            emptyMessage="A customer pays a deposit now and the balance over time — goods leave when paid in full."
                            emptyIcon={CalendarClock}
                        />
                    </div>
                    <form onSubmit={(e) => { e.preventDefault(); post(route('sales.laybys.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                        <h2 className="text-sm font-semibold text-slate-700">New lay-by</h2>
                        <FormField label="Customer" error={errors.customer_id}>
                            <select value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} className={input}><option value="">Customer…</option>{customers.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
                        </FormField>
                        <FormField label="Branch">
                            <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>{branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select>
                        </FormField>
                        <FormField label="Total value of the goods" error={errors.total}>
                            <input type="number" step="0.01" min="0" value={data.total} onChange={(e) => setData('total', e.target.value)} placeholder="0.00" className={`${input} text-right tabular-nums`} />
                        </FormField>
                        <FormField label={`Deposit today — min ${minDepositPct}%`} error={errors.deposit}>
                            <input type="number" step="0.01" min="0" value={data.deposit} onChange={(e) => setData('deposit', e.target.value)} placeholder={minDep} className={`${input} text-right tabular-nums`} />
                        </FormField>
                        <button type="submit" disabled={processing || !data.customer_id || !data.total || Number(data.deposit) < Number(minDep)}
                            title={Number(data.deposit) < Number(minDep) ? `Deposit must be at least ${minDep}` : undefined}
                            className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create lay-by</button>
                    </form>
                </div>
            </div>
        </ModuleLayout>
    );
}
