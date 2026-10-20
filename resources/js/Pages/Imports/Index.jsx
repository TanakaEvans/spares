import { Head, Link, router } from '@inertiajs/react';
import { Boxes, PackageSearch, Truck, Users, Download, Upload, CheckCircle2, AlertTriangle, ArrowRight, History } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import MoneyDisplay from '@/Components/MoneyDisplay';

const ICONS = { Boxes, PackageSearch, Truck, Users };

export default function ImportsIndex({ steps, opening, branch, recent }) {
    const reconciled = Math.abs(opening.gap) < 0.01;

    return (
        <ModuleLayout navConfig={navConfig} title="Data Upload"
            breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Data Upload' }]}>
            <Head title="Data Upload" />

            <div className="space-y-6 max-w-6xl">
                <div className="rounded-xl border border-orange-100 bg-orange-50 px-5 py-4">
                    <h2 className="text-base font-semibold text-slate-900">Load your real data in three steps</h2>
                    <p className="mt-1 text-sm text-slate-600">
                        For each step: <strong>download the template</strong>, fill it in Excel, then <strong>upload</strong> it (Excel or CSV).
                        Rows are matched on part/supplier/customer numbers, so uploading a corrected sheet fixes records instead of duplicating them.
                        Start with <strong>Parts &amp; Stock</strong> — that one sheet loads your parts, prices, quantities and costs together.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {steps.map((s, i) => {
                        const Icon = ICONS[s.icon] ?? Boxes;
                        return (
                            <div key={s.entity} className="rounded-xl border border-slate-100 bg-white p-5 flex flex-col">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-600 text-white">
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="text-xs font-medium uppercase tracking-wide text-slate-400">Step {i + 1}</div>
                                        <h3 className="text-base font-semibold text-slate-900">{s.label}</h3>
                                        <p className="mt-1 text-sm text-slate-500">{s.blurb}</p>
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center justify-between">
                                    <Link href={route(s.listRoute)} className="text-sm text-slate-500 hover:text-orange-600">
                                        <span className="font-semibold text-slate-800">{s.count.toLocaleString()}</span> {s.countLabel} so far →
                                    </Link>
                                </div>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    <a href={route('imports.template', s.entity)}
                                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <Download className="h-4 w-4" /> Download template
                                    </a>
                                    <Link href={route('imports.show', s.entity)}
                                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                        <Upload className="h-4 w-4" /> Upload {s.label}
                                    </Link>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className={`rounded-xl border p-5 ${reconciled ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
                    <div className="flex items-start gap-3">
                        {reconciled ? <CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-600" /> : <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-600" />}
                        <div className="flex-1">
                            <h3 className="text-sm font-semibold text-slate-900">Opening stock in the books — {branch.name}</h3>
                            <p className="mt-1 text-sm text-slate-600">
                                Stock on the shelf is worth <strong><MoneyDisplay amount={opening.stock_value} /></strong>; the Inventory account in the ledger shows <strong><MoneyDisplay amount={opening.gl_inventory} /></strong>.
                                {reconciled
                                    ? ' These agree — your opening stock is fully posted.'
                                    : ' After uploading stock, post the difference so the accounts match the shelf.'}
                            </p>
                            {!reconciled && (
                                <button
                                    onClick={() => router.post(route('admin.health.post-opening-stock'))}
                                    className="mt-3 inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Post opening stock to the books (<MoneyDisplay amount={Math.abs(opening.gap)} />) <ArrowRight className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="flex items-center gap-2 px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-700">
                        <History className="h-4 w-4 text-slate-400" /> Recent uploads
                    </div>
                    {recent.length === 0 ? (
                        <p className="px-4 py-6 text-sm text-slate-400">No uploads yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2">What</th><th className="px-2 py-2">File</th><th className="px-2 py-2">By</th>
                                <th className="px-2 py-2 text-right">Created</th><th className="px-2 py-2 text-right">Updated</th><th className="px-2 py-2 text-right">Skipped</th><th className="px-4 py-2">When</th>
                            </tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {recent.map((r) => (
                                    <tr key={r.id}>
                                        <td className="px-4 py-2"><Link href={route('imports.result', r.id)} className="font-medium text-orange-600 hover:text-orange-700">{r.entity}</Link></td>
                                        <td className="px-2 py-2 text-slate-500 truncate max-w-[14rem]">{r.file_name}</td>
                                        <td className="px-2 py-2 text-slate-500">{r.user ?? '—'}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-emerald-700">{r.created}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-700">{r.updated}</td>
                                        <td className={`px-2 py-2 text-right tabular-nums ${r.errors > 0 ? 'text-red-600 font-medium' : 'text-slate-400'}`}>{r.skipped}{r.errors > 0 && ` (${r.errors} errors)`}</td>
                                        <td className="px-4 py-2 text-slate-400">{r.at}</td>
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
