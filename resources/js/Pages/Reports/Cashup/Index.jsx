import { Head, router } from '@inertiajs/react';
import { Printer, Banknote } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { StatTile } from '@/Components/Charts';
import EntityLink from '@/Components/EntityLink';

const money = (n) => Number(n || 0).toFixed(2);

export default function CashupIndex({ cashup, branches, filters }) {
    function apply(params) {
        router.get(route('reports.cashup'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const t = cashup.totals;
    const printUrl = route('reports.cashup.print', { date: filters.date, branch_id: filters.branch_id ?? '' });

    return (
        <ModuleLayout navConfig={navConfig} title="Daily Cash-up"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Daily Cash-up' }]}>
            <Head title="Daily Cash-up" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <input type="date" aria-label="Cash-up date" value={filters.date} onChange={(e) => apply({ date: e.target.value })} className={input} />
                    <select aria-label="Branch" value={filters.branch_id ?? ''} onChange={(e) => apply({ branch_id: e.target.value })} className={input}>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <a href={printUrl} target="_blank" rel="noreferrer"
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Printer className="w-4 h-4" /> Print cash-up
                    </a>
                </div>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <StatTile label="Banked (cash+card+eft)" value={t.banked} money />
                    <StatTile label="Cash in drawer" value={t.cash} money />
                    <StatTile label="On account (credit)" value={t.credit} money />
                    <StatTile label="Change given" value={t.change_given} money />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-700">By payment method</div>
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2">Method</th><th className="px-2 py-2 text-right">Sales</th><th className="px-2 py-2 text-right">Receipts</th><th className="px-4 py-2 text-right">Total</th><th className="px-3 py-2 text-right">Txns</th>
                            </tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {cashup.by_method.map((m) => (
                                    <tr key={m.method}>
                                        <td className="px-4 py-2 capitalize text-slate-700">{m.method}{m.method === 'account' && <span className="text-xs text-slate-400"> (credit)</span>}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(m.sales)}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(m.receipts)}</td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium">{money(m.total)}</td>
                                        <td className="px-3 py-2 text-right tabular-nums text-slate-400">{m.count}</td>
                                    </tr>
                                ))}
                                <tr className="border-t-2 border-slate-200 font-semibold">
                                    <td className="px-4 py-2">Grand total</td>
                                    <td /><td />
                                    <td className="px-4 py-2 text-right tabular-nums"><MoneyDisplay amount={t.grand_total} /></td>
                                    <td className="px-3 py-2 text-right tabular-nums text-slate-400">{t.invoice_count + t.receipt_count}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-700">By cashier</div>
                        {cashup.by_cashier.length === 0 ? (
                            <div className="py-10 text-center text-sm text-slate-400"><Banknote className="w-8 h-8 mx-auto mb-2 text-slate-300" />No takings on this day.</div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Cashier</th><th className="px-2 py-2 text-right">Cash</th><th className="px-2 py-2 text-right">Card</th><th className="px-2 py-2 text-right">EFT</th><th className="px-2 py-2 text-right">Account</th><th className="px-4 py-2 text-right">Total</th>
                                </tr></thead>
                                <tbody className="divide-y divide-slate-50">
                                    {cashup.by_cashier.map((c, i) => (
                                        <tr key={i}>
                                            <td className="px-4 py-2"><EntityLink type="user" id={c.user_id} label={c.cashier} /></td>
                                            <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(c.cash)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(c.card)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(c.eft)}</td>
                                            <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(c.account)}</td>
                                            <td className="px-4 py-2 text-right tabular-nums font-medium">{money(c.total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
