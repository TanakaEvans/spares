import { Head, Link, router } from '@inertiajs/react';
import { BadgeCheck, Star } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';

export default function ApprovedIndex({ rows, filters }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Approved Suppliers"
            breadcrumbs={[{ label: 'Suppliers', href: route('modules.show', 'suppliers') }, { label: 'Approved Supplier List' }]}>
            <Head title="Approved Suppliers" />
            <div className="space-y-4">
                <SearchInput id="as-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('suppliers.approved.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Part number or description…" className="w-72" />
                <DataTable
                    columns={[
                        { key: 'part_number', label: 'Part', render: (r) => <Link href={route('inventory.parts.show', r.part_id)} onClick={(e) => e.stopPropagation()} className="font-mono font-semibold text-slate-800 hover:text-orange-600">{r.part_number}</Link> },
                        { key: 'description', label: 'Description', render: (r) => <span className="text-slate-500">{r.description}</span> },
                        { key: 'supplier', label: 'Supplier', render: (r) => <Link href={route('suppliers.show', r.supplier_id)} onClick={(e) => e.stopPropagation()} className="text-slate-700 hover:text-orange-600">{r.supplier}</Link> },
                        { key: 'lead_time_days', label: 'Lead (days)', align: 'right', render: (r) => <span className="tabular-nums text-slate-500">{r.lead_time_days ?? '—'}</span> },
                        { key: 'is_preferred', label: '', render: (r) => (r.is_preferred ? <span className="inline-flex items-center gap-1 text-xs font-semibold text-amber-600"><Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400" /> Preferred</span> : null) },
                    ]}
                    rows={rows.data}
                    pagination={rows}
                    emptyTitle="No approved suppliers"
                    emptyMessage="Approve suppliers per part from the reorder report's preferred-supplier control."
                    emptyIcon={BadgeCheck}
                />
            </div>
        </ModuleLayout>
    );
}
