import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function SuppliersIndex({ suppliers, filters }) {
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
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput
                        id="suppliers-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => router.get(route('suppliers.index'), s ? { search: s } : {}, { preserveState: true })}
                        placeholder="Search name or number…"
                        className="w-72"
                    />
                    <Link
                        href={route('suppliers.create')}
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        New Supplier
                    </Link>
                </div>

                <DataTable
                    columns={[
                        { key: 'supplier_number', label: 'Number', render: (s) => <span className="font-mono text-slate-500">{s.supplier_number}</span> },
                        { key: 'name', label: 'Supplier', render: (s) => <span className="font-medium text-slate-800">{s.name}</span> },
                        { key: 'type', label: 'Type', render: (s) => <span className="capitalize text-slate-500">{s.type}</span> },
                        { key: 'phone', label: 'Phone', render: (s) => <span className="text-slate-500">{s.phone}</span> },
                        { key: 'lead_time_days', label: 'Lead', align: 'right', render: (s) => <span className="text-slate-500">{s.lead_time_days}d</span> },
                        { key: 'purchase_orders_count', label: 'POs', align: 'right' },
                        { key: 'ap_balance', label: 'AP Balance', align: 'right', render: (s) => <MoneyDisplay amount={s.ap_balance} /> },
                        { key: 'is_active', label: 'Status', render: (s) => <StatusBadge status={s.is_active ? 'active' : 'inactive'} /> },
                    ]}
                    rows={suppliers.data}
                    pagination={suppliers}
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
