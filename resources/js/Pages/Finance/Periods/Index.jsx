import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import StatusBadge from '@/Components/StatusBadge';

export default function PeriodsIndex({ years }) {
    function transition(period, action) {
        router.post(route('finance.periods.transition', period.id), { action }, { preserveScroll: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Financial Periods"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Periods' }]}
        >
            <Head title="Financial Periods" />

            <div className="max-w-3xl space-y-6">
                <p className="text-sm text-slate-500">
                    Postings are only allowed into <strong>open</strong> periods. Close a period at month-end to freeze it; locking is permanent.
                </p>

                {years.map((year) => (
                    <div key={year.id} className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 bg-slate-50">
                            <h2 className="text-sm font-semibold text-slate-700">{year.name}</h2>
                            <StatusBadge status={year.status} />
                        </div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {year.periods.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2 font-medium text-slate-700 w-40">{p.name}</td>
                                        <td className="px-2 py-2 tabular-nums text-slate-400">{p.start_date} → {p.end_date}</td>
                                        <td className="px-2 py-2"><StatusBadge status={p.status} /></td>
                                        <td className="px-4 py-2 text-right">
                                            {p.status === 'open' && (
                                                <button onClick={() => transition(p, 'close')} className="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Close</button>
                                            )}
                                            {p.status === 'closed' && (
                                                <button onClick={() => transition(p, 'reopen')} className="rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Reopen</button>
                                            )}
                                            {p.status === 'locked' && <span className="text-xs text-slate-400">locked</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
