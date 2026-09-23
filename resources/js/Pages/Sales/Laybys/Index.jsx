import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

export default function LaybysIndex({ laybys, customers, branches, minDepositPct }) {
    const { data, setData, post, processing, reset } = useForm({ customer_id: '', branch_id: branches[0]?.id ?? '', total: '', deposit: '', notes: '' });
    const [payFor, setPayFor] = useState(null);
    const [payAmt, setPayAmt] = useState('');
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const minDep = data.total ? (Number(data.total) * minDepositPct / 100).toFixed(2) : '0.00';

    function pay(id) {
        router.post(route('sales.laybys.payment', id), { amount: Number(payAmt), method: 'cash' }, { preserveScroll: true, onSuccess: () => { setPayFor(null); setPayAmt(''); } });
    }

    return (
        <ModuleLayout navConfig={navConfig} title="Lay-by Management" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Lay-by Management' }]}>
            <Head title="Lay-by Management" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {laybys.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><CalendarClock className="w-8 h-8 mx-auto mb-2 text-slate-300" />No lay-bys yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Lay-by</th><th className="px-2 py-2">Customer</th><th className="px-2 py-2 text-right">Total</th><th className="px-2 py-2 text-right">Balance</th><th className="px-2 py-2">Status</th><th className="px-4 py-2"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {laybys.data.map((l) => (
                                    <tr key={l.id}>
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{l.layby_number}</td>
                                        <td className="px-2 py-2 text-slate-600">{l.customer}</td>
                                        <td className="px-2 py-2 text-right tabular-nums"><MoneyDisplay amount={l.total} /></td>
                                        <td className="px-2 py-2 text-right tabular-nums font-medium">{l.balance.toFixed(2)}</td>
                                        <td className="px-2 py-2"><StatusBadge status={l.status === 'active' ? 'in_progress' : l.status} /></td>
                                        <td className="px-4 py-2 text-right">
                                            {l.status === 'active' && (payFor === l.id ? (
                                                <span className="inline-flex items-center gap-1"><input type="number" value={payAmt} onChange={(e) => setPayAmt(e.target.value)} className="w-20 rounded border border-slate-300 px-2 py-1 text-xs text-right" placeholder="Amt" /><button onClick={() => pay(l.id)} disabled={!payAmt} className="rounded bg-orange-600 px-2 py-1 text-xs text-white disabled:opacity-40">Pay</button></span>
                                            ) : <button onClick={() => setPayFor(l.id)} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Payment</button>)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('sales.laybys.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New lay-by</h2>
                    <select value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} className={input}><option value="">Customer…</option>{customers.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
                    <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>{branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select>
                    <input type="number" step="0.01" value={data.total} onChange={(e) => setData('total', e.target.value)} placeholder="Total value" className={input} />
                    <input type="number" step="0.01" value={data.deposit} onChange={(e) => setData('deposit', e.target.value)} placeholder={`Deposit (min ${minDep})`} className={input} />
                    <p className="text-xs text-slate-400">Minimum deposit {minDepositPct}% = {minDep}.</p>
                    <button type="submit" disabled={processing || !data.customer_id || !data.total} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create lay-by</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
