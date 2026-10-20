import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, AlertTriangle, Upload, ArrowRight } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

export default function ImportResult({ import: imp, needsOpeningPost }) {
    const clean = imp.errors.length === 0;

    return (
        <ModuleLayout navConfig={navConfig} title={`${imp.label} — upload results`}
            breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Data Upload', href: route('imports.index') }, { label: 'Results' }]}>
            <Head title="Upload results" />

            <div className="max-w-4xl space-y-6">
                <div className={`rounded-xl border p-6 ${clean ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
                    <div className="flex items-start gap-3">
                        {clean ? <CheckCircle2 className="mt-0.5 h-6 w-6 text-emerald-600" /> : <AlertTriangle className="mt-0.5 h-6 w-6 text-amber-600" />}
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">{clean ? 'Upload complete' : 'Upload complete — some rows need attention'}</h2>
                            <p className="mt-1 text-sm text-slate-600">{imp.file_name} · {imp.at}</p>
                        </div>
                    </div>
                    <div className="mt-5 grid grid-cols-3 gap-3">
                        {[['Created', imp.created, 'text-emerald-700'], ['Updated', imp.updated, 'text-slate-800'], ['Skipped', imp.skipped, imp.skipped > 0 ? 'text-red-600' : 'text-slate-400']].map(([l, v, c]) => (
                            <div key={l} className="rounded-lg bg-white/70 px-4 py-3">
                                <div className="text-xs uppercase tracking-wide text-slate-400">{l}</div>
                                <div className={`text-2xl font-bold tabular-nums ${c}`}>{v}</div>
                            </div>
                        ))}
                    </div>
                </div>

                {needsOpeningPost && (
                    <div className="rounded-xl border border-slate-200 bg-white p-5">
                        <h3 className="text-sm font-semibold text-slate-900">One more step for stock: post it to the books</h3>
                        <p className="mt-1 text-sm text-slate-600">Your quantities are on the shelf; posting records their value in the Inventory account so the accounts match.</p>
                        <button onClick={() => router.post(route('admin.health.post-opening-stock'))}
                            className="mt-3 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Post opening stock to the books <ArrowRight className="h-4 w-4" />
                        </button>
                    </div>
                )}

                {!clean && (
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-700">
                            Rows that were skipped ({imp.errors.length}) — fix these in your sheet and upload it again; the good rows are already in.
                        </div>
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2 w-24">Row</th><th className="px-4 py-2">Reason</th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {imp.errors.map((e, i) => (
                                    <tr key={i}><td className="px-4 py-2 tabular-nums text-slate-500">{e.row}</td><td className="px-4 py-2 text-red-700">{e.message}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="flex flex-wrap gap-3">
                    <Link href={route(imp.listRoute)} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        See the records <ArrowRight className="h-4 w-4" />
                    </Link>
                    <Link href={route('imports.show', imp.entity)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <Upload className="h-4 w-4" /> Upload another file
                    </Link>
                    <Link href={route('imports.index')} className="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                        Back to Data Upload
                    </Link>
                </div>
            </div>
        </ModuleLayout>
    );
}
