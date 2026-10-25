import { Head, Link, router } from '@inertiajs/react';
import { FileSpreadsheet } from 'lucide-react';
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
    { key: 'posted', label: 'Posted' },
    { key: 'matched', label: 'Matched' },
    { key: 'disputed', label: 'Disputed' },
    { key: 'draft', label: 'Draft' },
];

export default function InvoicesIndex({ invoices, uninvoicedGrns, stats, filters }) {
    const filter = useListFilter('purchasing.invoices.index', filters);
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Supplier Invoices"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Supplier Invoices' },
            ]}
        >
            <Head title="Supplier Invoices" />

            <div className="space-y-4">
                <StatChips
                    items={[
                        { label: 'GRNs awaiting invoice', value: stats.awaiting_capture, tone: stats.awaiting_capture > 0 ? 'amber' : 'emerald' },
                        { label: 'Disputed', value: stats.disputed, tone: stats.disputed > 0 ? 'red' : 'emerald' },
                        { label: 'Posted this month', value: `R${fmt(stats.posted_month)}` },
                    ]}
                />

                {uninvoicedGrns.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">
                            GRNs awaiting invoice ({uninvoicedGrns.length})
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {uninvoicedGrns.map((g) => (
                                <Link
                                    key={g.id}
                                    href={route('purchasing.invoices.create', g.id)}
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:border-orange-400 hover:bg-orange-50/50"
                                >
                                    <FileSpreadsheet className="w-4 h-4 text-slate-400" />
                                    <span className="font-mono font-semibold text-slate-700">{g.grn_number}</span>
                                    <span className="text-slate-400">{g.supplier}</span>
                                    <MoneyDisplay amount={g.value} className="text-slate-600" />
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Invoice ref or supplier…"
                    searchClass="w-64"
                    dateRange
                    exportRoute="purchasing.invoices.export"
                >
                    <div className="flex rounded-lg border border-slate-200 bg-white p-0.5" role="tablist">
                        {PILLS.map((p) => (
                            <button
                                key={p.key}
                                role="tab"
                                aria-selected={(filters.status ?? '') === p.key}
                                onClick={() => filter({ status: p.key })}
                                className={`px-3 py-1.5 text-xs font-medium rounded-md ${
                                    (filters.status ?? '') === p.key ? 'bg-orange-600 text-white' : 'text-slate-500 hover:bg-slate-50'
                                }`}
                            >
                                {p.label}
                            </button>
                        ))}
                    </div>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'supplier_ref', label: 'Supplier Ref', sortable: 'supplier_ref', render: (i) => <EntityLink type="supplier_invoice" id={i.id} label={i.supplier_ref} mono className="font-semibold" /> },
                        { key: 'supplier', label: 'Supplier', render: (i) => <EntityLink type="supplier" id={i.supplier_id} label={i.supplier} /> },
                        { key: 'grn_number', label: 'GRN', render: (i) => <EntityLink type="grn" id={i.grn_id} label={i.grn_number} mono muted /> },
                        { key: 'invoice_date', label: 'Date', sortable: 'invoice_date', render: (i) => <span className="tabular-nums text-slate-500">{i.invoice_date}</span> },
                        { key: 'due_date', label: 'Due', sortable: 'due_date', render: (i) => <span className="tabular-nums text-slate-500">{i.due_date}</span> },
                        { key: 'total', label: 'Total', align: 'right', sortable: 'total', render: (i) => <MoneyDisplay amount={i.total} /> },
                        {
                            key: 'status', label: 'Status', sortable: 'status',
                            render: (i) => (
                                <div>
                                    <StatusBadge status={i.status} />
                                    {i.status === 'disputed' && (
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                router.post(route('purchasing.invoices.post', i.id), {}, { preserveScroll: true });
                                            }}
                                            className="block text-[11px] text-orange-700 hover:underline mt-0.5"
                                            title={i.dispute_reason}
                                        >
                                            Resolve & post
                                        </button>
                                    )}
                                </div>
                            ),
                        },
                    ]}
                    rows={invoices.data}
                    pagination={invoices}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(i) => route('purchasing.invoices.show', i.id)}
                    emptyTitle="No supplier invoices"
                    emptyMessage="Capture an invoice against a posted GRN — 3-way matching runs automatically."
                />
            </div>
        </ModuleLayout>
    );
}
