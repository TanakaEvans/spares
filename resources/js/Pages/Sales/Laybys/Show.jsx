import { Head, useForm } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const methodLabel = { cash: 'Cash', card: 'Card', eft: 'EFT' };

function Stat({ label, children, tone = 'text-slate-800' }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-white p-4">
            <div className="text-xs uppercase tracking-wider text-slate-400">{label}</div>
            <div className={`mt-1 text-lg font-semibold ${tone}`}>{children}</div>
        </div>
    );
}

export default function LaybyShow({ layby }) {
    const { data, setData, post, processing, errors, reset } = useForm({ amount: '', method: 'cash' });
    const isActive = layby.status === 'active';
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function pay(e) {
        e.preventDefault();
        post(route('sales.laybys.payment', layby.id), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={layby.layby_number}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Lay-by Management', href: route('sales.laybys.index') },
                { label: layby.layby_number },
            ]}
        >
            <Head title={layby.layby_number} />

            <div className="max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{layby.layby_number}</h1>
                            <StatusBadge status={layby.status === 'active' ? 'in_progress' : layby.status} label={layby.status} />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            <EntityLink type="customer" id={layby.customer?.id} label={layby.customer?.name} className="font-medium" />
                            {layby.customer?.customer_number && <span className="font-mono text-slate-400"> {layby.customer.customer_number}</span>}
                            {' · opened '}{layby.created_at}
                            {layby.branch && <> · <EntityLink type="branch" id={layby.branch.id} label={layby.branch.name} muted /></>}
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <Stat label="Lay-by total"><MoneyDisplay amount={layby.total} /></Stat>
                    <Stat label="Paid to date" tone="text-emerald-600"><MoneyDisplay amount={layby.deposit_paid} /></Stat>
                    <Stat label="Balance outstanding" tone={layby.balance > 0 ? 'text-amber-600' : 'text-emerald-600'}><MoneyDisplay amount={layby.balance} /></Stat>
                </div>

                {layby.notes && (
                    <div className="rounded-xl border border-slate-100 bg-white p-4 text-sm text-slate-600">{layby.notes}</div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-3 border-b border-slate-100">
                            <h2 className="text-sm font-semibold text-slate-700">Payments</h2>
                        </div>
                        {layby.payments.length === 0 ? (
                            <div className="py-10 text-center text-sm text-slate-400">
                                <CalendarClock className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                No payments recorded yet.
                            </div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-50">
                                        <th className="px-4 py-2">Date</th>
                                        <th className="px-2 py-2">Method</th>
                                        <th className="px-4 py-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {layby.payments.map((p) => (
                                        <tr key={p.id}>
                                            <td className="px-4 py-2 tabular-nums text-slate-500">{p.paid_on}</td>
                                            <td className="px-2 py-2 text-slate-600">{methodLabel[p.method] ?? p.method}</td>
                                            <td className="px-4 py-2 text-right tabular-nums font-medium"><MoneyDisplay amount={p.amount} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {isActive ? (
                        <form onSubmit={pay} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                            <h2 className="text-sm font-semibold text-slate-700">Record a payment</h2>
                            <div className="space-y-1">
                                <label htmlFor="lb-amount" className="block text-xs font-medium text-slate-500">Amount</label>
                                <input id="lb-amount" type="number" step="0.01" min="0.01" max={layby.balance} value={data.amount}
                                    onChange={(e) => setData('amount', e.target.value)} placeholder={`Up to ${layby.balance.toFixed(2)}`} className={`${input} text-right tabular-nums`} />
                                {errors.amount && <p className="text-xs text-red-600">{errors.amount}</p>}
                            </div>
                            <div className="space-y-1">
                                <label htmlFor="lb-method" className="block text-xs font-medium text-slate-500">Method</label>
                                <select id="lb-method" value={data.method} onChange={(e) => setData('method', e.target.value)} className={input}>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="eft">EFT</option>
                                </select>
                            </div>
                            <button type="submit" disabled={processing || !data.amount || Number(data.amount) <= 0}
                                className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                                {processing ? 'Saving…' : 'Record payment'}
                            </button>
                            <p className="text-xs text-slate-400">Paying the full balance marks the lay-by completed and releases the goods.</p>
                        </form>
                    ) : (
                        <div className="rounded-xl border border-slate-100 bg-slate-50 p-5 text-sm text-slate-500 h-fit">
                            This lay-by is <strong className="text-slate-700">{layby.status}</strong> — no further payments can be taken.
                        </div>
                    )}
                </div>

                <RelatedLinks
                    groups={[
                        { title: 'Customer', items: layby.customer ? [{ type: 'customer', id: layby.customer.id, label: layby.customer.name }] : [] },
                        { title: 'Branch', items: layby.branch ? [{ type: 'branch', id: layby.branch.id, label: layby.branch.name }] : [] },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}
