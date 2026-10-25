import { Head, Link } from '@inertiajs/react';
import { PackageCheck, TriangleAlert } from 'lucide-react';
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
    { key: 'draft', label: 'Draft' },
];

export default function GrnsIndex({ grns, awaiting, stats, filters }) {
    const filter = useListFilter('purchasing.grns.index', filters);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Goods Receiving"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Goods Receiving' },
            ]}
        >
            <Head title="Goods Receiving" />

            <div className="space-y-4">
                <StatChips
                    items={[
                        { label: 'POs awaiting delivery', value: stats.awaiting, tone: stats.awaiting > 0 ? 'amber' : 'emerald' },
                        { label: 'Received today', value: stats.today_count },
                        { label: "Today's value", value: `R${fmt(stats.today_value)}` },
                    ]}
                />

                {awaiting.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">
                            POs awaiting delivery ({awaiting.length})
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {awaiting.map((po) => (
                                <Link
                                    key={po.id}
                                    href={route('purchasing.grns.create', po.id)}
                                    className={`inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors ${
                                        po.is_overdue ? 'border-red-200 bg-red-50/50 hover:border-red-400' : 'border-slate-200 hover:border-orange-400 hover:bg-orange-50/50'
                                    }`}
                                >
                                    {po.is_overdue ? <TriangleAlert className="w-4 h-4 text-red-500" /> : <PackageCheck className="w-4 h-4 text-green-600" />}
                                    <span className="font-mono font-semibold text-slate-700">{po.po_number}</span>
                                    <span className="text-slate-400">{po.supplier}</span>
                                    {po.expected_date && <span className={`text-xs tabular-nums ${po.is_overdue ? 'text-red-600 font-medium' : 'text-slate-400'}`}>{po.expected_date}</span>}
                                    <StatusBadge status={po.status} />
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="GRN, PO, delivery note or supplier…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="purchasing.grns.export"
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
                        { key: 'grn_number', label: 'GRN No', sortable: 'grn_number', render: (g) => <EntityLink type="grn" id={g.id} label={g.grn_number} mono className="font-semibold" /> },
                        { key: 'po_number', label: 'PO', render: (g) => <EntityLink type="purchase_order" id={g.po_id} label={g.po_number} mono /> },
                        { key: 'supplier', label: 'Supplier', render: (g) => <EntityLink type="supplier" id={g.supplier_id} label={g.supplier} /> },
                        { key: 'branch', label: 'Branch', render: (g) => <EntityLink type="branch" id={g.branch_id} label={g.branch} muted /> },
                        { key: 'received_date', label: 'Received', sortable: 'received_date', render: (g) => <span className="tabular-nums text-slate-500">{g.received_date}</span> },
                        { key: 'value', label: 'Value', align: 'right', render: (g) => <MoneyDisplay amount={g.value} /> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (g) => <StatusBadge status={g.status} /> },
                    ]}
                    rows={grns.data}
                    pagination={grns}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(g) => route('purchasing.grns.show', g.id)}
                    emptyTitle="Nothing received yet"
                    emptyMessage="Receive goods against a submitted or confirmed purchase order."
                />
            </div>
        </ModuleLayout>
    );
}
