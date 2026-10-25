import { Head } from '@inertiajs/react';
import { Undo2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import EntityLink from '@/Components/EntityLink';
import StatChips from '@/Components/StatChips';
import { fmt } from '@/Components/Pos/posMath';
import { useListFilter } from '@/lib/listFilters';

export default function CreditNotesIndex({ creditNotes, stats, filters }) {
    const filter = useListFilter('sales.credit-notes.index', filters);
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Credit Notes"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Credit Notes' }]}
        >
            <Head title="Credit Notes" />
            <div className="space-y-4">
                <StatChips items={[
                    { label: 'This month', value: `${stats.month_count} credit${stats.month_count === 1 ? '' : 's'}` },
                    { label: 'Credited this month', value: `−R${fmt(stats.month_total)}`, tone: stats.month_total > 0 ? 'red' : 'slate' },
                ]} />
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Credit note number or customer…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="sales.credit-notes.export"
                />
                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Credit Note', sortable: 'document_number', render: (d) => <EntityLink type="credit_note" id={d.id} label={d.document_number} mono className="font-semibold" /> },
                        { key: 'customer', label: 'Customer', render: (d) => <EntityLink type="customer" id={d.customer_id} label={d.customer} /> },
                        { key: 'against', label: 'Against', render: (d) => <EntityLink type="invoice" id={d.against_id} label={d.against} mono muted /> },
                        { key: 'document_date', label: 'Date', sortable: 'document_date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'credit_mode', label: 'Refund', sortable: 'credit_mode', render: (d) => <span className="text-slate-500">{d.credit_mode === 'refund_cash' ? 'Cash' : 'To account'}</span> },
                        { key: 'total_incl', label: 'Amount', align: 'right', sortable: 'total_incl', render: (d) => <span className="text-red-600"><MoneyDisplay amount={d.total_incl} /></span> },
                    ]}
                    rows={creditNotes.data}
                    pagination={creditNotes}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(d) => route('sales.credit-notes.show', d.id)}
                    emptyTitle="No credit notes"
                    emptyMessage="Credit notes are raised from a posted invoice."
                    emptyIcon={Undo2}
                />
            </div>
        </ModuleLayout>
    );
}
