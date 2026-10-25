import { Head, router, useForm } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';

export default function ScheduledIndex({ reports, reportKeys, filters = {} }) {
    const { data, setData, post, processing, reset } = useForm({ name: '', report_key: 'sales_summary', frequency: 'monthly', run_time: '08:00', recipients: '', format: 'pdf' });
    const filter = useListFilter('reports.scheduled.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Scheduled Reports" breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Scheduled Reports' }]}>
            <Head title="Scheduled Reports" />
            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Report name or recipients…"
                searchClass="w-64"
            />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl mt-4">
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Report', sortable: 'name', render: (r) => (
                                <div><div className="font-medium text-slate-800">{r.name}</div><div className="text-xs text-slate-400">{reportKeys[r.report_key] ?? r.report_key} · {r.format.toUpperCase()}</div></div>
                            ) },
                            { key: 'frequency', label: 'Frequency', sortable: 'frequency', render: (r) => <span className="capitalize text-slate-500">{r.frequency} @ {r.run_time}</span> },
                            { key: 'recipients', label: 'Recipients', render: (r) => <span className="text-slate-500 truncate inline-block max-w-[12rem]">{r.recipients ?? '—'}</span> },
                            { key: 'status', label: 'Status', sortable: 'is_active', render: (r) => <StatusBadge status={r.is_active ? 'active' : 'inactive'} /> },
                            { key: 'actions', label: '', align: 'right', render: (r) => (
                                <button onClick={(e) => { e.stopPropagation(); router.post(route('reports.scheduled.toggle', r.id), {}, { preserveScroll: true }); }} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">
                                    {r.is_active ? 'Pause' : 'Resume'}
                                </button>
                            ) },
                        ]}
                        rows={reports.data}
                        pagination={reports}
                        sorting={{ filters, onFilter: filter }}
                        emptyTitle="No scheduled reports"
                        emptyIcon={Clock}
                    />
                    <p className="mt-2 text-xs text-slate-400">Delivery runs once the background scheduler + email are enabled in operations.</p>
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
