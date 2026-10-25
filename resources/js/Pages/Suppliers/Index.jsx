import { Head, Link } from '@inertiajs/react';
import { Plus, Upload } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function SuppliersIndex({ suppliers, filters }) {
    const filter = useListFilter('suppliers.index', filters);
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Suppliers"
            breadcrumbs={[
                { label: 'Suppliers', href: route('modules.show', 'suppliers') },
                { label: 'All Suppliers' },
            ]}
        >
            <Head title="Suppliers" />

            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Search name, number or phone…"
                    searchClass="w-72"
                    exportRoute="suppliers.export"
                >
                    <Link
                        href={route('imports.show', 'suppliers')}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        <Upload className="w-4 h-4" />
                        Import CSV
                    </Link>
                    <Link
                        href={route('suppliers.create')}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        New Supplier
                    </Link>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'supplier_number', label: 'Number', sortable: 'supplier_number', render: (s) => <EntityLink type="supplier" id={s.id} label={s.supplier_number} mono muted /> },
                        { key: 'name', label: 'Supplier', sortable: 'name', render: (s) => <EntityLink type="supplier" id={s.id} label={s.name} className="font-medium" /> },
                        { key: 'type', label: 'Type', sortable: 'type', render: (s) => <span className="capitalize text-slate-500">{s.type}</span> },
                        { key: 'phone', label: 'Phone', render: (s) => <span className="text-slate-500">{s.phone}</span> },
                        { key: 'lead_time_days', label: 'Lead', align: 'right', sortable: 'lead_time_days', render: (s) => <span className="text-slate-500">{s.lead_time_days}d</span> },
                        { key: 'purchase_orders_count', label: 'Orders', align: 'right' },
                        { key: 'ap_balance', label: 'AP Balance', align: 'right', render: (s) => <MoneyDisplay amount={s.ap_balance} /> },
                        { key: 'is_active', label: 'Status', render: (s) => <StatusBadge status={s.is_active ? 'active' : 'inactive'} /> },
                    ]}
                    rows={suppliers.data}
                    pagination={suppliers}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(s) => route('suppliers.show', s.id)}
                    emptyTitle="No suppliers yet"
                    emptyMessage="Add your first supplier to start ordering stock."
                    emptyAction={
                        <Link href={route('suppliers.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            <Plus className="w-4 h-4" />
                            New Supplier
                        </Link>
                    }
                />
            </div>
        </ModuleLayout>
    );
}
