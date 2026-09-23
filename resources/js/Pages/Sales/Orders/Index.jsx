import { Head, router } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function OrdersIndex({ orders, filters }) {
    function filter(params) {
        router.get(route('sales.orders.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Sales Orders"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Sales Orders' }]}
        >
            <Head title="Sales Orders" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="so-search" value={filters.search ?? ''} onSearch={(s) => filter({ search: s })} placeholder="Order number or customer…" className="w-72" />
                    <select aria-label="Filter by status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        {['confirmed', 'invoiced', 'cancelled'].map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                </div>
                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Order', render: (d) => <span className="font-mono font-semibold text-slate-800">{d.document_number}</span> },
                        { key: 'customer', label: 'Customer', render: (d) => <span className="text-slate-700">{d.customer}</span> },
                        { key: 'document_date', label: 'Date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'total_incl', label: 'Total', align: 'right', render: (d) => <MoneyDisplay amount={d.total_incl} /> },
                        { key: 'status', label: 'Status', render: (d) => <StatusBadge status={d.status} /> },
                    ]}
                    rows={orders.data}
                    pagination={orders}
                    rowHref={(d) => route('sales.orders.show', d.id)}
                    emptyTitle="No sales orders"
                    emptyMessage="Orders are created by converting a quotation."
                    emptyIcon={ClipboardList}
                />
            </div>
        </ModuleLayout>
    );
}
