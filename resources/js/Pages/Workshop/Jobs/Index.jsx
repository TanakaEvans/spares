import { Head, Link, router } from '@inertiajs/react';
import { Plus, ClipboardList } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

const STATUSES = ['open', 'allocated', 'in_progress', 'awaiting_parts', 'awaiting_customer', 'on_hold', 'quality_check', 'completed', 'invoiced', 'closed', 'cancelled'];

export default function JobsIndex({ jobs, filters }) {
    function filter(params) {
        router.get(route('workshop.jobs.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Job Cards"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Job Cards' }]}
        >
            <Head title="Job Cards" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="job-search" value={filters.search ?? ''} onSearch={(s) => filter({ search: s })} placeholder="Job number, plate or customer…" className="w-72" />
                    <select aria-label="Status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        {STATUSES.map((s) => <option key={s} value={s}>{s.replace(/_/g, ' ')}</option>)}
                    </select>
                    <Link href={route('workshop.jobs.create')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Job
                    </Link>
                </div>
                <DataTable
                    columns={[
                        { key: 'job_number', label: 'Job', render: (j) => <span className="font-mono font-semibold text-slate-800">{j.job_number}</span> },
                        { key: 'customer', label: 'Customer', render: (j) => j.customer_id ? <Link href={route('customers.show', j.customer_id)} onClick={(e) => e.stopPropagation()} className="text-slate-700 hover:text-orange-600">{j.customer}</Link> : <span className="text-slate-400">—</span> },
                        { key: 'vehicle', label: 'Vehicle', render: (j) => j.vehicle_id ? <Link href={route('workshop.vehicles.show', j.vehicle_id)} onClick={(e) => e.stopPropagation()} className="font-mono text-slate-500 hover:text-orange-600">{j.vehicle}</Link> : <span className="font-mono text-slate-400">—</span> },
                        { key: 'technician', label: 'Technician', render: (j) => <span className="text-slate-500">{j.technician ?? '—'}</span> },
                        { key: 'created_at', label: 'Opened', render: (j) => <span className="tabular-nums text-slate-500">{j.created_at}</span> },
                        { key: 'status', label: 'Status', render: (j) => <StatusBadge status={j.status} /> },
                    ]}
                    rows={jobs.data}
                    pagination={jobs}
                    rowHref={(j) => route('workshop.jobs.show', j.id)}
                    emptyTitle="No job cards"
                    emptyMessage="Open a job to start booking labour and parts."
                    emptyIcon={ClipboardList}
                />
            </div>
        </ModuleLayout>
    );
}
