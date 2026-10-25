import { Head } from '@inertiajs/react';
import { BadgeCheck, Star } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function ApprovedIndex({ rows, filters }) {
    const filter = useListFilter('suppliers.approved.index', filters);
    return (
        <ModuleLayout navConfig={navConfig} title="Approved Suppliers"
            breadcrumbs={[{ label: 'Suppliers', href: route('modules.show', 'suppliers') }, { label: 'Approved Supplier List' }]}>
            <Head title="Approved Suppliers" />
            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Part number, description or supplier…"
                    searchClass="w-72"
                >
                    <label className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 cursor-pointer">
                        <input type="checkbox" checked={filters.preferred === '1'} onChange={(e) => filter({ preferred: e.target.checked ? '1' : '' })} className="rounded border-slate-300" />
                        Preferred only
                    </label>
                </ListToolbar>
                <DataTable
                    columns={[
                        { key: 'part_number', label: 'Part', render: (r) => <EntityLink type="part" id={r.part_id} label={r.part_number} mono className="font-semibold" /> },
                        { key: 'description', label: 'Description', render: (r) => <span className="text-slate-500">{r.description}</span> },
                        { key: 'supplier', label: 'Supplier', render: (r) => <EntityLink type="supplier" id={r.supplier_id} label={r.supplier} /> },
                        { key: 'supplier_part_number', label: 'Their Code', render: (r) => <span className="font-mono text-slate-500">{r.supplier_part_number ?? '—'}</span> },
                        { key: 'lead_time_days', label: 'Lead (days)', align: 'right', sortable: 'lead_time_days', render: (r) => <span className="tabular-nums text-slate-500">{r.lead_time_days ?? '—'}</span> },
                        { key: 'is_preferred', label: '', sortable: 'is_preferred', render: (r) => (r.is_preferred ? <span className="inline-flex items-center gap-1 text-xs font-semibold text-amber-600"><Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" /> Preferred</span> : null) },
                    ]}
                    rows={rows.data}
                    pagination={rows}
                    sorting={{ filters, onFilter: filter }}
                    emptyTitle="No approved suppliers"
                    emptyMessage="Approve suppliers per part from the reorder report's preferred-supplier control."
                    emptyIcon={BadgeCheck}
                />
            </div>
        </ModuleLayout>
    );
}
