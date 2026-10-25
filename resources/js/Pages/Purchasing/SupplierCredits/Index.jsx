import { Head } from '@inertiajs/react';
import { FileMinus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import StatChips from '@/Components/StatChips';
import EntityLink from '@/Components/EntityLink';
import { fmt } from '@/Components/Pos/posMath';
import { useListFilter } from '@/lib/listFilters';

export default function SupplierCreditsIndex({ credits, stats, filters }) {
    const filter = useListFilter('purchasing.supplier-credits.index', filters);
    return (
        <ModuleLayout navConfig={navConfig} title="Supplier Credit Notes"
            breadcrumbs={[{ label: 'Purchasing', href: route('modules.show', 'purchasing') }, { label: 'Supplier Credit Notes' }]}>
            <Head title="Supplier Credit Notes" />
            <div className="space-y-4">
                <p className="text-sm text-slate-500">Credits received from suppliers against returns — each one reduced Accounts Payable.</p>
                <StatChips
                    items={[
                        { label: 'Credit notes', value: stats.count },
                        { label: 'Recovered from suppliers', value: `R${fmt(stats.total)}` },
                    ]}
                />
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Return, credit ref or supplier…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="purchasing.supplier-credits.export"
                />
                <DataTable
                    columns={[
                        { key: 'return_number', label: 'Return', sortable: 'return_number', render: (c) => <EntityLink type="supplier_return" id={c.id} label={c.return_number} mono className="font-semibold" /> },
                        { key: 'credit_note_ref', label: 'Supplier credit ref', sortable: 'credit_note_ref', render: (c) => <span className="font-mono text-slate-600">{c.credit_note_ref ?? '—'}</span> },
                        { key: 'supplier', label: 'Supplier', render: (c) => <EntityLink type="supplier" id={c.supplier_id} label={c.supplier} /> },
                        { key: 'reason', label: 'Reason', render: (c) => <span className="text-slate-500">{c.reason}</span> },
                        { key: 'credit_total', label: 'Credit', align: 'right', sortable: 'credit_total', render: (c) => <span className="text-red-600"><MoneyDisplay amount={c.credit_total} /></span> },
                    ]}
                    rows={credits.data}
                    pagination={credits}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(c) => route('purchasing.returns.show', c.id)}
                    emptyTitle="No supplier credit notes"
                    emptyMessage="Credits appear here once a return to supplier is credited."
                    emptyIcon={FileMinus}
                />
            </div>
        </ModuleLayout>
    );
}
