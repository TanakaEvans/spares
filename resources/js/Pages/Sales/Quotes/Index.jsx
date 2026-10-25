import { Head, Link, router } from '@inertiajs/react';
import { Plus, FileText, Clock, CircleCheck } from 'lucide-react';
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

const STATUSES = [
    { key: '', label: 'All' },
    { key: 'open', label: 'Open' },
    { key: 'converted', label: 'Converted' },
    { key: 'expired', label: 'Expired' },
    { key: 'cancelled', label: 'Cancelled' },
];

export default function QuotesIndex({ quotes, stats, filters }) {
    const filter = useListFilter('sales.quotes.index', filters);

    const active = filters.status ?? '';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Quotations"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Quotations' }]}
        >
            <Head title="Quotations" />
            <div className="space-y-4">
                <StatChips items={[
                    { label: 'Open quotes', value: `${stats.open} · R${fmt(stats.open_value)}`, icon: FileText },
                    { label: 'Expiring ≤ 5 days', value: stats.expiring, tone: stats.expiring > 0 ? 'amber' : 'slate', icon: Clock, hint: stats.expiring > 0 ? 'Follow up before they lapse' : undefined },
                    { label: 'Converted', value: quotes.data.filter((q) => q.status === 'converted').length, icon: CircleCheck, tone: 'emerald', hint: 'on this page' },
                ]} />

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Quote number or customer…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="sales.quotes.export"
                >
                    <div className="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-1" role="tablist" aria-label="Status filter">
                        {STATUSES.map((s) => (
                            <button key={s.key} role="tab" aria-selected={active === s.key} onClick={() => filter({ status: s.key })}
                                className={`rounded-md px-3 py-1.5 text-xs font-semibold capitalize transition ${active === s.key ? 'bg-orange-600 text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
                                {s.label}
                            </button>
                        ))}
                    </div>
                    <Link href={route('sales.quotes.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Quote
                    </Link>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Quote', sortable: 'document_number', render: (d) => <EntityLink type="quote" id={d.id} label={d.document_number} mono className="font-semibold" /> },
                        { key: 'customer', label: 'Customer', render: (d) => <EntityLink type="customer" id={d.customer_id} label={d.customer} /> },
                        { key: 'document_date', label: 'Date', sortable: 'document_date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        {
                            key: 'expiry_date', label: 'Expires', sortable: 'expiry_date', render: (d) => {
                                if (d.status !== 'open') return <span className="tabular-nums text-slate-400">{d.expiry_date ?? '—'}</span>;
                                const urgent = d.days_left != null && d.days_left <= 5;
                                return (
                                    <span className={`tabular-nums ${urgent ? 'font-semibold text-red-600' : 'text-slate-500'}`}>
                                        {d.expiry_date ?? '—'}
                                        {d.days_left != null && <span className="ml-1 text-xs">{d.days_left < 0 ? 'lapsed' : `(${d.days_left}d)`}</span>}
                                    </span>
                                );
                            },
                        },
                        { key: 'total_incl', label: 'Total', align: 'right', sortable: 'total_incl', render: (d) => <MoneyDisplay amount={d.total_incl} /> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (d) => <StatusBadge status={d.status} /> },
                    ]}
                    rows={quotes.data}
                    pagination={quotes}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(d) => route('sales.quotes.show', d.id)}
                    emptyTitle="No quotations"
                    emptyMessage="Quote a customer — convert it to an order in one click."
                    emptyIcon={FileText}
                />
            </div>
        </ModuleLayout>
    );
}
