import { Head, Link, router } from '@inertiajs/react';
import { Plus, HandCoins } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

function AgeingTable({ ageing, linkFor, label }) {
    const { rows, totals } = ageing;
    return (
        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
            <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h2 className="text-sm font-semibold text-slate-700">Aged {label} — as at {ageing.as_of}</h2>
            </div>
            <table className="w-full text-sm">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                        <th className="px-4 py-2">{label}</th>
                        <th className="px-2 py-2 text-right">Current</th>
                        <th className="px-2 py-2 text-right">30</th>
                        <th className="px-2 py-2 text-right">60</th>
                        <th className="px-2 py-2 text-right">90+</th>
                        <th className="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                    {rows.length === 0 ? (
                        <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-400">Nothing outstanding.</td></tr>
                    ) : rows.map((r) => (
                        <tr key={r.customer_id ?? r.supplier_id} className="hover:bg-slate-50">
                            <td className="px-4 py-2">
                                {linkFor ? <Link href={linkFor(r)} className="font-medium text-slate-700 hover:text-orange-600">{r.customer ?? r.supplier}</Link>
                                    : <span className="font-medium text-slate-700">{r.customer ?? r.supplier}</span>}
                            </td>
                            <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.current.toFixed(2)}</td>
                            <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.b30.toFixed(2)}</td>
                            <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.b60.toFixed(2)}</td>
                            <td className={`px-2 py-2 text-right tabular-nums ${r.b90 > 0 ? 'text-red-600 font-medium' : 'text-slate-600'}`}>{r.b90.toFixed(2)}</td>
                            <td className="px-4 py-2 text-right tabular-nums font-semibold text-slate-800">{r.total.toFixed(2)}</td>
                        </tr>
                    ))}
                </tbody>
                <tfoot>
                    <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                        <td className="px-4 py-2.5 text-right">Totals</td>
                        <td className="px-2 py-2.5 text-right tabular-nums">{totals.current.toFixed(2)}</td>
                        <td className="px-2 py-2.5 text-right tabular-nums">{totals.b30.toFixed(2)}</td>
                        <td className="px-2 py-2.5 text-right tabular-nums">{totals.b60.toFixed(2)}</td>
                        <td className="px-2 py-2.5 text-right tabular-nums">{totals.b90.toFixed(2)}</td>
                        <td className="px-4 py-2.5 text-right tabular-nums">{totals.total.toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    );
}

export default function ReceiptsIndex({ receipts, ageing, filters }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Customer Receipts"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Customer Receipts' }]}
        >
            <Head title="Customer Receipts" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <label className="text-sm text-slate-500">Ageing as at</label>
                        <input type="date" value={filters.as_of}
                            onChange={(e) => router.get(route('finance.receipts.index'), { as_of: e.target.value }, { preserveState: true, replace: true })}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <Link href={route('finance.receipts.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Receipt
                    </Link>
                </div>

                <AgeingTable ageing={ageing} label="Debtors" linkFor={(r) => route('customers.show', r.customer_id)} />

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Recent receipts</h2></div>
                    {receipts.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><HandCoins className="w-8 h-8 mx-auto mb-2 text-slate-300" />No receipts captured yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Receipt</th>
                                    <th className="px-2 py-2">Customer</th>
                                    <th className="px-2 py-2">Date</th>
                                    <th className="px-2 py-2">Method</th>
                                    <th className="px-2 py-2 text-right">Amount</th>
                                    <th className="px-4 py-2 text-right">Unallocated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {receipts.data.map((r) => (
                                    <tr key={r.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{r.receipt_number}</td>
                                        <td className="px-2 py-2"><Link href={route('customers.show', r.customer_id)} className="text-slate-700 hover:text-orange-600">{r.customer}</Link></td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{r.receipt_date}</td>
                                        <td className="px-2 py-2 capitalize text-slate-500">{r.method}</td>
                                        <td className="px-2 py-2 text-right tabular-nums"><MoneyDisplay amount={r.amount} /></td>
                                        <td className={`px-4 py-2 text-right tabular-nums ${r.unallocated > 0 ? 'text-amber-600' : 'text-slate-400'}`}>{r.unallocated.toFixed(2)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
