import { Head, router } from '@inertiajs/react';
import { CheckCircle2, AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';

export default function TrialBalance({ report, filters, branches }) {
    function apply(params) {
        router.get(route('finance.reports.trial-balance'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Trial Balance"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Trial Balance' }]}
        >
            <Head title="Trial Balance" />

            <div className="max-w-3xl space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <label className="text-sm text-slate-500">As at</label>
                    <input type="date" value={filters.to} onChange={(e) => apply({ to: e.target.value })} className={input} />
                    <select aria-label="Branch" value={filters.branch_id ?? ''} onChange={(e) => apply({ branch_id: e.target.value })} className={input}>
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <span className={`ml-auto inline-flex items-center gap-1.5 text-sm font-medium ${report.balanced ? 'text-emerald-600' : 'text-red-600'}`}>
                        {report.balanced ? <CheckCircle2 className="w-4 h-4" /> : <AlertTriangle className="w-4 h-4" />}
                        {report.balanced ? 'Balanced' : 'Out of balance'}
                    </span>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Account</th>
                                <th className="px-2 py-2.5 text-right">Debit</th>
                                <th className="px-4 py-2.5 text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {report.rows.map((r) => (
                                <tr key={r.account_code} className="hover:bg-slate-50">
                                    <td className="px-4 py-2"><span className="font-mono text-slate-400">{r.account_code}</span> <span className="text-slate-700">{r.name}</span></td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-700">{r.debit > 0 ? r.debit.toFixed(2) : ''}</td>
                                    <td className="px-4 py-2 text-right tabular-nums text-slate-700">{r.credit > 0 ? r.credit.toFixed(2) : ''}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                                <td className="px-4 py-2.5 text-right">Totals</td>
                                <td className="px-2 py-2.5 text-right tabular-nums">{report.total_debit.toFixed(2)}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums">{report.total_credit.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </ModuleLayout>
    );
}
