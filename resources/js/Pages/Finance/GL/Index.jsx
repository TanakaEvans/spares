import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

export default function GlIndex({ accounts, account, movements, filters }) {
    function apply(params) {
        router.get(route('finance.gl.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    }

    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="GL Enquiry"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'GL Enquiry' }]}
        >
            <Head title="GL Enquiry" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <select aria-label="Account" value={filters.account_id ?? ''} onChange={(e) => apply({ account_id: e.target.value })} className={`${input} w-80`}>
                        {accounts.map((a) => <option key={a.id} value={a.id}>{a.account_code} — {a.name}</option>)}
                    </select>
                    <input type="date" aria-label="From" value={filters.from} onChange={(e) => apply({ from: e.target.value })} className={input} />
                    <input type="date" aria-label="To" value={filters.to} onChange={(e) => apply({ to: e.target.value })} className={input} />
                </div>

                {account && (
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                            <div>
                                <span className="font-mono text-slate-400">{account.account_code}</span>
                                <span className="ml-2 font-semibold text-slate-800">{account.name}</span>
                            </div>
                            <div className="text-sm text-slate-500">
                                Opening <span className="tabular-nums font-medium text-slate-700"><MoneyDisplay amount={movements.opening} /></span>
                                <span className="mx-2 text-slate-300">·</span>
                                Closing <span className="tabular-nums font-semibold text-slate-900"><MoneyDisplay amount={movements.closing} /></span>
                            </div>
                        </div>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Date</th>
                                    <th className="px-2 py-2">Journal</th>
                                    <th className="px-2 py-2">Description</th>
                                    <th className="px-2 py-2 text-right">Debit</th>
                                    <th className="px-2 py-2 text-right">Credit</th>
                                    <th className="px-4 py-2 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {movements.rows.length === 0 ? (
                                    <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-400">No movements in this range.</td></tr>
                                ) : movements.rows.map((r, i) => (
                                    <tr key={i} className="hover:bg-slate-50">
                                        <td className="px-4 py-2 tabular-nums text-slate-500">{r.date}</td>
                                        <td className="px-2 py-2 font-mono text-slate-500">{r.journal_number}</td>
                                        <td className="px-2 py-2 text-slate-600">{r.description}{r.reference && <span className="text-slate-400"> · {r.reference}</span>}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.debit > 0 ? r.debit.toFixed(2) : ''}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.credit > 0 ? r.credit.toFixed(2) : ''}</td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-800">{r.balance.toFixed(2)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </ModuleLayout>
    );
}
