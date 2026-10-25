import { Head, Link } from '@inertiajs/react';
import { Plus, ClipboardCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';

export default function StockTakesIndex({ takes, filters = {} }) {
    const filter = useListFilter('inventory.stock-takes.index', filters);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Stock Takes"
            breadcrumbs={[{ label: 'Inventory', href: route('modules.show', 'inventory') }, { label: 'Stock Takes' }]}
        >
            <Head title="Stock Takes" />
            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Take number or notes…"
                    searchClass="w-64"
                    dateRange
                    exportRoute="inventory.stock-takes.export"
                >
                    <select aria-label="Filter by status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        {['counting', 'review', 'posted', 'cancelled'].map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <select aria-label="Filter by type" value={filters.type ?? ''} onChange={(e) => filter({ type: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Full & spot</option>
                        <option value="full">Full</option>
                        <option value="spot">Spot</option>
                    </select>
                    <Link href={route('inventory.stock-takes.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Stock Take
                    </Link>
                </ListToolbar>
                <DataTable
                    columns={[
                        { key: 'take_number', label: 'Number', sortable: 'take_number', render: (t) => <EntityLink type="stock_take" id={t.id} label={t.take_number} mono className="font-semibold" /> },
                        { key: 'branch', label: 'Branch', render: (t) => <EntityLink type="branch" id={t.branch_id} label={t.branch} /> },
                        { key: 'type', label: 'Type', sortable: 'type', render: (t) => <span className="capitalize text-slate-500">{t.type}</span> },
                        { key: 'lines_count', label: 'Lines', align: 'right', render: (t) => <span className="tabular-nums text-slate-500">{t.lines_count}</span> },
                        { key: 'started_by', label: 'Started by', render: (t) => <EntityLink type="user" id={t.started_by_id} label={t.started_by ?? '—'} muted /> },
                        { key: 'created_at', label: 'Date', sortable: 'created_at', render: (t) => <span className="tabular-nums text-slate-500">{t.created_at}</span> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (t) => <StatusBadge status={t.status} /> },
                    ]}
                    rows={takes.data}
                    pagination={takes}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(t) => route('inventory.stock-takes.show', t.id)}
                    emptyTitle="No stock takes"
                    emptyMessage="Start a full or spot count to reconcile physical stock."
                    emptyIcon={ClipboardCheck}
                />
            </div>
        </ModuleLayout>
    );
}
