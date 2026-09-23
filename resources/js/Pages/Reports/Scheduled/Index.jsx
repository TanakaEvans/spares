import { Head, router, useForm } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import StatusBadge from '@/Components/StatusBadge';

export default function ScheduledIndex({ reports, reportKeys }) {
    const { data, setData, post, processing, reset } = useForm({ name: '', report_key: 'sales_summary', frequency: 'monthly', run_time: '08:00', recipients: '', format: 'pdf' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Scheduled Reports" breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Scheduled Reports' }]}>
            <Head title="Scheduled Reports" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {reports.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Clock className="w-8 h-8 mx-auto mb-2 text-slate-300" />No scheduled reports.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Report</th><th className="px-2 py-2">Frequency</th><th className="px-2 py-2">Recipients</th><th className="px-2 py-2">Status</th><th className="px-4 py-2"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {reports.map((r) => (
                                    <tr key={r.id}>
                                        <td className="px-4 py-2"><div className="font-medium text-slate-800">{r.name}</div><div className="text-xs text-slate-400">{reportKeys[r.report_key] ?? r.report_key} · {r.format.toUpperCase()}</div></td>
                                        <td className="px-2 py-2 capitalize text-slate-500">{r.frequency} @ {r.run_time}</td>
                                        <td className="px-2 py-2 text-slate-500 truncate max-w-[12rem]">{r.recipients ?? '—'}</td>
                                        <td className="px-2 py-2"><StatusBadge status={r.is_active ? 'active' : 'inactive'} /></td>
                                        <td className="px-4 py-2 text-right"><button onClick={() => router.post(route('reports.scheduled.toggle', r.id), {}, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">{r.is_active ? 'Pause' : 'Resume'}</button></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                    <p className="px-4 py-2 text-xs text-slate-400 border-t border-slate-50">Delivery runs once the background scheduler + email are enabled in operations.</p>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('reports.scheduled.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New schedule</h2>
                    <input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Name" className={input} />
                    <select value={data.report_key} onChange={(e) => setData('report_key', e.target.value)} className={input}>{Object.entries(reportKeys).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select>
                    <div className="grid grid-cols-2 gap-2">
                        <select value={data.frequency} onChange={(e) => setData('frequency', e.target.value)} className={input}><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select>
                        <input value={data.run_time} onChange={(e) => setData('run_time', e.target.value)} placeholder="08:00" className={input} />
                    </div>
                    <input value={data.recipients} onChange={(e) => setData('recipients', e.target.value)} placeholder="Recipients (emails)" className={input} />
                    <select value={data.format} onChange={(e) => setData('format', e.target.value)} className={input}><option value="pdf">PDF</option><option value="excel">Excel</option></select>
                    <button type="submit" disabled={processing || !data.name} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Schedule</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
