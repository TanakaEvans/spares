import { Head, Link } from '@inertiajs/react';
import { ScanBarcode, Receipt, BookUser } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import { fmt } from '@/Components/Pos/posMath';
import { useListFilter } from '@/lib/listFilters';

export default function InvoicesIndex({ invoices, stats, filters }) {
    const filter = useListFilter('sales.invoices.index', filters);
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Tax Invoices"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Tax Invoices' }]}
        >
            <Head title="Tax Invoices" />
            <div className="space-y-4">
                <StatChips items={[
                    { label: 'Today', value: `${stats.today_count} sale${stats.today_count === 1 ? '' : 's'}`, icon: Receipt },
                    { label: "Today's takings", value: `R${fmt(stats.today_total)}`, tone: 'emerald' },
                    { label: 'On account', value: stats.on_account, tone: stats.on_account > 0 ? 'amber' : 'slate', icon: BookUser, hint: 'invoices awaiting payment' },
                ]} />

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Invoice number or customer…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="sales.invoices.export"
                >
                    <Link href={route('sales.pos')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <ScanBarcode className="w-4 h-4" />
                        New Sale
                    </Link>
                </ListToolbar>
                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Invoice', sortable: 'document_number', render: (d) => <EntityLink type="invoice" id={d.id} label={d.document_number} mono className="font-semibold" /> },
                        { key: 'customer', label: 'Customer', render: (d) => <EntityLink type="customer" id={d.customer_id} label={d.customer} /> },
                        { key: 'branch', label: 'Branch', render: (d) => <span className="text-slate-500">{d.branch}</span> },
                        { key: 'document_date', label: 'Date', sortable: 'document_date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'total_incl', label: 'Total', align: 'right', sortable: 'total_incl', render: (d) => <MoneyDisplay amount={d.total_incl} /> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (d) => (
                            <span className="inline-flex items-center gap-1.5">
                                <StatusBadge status={d.status} />
                                {d.on_account && <StatusBadge status="on_hold" label="on account" />}
                            </span>
                        ) },
                    ]}
                    rows={invoices.data}
                    pagination={invoices}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(d) => route('sales.invoices.show', d.id)}
                    emptyTitle="No invoices yet"
                    emptyMessage="Post your first sale at the till."
                    emptyIcon={ScanBarcode}
                />
            </div>
        </ModuleLayout>
    );
}
