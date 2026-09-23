import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, AlertTriangle, XCircle, RefreshCw, Wrench, ArrowRight, HeartPulse } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

const ICON = {
    ok: <CheckCircle2 className="w-5 h-5 text-emerald-500" />,
    warn: <AlertTriangle className="w-5 h-5 text-amber-500" />,
    fail: <XCircle className="w-5 h-5 text-red-500" />,
};
const OVERALL = {
    ok: { dot: 'bg-emerald-500', label: 'All systems healthy', tone: 'text-emerald-700 bg-emerald-50 border-emerald-200' },
    warn: { dot: 'bg-amber-500', label: 'Attention needed', tone: 'text-amber-700 bg-amber-50 border-amber-200' },
    fail: { dot: 'bg-red-500', label: 'Integrity failure — act now', tone: 'text-red-700 bg-red-50 border-red-200' },
};

export default function HealthIndex({ groups, summary, ranAt }) {
    const overall = OVERALL[summary.overall];

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="System Health"
            breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'System Health' }]}
        >
            <Head title="System Health" />

            <div className="max-w-4xl space-y-6">
                <div className={`flex flex-wrap items-center justify-between gap-3 rounded-xl border px-5 py-4 ${overall.tone}`}>
                    <div className="flex items-center gap-3">
                        <span className={`w-3 h-3 rounded-full ${overall.dot} animate-pulse`} />
                        <div>
                            <div className="text-base font-bold">{overall.label}</div>
                            <div className="text-xs opacity-70">
                                {summary.ok} passing · {summary.warn} warnings · {summary.fail} failures · checked {ranAt}
                            </div>
                        </div>
                    </div>
                    <button onClick={() => router.reload()} className="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <RefreshCw className="w-4 h-4" /> Run all checks
                    </button>
                </div>

                {Object.entries(groups).map(([group, checks]) => (
                    <div key={group} className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50">
                            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">{group} Integrity</h2>
                        </div>
                        <ul className="divide-y divide-slate-50">
                            {checks.map((c) => (
                                <li key={c.key} className="flex items-center gap-3 px-4 py-3">
                                    <span className="shrink-0">{ICON[c.status]}</span>
                                    <div className="flex-1 min-w-0">
                                        <div className="text-sm font-medium text-slate-800">{c.label}</div>
                                        <div className={`text-xs ${c.status === 'fail' ? 'text-red-600' : c.status === 'warn' ? 'text-amber-600' : 'text-slate-400'}`}>{c.message}</div>
                                    </div>
                                    {c.fix && (
                                        <button onClick={() => router.post(route(c.fix))} className="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700">
                                            <Wrench className="w-3.5 h-3.5" /> Fix
                                        </button>
                                    )}
                                    {!c.fix && c.link && c.status !== 'ok' && (
                                        <Link href={route(c.link)} className="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-orange-600">
                                            View <ArrowRight className="w-3.5 h-3.5" />
                                        </Link>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}

                <p className="flex items-center gap-2 text-xs text-slate-400">
                    <HeartPulse className="w-3.5 h-3.5" />
                    Every figure is recomputed live from the ledger — this screen can't show a stale “all-clear”.
                </p>
            </div>
        </ModuleLayout>
    );
}
