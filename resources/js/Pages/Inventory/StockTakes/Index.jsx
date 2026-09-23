import { Head, Link, router } from '@inertiajs/react';
import { Plus, ClipboardCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import StatusBadge from '@/Components/StatusBadge';

export default function StockTakesIndex({ takes, filters }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Stock Takes"
            breadcrumbs={[{ label: 'Inventory', href: route('modules.show', 'inventory') }, { label: 'Stock Takes' }]}
        >
            <Head title="Stock Takes" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <select aria-label="Filter by status" value={filters.status ?? ''}
                        onChange={(e) => router.get(route('inventory.stock-takes.index'), e.target.value ? { status: e.target.value } : {}, { preserveState: true, replace: true })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        {['counting', 'review', 'posted', 'cancelled'].map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <Link href={route('inventory.stock-takes.create')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Stock Take
                    </Link>
                </div>
                <DataTable
                    columns={[
                        { key: 'take_number', label: 'Number', render: (t) => <span className="font-mono font-semibold text-slate-800">{t.take_number}</span> },
                        { key: 'branch', label: 'Branch', render: (t) => <span className="text-slate-700">{t.branch}</span> },
                        { key: 'type', label: 'Type', render: (t) => <span className="capitalize text-slate-500">{t.type}</span> },
                        { key: 'lines_count', label: 'Lines', align: 'right', render: (t) => <span className="tabular-nums text-slate-500">{t.lines_count}</span> },
                        { key: 'started_by', label: 'Started by', render: (t) => <span className="text-slate-500">{t.started_by ?? '—'}</span> },
                        { key: 'created_at', label: 'Date', render: (t) => <span className="tabular-nums text-slate-500">{t.created_at}</span> },
                        { key: 'status', label: 'Status', render: (t) => <StatusBadge status={t.status} /> },
                    ]}
                    rows={takes.data}
                    pagination={takes}
                    rowHref={(t) => route('inventory.stock-takes.show', t.id)}
                    emptyTitle="No stock takes"
                    emptyMessage="Start a full or spot count to reconcile physical stock."
                    emptyIcon={ClipboardCheck}
                />
            </div>
        </ModuleLayout>
    );
}
