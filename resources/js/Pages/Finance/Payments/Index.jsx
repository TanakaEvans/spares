import { Head, Link, router } from '@inertiajs/react';
import { Plus, Rows3, Play } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

export default function PaymentsIndex({ payments, ageing, filters }) {
    const { rows, totals } = ageing;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Supplier Payments"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Supplier Payments' }]}
        >
            <Head title="Supplier Payments" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <label className="text-sm text-slate-500">Ageing as at</label>
                        <input type="date" value={filters.as_of}
                            onChange={(e) => router.get(route('finance.payments.index'), { as_of: e.target.value }, { preserveState: true, replace: true })}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('finance.payment-run.index')} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <Play className="w-4 h-4" /> Payment Run
                        </Link>
                        <Link href={route('finance.payments.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            <Plus className="w-4 h-4" /> New Payment
                        </Link>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50"><h2 className="text-sm font-semibold text-slate-700">Aged Creditors — as at {ageing.as_of}</h2></div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2">Supplier</th>
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
                                <tr key={r.supplier_id} className="hover:bg-slate-50">
                                    <td className="px-4 py-2"><Link href={route('suppliers.show', r.supplier_id)} className="font-medium text-slate-700 hover:text-orange-600">{r.supplier}</Link></td>
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

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Recent payments</h2></div>
                    {payments.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Rows3 className="w-8 h-8 mx-auto mb-2 text-slate-300" />No payments captured yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Payment</th>
                                    <th className="px-2 py-2">Supplier</th>
                                    <th className="px-2 py-2">Date</th>
                                    <th className="px-2 py-2">Method</th>
                                    <th className="px-2 py-2">Batch</th>
                                    <th className="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {payments.data.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{p.payment_number}</td>
                                        <td className="px-2 py-2"><Link href={route('suppliers.show', p.supplier_id)} className="text-slate-700 hover:text-orange-600">{p.supplier}</Link></td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{p.payment_date}</td>
                                        <td className="px-2 py-2 capitalize text-slate-500">{p.method}</td>
                                        <td className="px-2 py-2 font-mono text-xs text-slate-400">{p.batch_ref ?? '—'}</td>
                                        <td className="px-4 py-2 text-right tabular-nums"><MoneyDisplay amount={p.amount} /></td>
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
