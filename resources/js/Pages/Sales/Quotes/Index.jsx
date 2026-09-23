import { Head, Link, router } from '@inertiajs/react';
import { Plus, FileText } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function QuotesIndex({ quotes, filters }) {
    function filter(params) {
        router.get(route('sales.quotes.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Quotations"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Quotations' }]}
        >
            <Head title="Quotations" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="q-search" value={filters.search ?? ''} onSearch={(s) => filter({ search: s })} placeholder="Quote number or customer…" className="w-72" />
                    <select aria-label="Filter by status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        {['open', 'converted', 'cancelled'].map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    <Link href={route('sales.quotes.create')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Quote
                    </Link>
                </div>
                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Quote', render: (d) => <span className="font-mono font-semibold text-slate-800">{d.document_number}</span> },
                        { key: 'customer', label: 'Customer', render: (d) => <span className="text-slate-700">{d.customer}</span> },
                        { key: 'document_date', label: 'Date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'expiry_date', label: 'Expires', render: (d) => <span className="tabular-nums text-slate-500">{d.expiry_date ?? '—'}</span> },
                        { key: 'total_incl', label: 'Total', align: 'right', render: (d) => <MoneyDisplay amount={d.total_incl} /> },
                        { key: 'status', label: 'Status', render: (d) => <StatusBadge status={d.status} /> },
                    ]}
                    rows={quotes.data}
                    pagination={quotes}
                    rowHref={(d) => route('sales.quotes.show', d.id)}
                    emptyTitle="No quotations"
                    emptyMessage="Quote a customer — convert it to an order in one click."
                    emptyIcon={FileText}
                />
            </div>
        </ModuleLayout>
    );
}
