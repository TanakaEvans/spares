import { Head, Link, router } from '@inertiajs/react';
import { ScanBarcode } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function InvoicesIndex({ invoices, filters }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Tax Invoices"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Tax Invoices' }]}
        >
            <Head title="Tax Invoices" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput
                        id="inv-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => router.get(route('sales.invoices.index'), s ? { search: s } : {}, { preserveState: true, replace: true })}
                        placeholder="Invoice number or customer…"
                        className="w-72"
                    />
                    <Link href={route('sales.pos')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <ScanBarcode className="w-4 h-4" />
                        New Sale
                    </Link>
                </div>
                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Invoice', render: (d) => <span className="font-mono font-semibold text-slate-800">{d.document_number}</span> },
                        { key: 'customer', label: 'Customer', render: (d) => d.customer_id ? <Link href={route('customers.show', d.customer_id)} onClick={(e) => e.stopPropagation()} className="text-slate-700 hover:text-orange-600">{d.customer}</Link> : <span className="text-slate-700">{d.customer}</span> },
                        { key: 'branch', label: 'Branch', render: (d) => <span className="text-slate-500">{d.branch}</span> },
                        { key: 'document_date', label: 'Date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'total_incl', label: 'Total', align: 'right', render: (d) => <MoneyDisplay amount={d.total_incl} /> },
                        { key: 'status', label: 'Status', render: (d) => <StatusBadge status={d.status} /> },
                    ]}
                    rows={invoices.data}
                    pagination={invoices}
                    rowHref={(d) => route('sales.invoices.show', d.id)}
                    emptyTitle="No invoices yet"
                    emptyMessage="Post your first sale at the till."
                    emptyIcon={ScanBarcode}
                />
            </div>
        </ModuleLayout>
    );
}
