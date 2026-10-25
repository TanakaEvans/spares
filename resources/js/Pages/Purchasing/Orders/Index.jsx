import { Head, Link } from '@inertiajs/react';
import { Plus, TriangleAlert } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import StatChips from '@/Components/StatChips';
import EntityLink from '@/Components/EntityLink';
import { fmt } from '@/Components/Pos/posMath';
import { useListFilter } from '@/lib/listFilters';

const PILLS = [
    { key: '', label: 'All' },
    { key: 'draft', label: 'Draft' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'confirmed', label: 'Confirmed' },
    { key: 'partial', label: 'Part received' },
    { key: 'received', label: 'Received' },
    { key: 'closed', label: 'Closed' },
    { key: 'cancelled', label: 'Cancelled' },
];

export default function OrdersIndex({ orders, stats, filters }) {
    const filter = useListFilter('purchasing.orders.index', filters);

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
                <StatChips
                    items={[
                        { label: 'Open orders', value: stats.open },
                        { label: 'Open value', value: `R${fmt(stats.open_value)}`, tone: stats.open_value > 0 ? 'orange' : 'slate' },
                        { label: 'Overdue', value: stats.overdue, tone: stats.overdue > 0 ? 'red' : 'emerald' },
                        { label: 'Drafts', value: stats.drafts, tone: stats.drafts > 0 ? 'amber' : 'slate' },
                    ]}
                />

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="PO number or supplier…"
                    searchClass="w-64"
                    dateRange
                    exportRoute="purchasing.orders.export"
                >
                    <div className="flex rounded-lg border border-slate-200 bg-white p-0.5" role="tablist">
                        {PILLS.map((p) => (
                            <button
                                key={p.key}
                                role="tab"
                                aria-selected={(filters.status ?? '') === p.key}
                                onClick={() => filter({ status: p.key })}
                                className={`px-3 py-1.5 text-xs font-medium rounded-md capitalize ${
                                    (filters.status ?? '') === p.key ? 'bg-orange-600 text-white' : 'text-slate-500 hover:bg-slate-50'
                                }`}
                            >
                                {p.label}
                            </button>
                        ))}
                    </div>
                    <Link
                        href={route('purchasing.orders.create')}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        New Order
                    </Link>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'po_number', label: 'PO No', sortable: 'po_number', render: (po) => <EntityLink type="purchase_order" id={po.id} label={po.po_number} mono className="font-semibold" /> },
                        { key: 'supplier', label: 'Supplier', render: (po) => <EntityLink type="supplier" id={po.supplier_id} label={po.supplier} /> },
                        { key: 'branch', label: 'Branch', render: (po) => <EntityLink type="branch" id={po.branch_id} label={po.branch} muted /> },
                        { key: 'order_date', label: 'Ordered', sortable: 'order_date', render: (po) => <span className="tabular-nums text-slate-500">{po.order_date}</span> },
                        {
                            key: 'expected_date', label: 'Expected', sortable: 'expected_date',
                            render: (po) => po.expected_date ? (
                                <span className={`inline-flex items-center gap-1 tabular-nums ${po.is_overdue ? 'font-semibold text-red-600' : 'text-slate-500'}`}>
                                    {po.is_overdue && <TriangleAlert className="w-3.5 h-3.5" />}
                                    {po.expected_date}
                                </span>
                            ) : <span className="text-slate-300">—</span>,
                        },
                        { key: 'total', label: 'Total', align: 'right', sortable: 'total', render: (po) => <MoneyDisplay amount={po.total} /> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (po) => <StatusBadge status={po.status} /> },
                    ]}
                    rows={orders.data}
                    pagination={orders}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(po) => route('purchasing.orders.show', po.id)}
                    emptyTitle="No purchase orders"
                    emptyMessage="Create the first order — or let the reorder report draft them for you."
                />
            </div>
        </ModuleLayout>
    );
}
