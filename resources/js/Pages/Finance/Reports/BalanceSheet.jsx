import { Head, router } from '@inertiajs/react';
import { CheckCircle2, AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

function Section({ title, rows, total, extra }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
            <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50"><h2 className="text-sm font-semibold text-slate-700">{title}</h2></div>
            <table className="w-full text-sm">
                <tbody className="divide-y divide-slate-50">
                    {rows.map((r) => (
                        <tr key={r.account_code}>
                            <td className="px-4 py-2"><span className="font-mono text-slate-400">{r.account_code}</span> <span className="text-slate-700">{r.name}</span></td>
                            <td className="px-4 py-2 text-right tabular-nums text-slate-700"><MoneyDisplay amount={r.amount} /></td>
                        </tr>
                    ))}
                    {extra && (
                        <tr>
                            <td className="px-4 py-2 text-slate-700">{extra.label}</td>
                            <td className="px-4 py-2 text-right tabular-nums text-slate-700"><MoneyDisplay amount={extra.amount} /></td>
                        </tr>
                    )}
                </tbody>
                <tfoot>
                    <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                        <td className="px-4 py-2.5 text-right">Total {title}</td>
                        <td className="px-4 py-2.5 text-right tabular-nums"><MoneyDisplay amount={total} /></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    );
}

export default function BalanceSheet({ report, filters, branches }) {
    function apply(params) {
        router.get(route('finance.reports.balance-sheet'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Balance Sheet"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Balance Sheet' }]}
        >
            <Head title="Balance Sheet" />

            <div className="max-w-4xl space-y-4">
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

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                    <Section title="Assets" rows={report.assets} total={report.total_assets} />
                    <div className="space-y-4">
                        <Section title="Liabilities" rows={report.liabilities} total={report.total_liabilities} />
                        <Section
                            title="Equity"
                            rows={report.equity}
                            total={report.total_equity}
                            extra={{ label: 'Retained earnings (current year)', amount: report.retained_current_year }}
                        />
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 flex items-center justify-between text-sm">
                    <span className="font-semibold text-slate-700">Assets = Liabilities + Equity</span>
                    <span className="tabular-nums text-slate-700">
                        <MoneyDisplay amount={report.total_assets} /> = <MoneyDisplay amount={report.total_liabilities} /> + <MoneyDisplay amount={report.total_equity} />
                    </span>
                </div>
            </div>
        </ModuleLayout>
    );
}
