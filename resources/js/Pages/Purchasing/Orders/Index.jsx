import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function OrdersIndex({ orders, filters }) {
    function filter(params) {
        router.get(route('purchasing.orders.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Purchase Orders"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Purchase Orders' },
            ]}
        >
            <Head title="Purchase Orders" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput
                        id="po-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => filter({ search: s })}
                        placeholder="PO number or supplier…"
                        className="w-72"
                    />
                    <select
                        aria-label="Filter by status"
                        value={filters.status ?? ''}
                        onChange={(e) => filter({ status: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All statuses</option>
                        {['draft', 'submitted', 'confirmed', 'partial', 'received', 'closed', 'cancelled'].map((s) => (
                            <option key={s} value={s}>{s}</option>
                        ))}
                    </select>
                    <Link
                        href={route('purchasing.orders.create')}
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        New Order
                    </Link>
                </div>

                <DataTable
                    columns={[
                        { key: 'po_number', label: 'PO No', render: (po) => <span className="font-mono font-semibold text-slate-800">{po.po_number}</span> },
                        { key: 'supplier', label: 'Supplier', render: (po) => <span className="text-slate-700">{po.supplier}</span> },
                        { key: 'branch', label: 'Branch', render: (po) => <span className="text-slate-500">{po.branch}</span> },
                        { key: 'order_date', label: 'Ordered', render: (po) => <span className="tabular-nums text-slate-500">{po.order_date}</span> },
                        { key: 'expected_date', label: 'Expected', render: (po) => <span className="tabular-nums text-slate-500">{po.expected_date ?? '—'}</span> },
                        { key: 'total', label: 'Total', align: 'right', render: (po) => <MoneyDisplay amount={po.total} /> },
                        { key: 'status', label: 'Status', render: (po) => <StatusBadge status={po.status} /> },
                    ]}
                    rows={orders.data}
                    pagination={orders}
                    rowHref={(po) => route('purchasing.orders.show', po.id)}
                    emptyTitle="No purchase orders"
                    emptyMessage="Create the first order — or let the reorder report draft them for you."
                />
            </div>
        </ModuleLayout>
    );
}
