import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { StatTile } from '@/Components/Charts';

export default function WorkshopReport({ productivity, profitability, branches, filters }) {
    function apply(params) {
        router.get(route('reports.workshop.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Workshop Reports"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Workshop' }]}>
            <Head title="Workshop Reports" />

            <div className="space-y-6">
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

                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <StatTile label="Jobs opened" value={productivity.opened} />
                    <StatTile label="Completed" value={productivity.completed} />
                    <StatTile label="Invoiced" value={productivity.invoiced} tone="emerald" />
                    <StatTile label="Revenue" value={productivity.revenue} money />
                    <StatTile label="Avg. job value" value={productivity.avg_job_value} money />
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Job profitability</h2></div>
                    <table className="w-full text-sm">
                        <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Job</th><th className="px-2 py-2">Customer</th><th className="px-2 py-2 text-right">Cost</th><th className="px-2 py-2 text-right">Billed</th><th className="px-4 py-2 text-right">Margin</th></tr></thead>
                        <tbody className="divide-y divide-slate-50">
                            {profitability.length === 0 ? <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-400">No invoiced jobs in range.</td></tr> : profitability.map((j, i) => (
                                <tr key={i} className="hover:bg-slate-50">
                                    <td className="px-4 py-2 font-mono font-semibold text-slate-700">{j.job_number}</td>
                                    <td className="px-2 py-2 text-slate-600">{j.customer}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-500"><MoneyDisplay amount={j.cost} /></td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-700"><MoneyDisplay amount={j.billed} /></td>
                                    <td className={`px-4 py-2 text-right tabular-nums font-medium ${j.margin_pct >= 30 ? 'text-emerald-600' : j.margin_pct >= 0 ? 'text-amber-600' : 'text-red-600'}`}>{j.margin_pct}%</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </ModuleLayout>
    );
}
