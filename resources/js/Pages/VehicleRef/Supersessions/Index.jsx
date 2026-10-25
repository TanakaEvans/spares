import { Head } from '@inertiajs/react';
import { GitBranch, ArrowRight } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';

export default function SupersessionsIndex({ rows, filters }) {
    const filter = useListFilter('vehicle-ref.supersessions.index', filters);
    return (
        <ModuleLayout navConfig={navConfig} title="Supersessions"
            breadcrumbs={[{ label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') }, { label: 'Supersession Management' }]}>
            <Head title="Supersessions" />
            <div className="space-y-4">
                <p className="text-sm text-slate-500">When a manufacturer replaces a part number, record it here — searches for the old number resolve to the replacement automatically.</p>
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Old or new part number…"
                    searchClass="w-72"
                >
                    <select aria-label="Status" value={filters.active ?? ''} onChange={(e) => filter({ active: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </ListToolbar>
                <DataTable
                    columns={[
                        { key: 'old', label: 'Superseded (old)', render: (r) => <EntityLink type="part" id={r.old_part_id} label={r.old_number} mono muted className="font-semibold line-through" title={r.old_description} /> },
                        { key: 'arrow', label: '', render: () => <ArrowRight className="w-4 h-4 text-slate-300" /> },
                        { key: 'new', label: 'Replaced by (new)', render: (r) => <EntityLink type="part" id={r.new_part_id} label={r.new_number} mono className="font-semibold" title={r.new_description} /> },
                        { key: 'reason', label: 'Reason', sortable: 'reason', render: (r) => <span className="text-slate-500">{r.reason ?? '—'}</span> },
                        { key: 'is_active', label: 'Status', sortable: 'is_active', render: (r) => <StatusBadge status={r.is_active ? 'active' : 'inactive'} /> },
                    ]}
                    rows={rows.data}
                    pagination={rows}
                    sorting={{ filters, onFilter: filter }}
                    emptyTitle="No supersessions"
                    emptyMessage="Record a replaced part number to auto-redirect old-number searches."
                    emptyIcon={GitBranch}
                />
            </div>
        </ModuleLayout>
    );
}
