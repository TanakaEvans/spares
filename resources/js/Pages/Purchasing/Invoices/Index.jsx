import { Head, Link, router } from '@inertiajs/react';
import { FileSpreadsheet } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

export default function InvoicesIndex({ invoices, uninvoicedGrns, filters }) {
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

            <div className="space-y-6">
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

                <div className="flex gap-3">
                    <select
                        aria-label="Filter by status"
                        value={filters.status ?? ''}
                        onChange={(e) => router.get(route('purchasing.invoices.index'), e.target.value ? { status: e.target.value } : {}, { preserveState: true })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All statuses</option>
                        {['posted', 'disputed', 'matched', 'draft'].map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                </div>

                <DataTable
                    columns={[
                        { key: 'supplier_ref', label: 'Supplier Ref', render: (i) => <span className="font-mono font-semibold text-slate-800">{i.supplier_ref}</span> },
                        { key: 'supplier', label: 'Supplier', render: (i) => <span className="text-slate-700">{i.supplier}</span> },
                        { key: 'grn_number', label: 'GRN', render: (i) => <span className="font-mono text-slate-500">{i.grn_number}</span> },
                        { key: 'invoice_date', label: 'Date', render: (i) => <span className="tabular-nums text-slate-500">{i.invoice_date}</span> },
                        { key: 'due_date', label: 'Due', render: (i) => <span className="tabular-nums text-slate-500">{i.due_date}</span> },
                        { key: 'total', label: 'Total', align: 'right', render: (i) => <MoneyDisplay amount={i.total} /> },
                        {
                            key: 'status', label: 'Status',
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
                    emptyTitle="No supplier invoices"
                    emptyMessage="Capture an invoice against a posted GRN — 3-way matching runs automatically."
                />
            </div>
        </ModuleLayout>
    );
}
