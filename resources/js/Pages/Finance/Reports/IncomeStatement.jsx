import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

function Section({ title, rows, total }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
            <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50"><h2 className="text-sm font-semibold text-slate-700">{title}</h2></div>
            <table className="w-full text-sm">
                <tbody className="divide-y divide-slate-50">
                    {rows.length === 0 ? (
                        <tr><td className="px-4 py-3 text-slate-400">No movement.</td></tr>
                    ) : rows.map((r) => (
                        <tr key={r.account_code}>
                            <td className="px-4 py-2"><span className="font-mono text-slate-400">{r.account_code}</span> <span className="text-slate-700">{r.name}</span></td>
                            <td className="px-4 py-2 text-right tabular-nums text-slate-700"><MoneyDisplay amount={r.amount} /></td>
                        </tr>
                    ))}
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

export default function IncomeStatement({ report, filters, branches }) {
    function apply(params) {
        router.get(route('finance.reports.income-statement'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Income Statement"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Income Statement' }]}
        >
            <Head title="Income Statement" />

            <div className="max-w-3xl space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <label className="text-sm text-slate-500">From</label>
                    <input type="date" value={filters.from} onChange={(e) => apply({ from: e.target.value })} className={input} />
                    <label className="text-sm text-slate-500">to</label>
                    <input type="date" value={filters.to} onChange={(e) => apply({ to: e.target.value })} className={input} />
                    <select aria-label="Branch" value={filters.branch_id ?? ''} onChange={(e) => apply({ branch_id: e.target.value })} className={input}>
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                </div>

                <Section title="Revenue" rows={report.revenue} total={report.total_revenue} />
                <Section title="Expenses" rows={report.expenses} total={report.total_expense} />

                <div className={`rounded-xl border p-4 flex items-center justify-between ${report.net_profit >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'}`}>
                    <span className="text-sm font-semibold text-slate-700">Net {report.net_profit >= 0 ? 'Profit' : 'Loss'}</span>
                    <span className={`text-lg font-bold tabular-nums ${report.net_profit >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>
                        <MoneyDisplay amount={report.net_profit} />
                    </span>
                </div>
            </div>
        </ModuleLayout>
    );
}
