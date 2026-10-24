import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, PackageCheck, Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import { fmt } from '@/Components/Pos/posMath';

const STATUSES = [
    { key: '', label: 'All' },
    { key: 'confirmed', label: 'Awaiting fulfilment' },
    { key: 'invoiced', label: 'Invoiced' },
    { key: 'cancelled', label: 'Cancelled' },
];

export default function OrdersIndex({ orders, stats, filters }) {
    function filter(params) {
        router.get(route('sales.orders.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

    const active = filters.status ?? '';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Sales Orders"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Sales Orders' }]}
        >
            <Head title="Sales Orders" />
            <div className="space-y-4">
                <StatChips items={[
                    { label: 'Awaiting fulfilment', value: stats.awaiting, tone: stats.awaiting > 0 ? 'orange' : 'slate', icon: ClipboardList, hint: 'stock reserved' },
                    { label: 'Reserved value', value: `R${fmt(stats.awaiting_value)}` },
                    { label: 'Invoiced today', value: stats.invoiced_today, tone: 'emerald', icon: PackageCheck },
                ]} />

                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="so-search" value={filters.search ?? ''} onSearch={(s) => filter({ search: s })} placeholder="Order number or customer…" className="w-72" />
                    <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1" role="tablist" aria-label="Status filter">
                        {STATUSES.map((s) => (
                            <button key={s.key} role="tab" aria-selected={active === s.key} onClick={() => filter({ status: s.key })}
                                className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${active === s.key ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
                                {s.label}
                            </button>
                        ))}
                    </div>
                    <Link href={route('sales.orders.create')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Order
                    </Link>
                </div>

                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Order', render: (d) => <EntityLink type="order" id={d.id} label={d.document_number} mono className="font-semibold" /> },
                        { key: 'customer', label: 'Customer', render: (d) => <EntityLink type="customer" id={d.customer_id} label={d.customer} /> },
                        { key: 'document_date', label: 'Date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'total_incl', label: 'Total', align: 'right', render: (d) => <MoneyDisplay amount={d.total_incl} /> },
                        { key: 'status', label: 'Status', render: (d) => <StatusBadge status={d.status} label={d.status === 'confirmed' ? 'awaiting fulfilment' : undefined} /> },
                    ]}
                    rows={orders.data}
                    pagination={orders}
                    rowHref={(d) => route('sales.orders.show', d.id)}
                    emptyTitle="No sales orders"
                    emptyMessage="Take a phoned-in order here, or convert a quotation."
                    emptyIcon={ClipboardList}
                />
            </div>
        </ModuleLayout>
    );
}
