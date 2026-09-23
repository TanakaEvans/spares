import { Head, Link, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { HBarList } from '@/Components/Charts';

export default function CustomerReport({ ageing, topCustomers, dormant, creditReview, filters }) {
    function apply(params) {
        router.get(route('reports.customers.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Customer Reports"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Customers' }]}>
            <Head title="Customer Reports" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <label className="text-sm text-slate-500">Top customers from</label>
                    <input type="date" value={filters.from} onChange={(e) => apply({ from: e.target.value })} className={input} />
                    <label className="text-sm text-slate-500">to</label>
                    <input type="date" value={filters.to} onChange={(e) => apply({ to: e.target.value })} className={input} />
                    <label className="text-sm text-slate-500 ml-4">Dormant after</label>
                    <select value={filters.dormant_days} onChange={(e) => apply({ dormant_days: e.target.value })} className={input}>
                        {[60, 90, 180].map((d) => <option key={d} value={d}>{d} days</option>)}
                    </select>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Top customers by spend</h2>
                        <HBarList data={topCustomers.map((c) => ({ label: c.customer, value: c.spend }))} labelKey="label" valueKey="value" />
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Credit review (≥80% of limit / on hold)</h2></div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {creditReview.length === 0 ? (
                                    <tr><td className="px-4 py-6 text-center text-slate-400">No accounts near their limit.</td></tr>
                                ) : creditReview.map((c) => (
                                    <tr key={c.customer_id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2"><Link href={route('customers.show', c.customer_id)} className="font-medium text-slate-700 hover:text-orange-600">{c.customer}</Link></td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500"><MoneyDisplay amount={c.balance} /> / <MoneyDisplay amount={c.credit_limit} /></td>
                                        <td className={`px-4 py-2 text-right tabular-nums font-medium ${c.utilisation >= 100 ? 'text-red-600' : 'text-amber-600'}`}>{c.utilisation}%{c.on_hold && <span className="ml-1 text-red-500">hold</span>}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Debtors ageing</h2></div>
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Customer</th><th className="px-2 py-2 text-right">Current</th><th className="px-2 py-2 text-right">30</th><th className="px-2 py-2 text-right">60</th><th className="px-4 py-2 text-right">90+</th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {ageing.rows.length === 0 ? <tr><td colSpan={5} className="px-4 py-6 text-center text-slate-400">Nothing outstanding.</td></tr> : ageing.rows.map((r) => (
                                    <tr key={r.customer_id}><td className="px-4 py-2 text-slate-700">{r.customer}</td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.current.toFixed(2)}</td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.b30.toFixed(2)}</td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.b60.toFixed(2)}</td><td className={`px-4 py-2 text-right tabular-nums ${r.b90 > 0 ? 'text-red-600 font-medium' : 'text-slate-500'}`}>{r.b90.toFixed(2)}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Dormant customers</h2></div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {dormant.length === 0 ? <tr><td className="px-4 py-6 text-center text-slate-400">No dormant customers.</td></tr> : dormant.map((c) => (
                                    <tr key={c.customer_id}><td className="px-4 py-2"><Link href={route('customers.show', c.customer_id)} className="text-slate-700 hover:text-orange-600">{c.customer}</Link><span className="block text-xs text-slate-400">{c.phone}</span></td><td className="px-4 py-2 text-right tabular-nums text-slate-400">last {c.last_invoice ?? '—'}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
