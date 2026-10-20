import { Head, Link, router } from '@inertiajs/react';
import { Plus, ClipboardList } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';

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
                        { key: 'job_number', label: 'Job', render: (j) => <EntityLink type="job" id={j.id} label={j.job_number} mono className="font-semibold" /> },
                        { key: 'customer', label: 'Customer', render: (j) => <EntityLink type="customer" id={j.customer_id} label={j.customer} /> },
                        { key: 'vehicle', label: 'Vehicle', render: (j) => <EntityLink type="vehicle" id={j.vehicle_id} label={j.vehicle} mono muted /> },
                        { key: 'technician', label: 'Technician', render: (j) => <EntityLink type="technician" id={j.technician_id} label={j.technician} muted /> },
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
