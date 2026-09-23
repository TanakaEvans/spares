import { Head, router } from '@inertiajs/react';
import { Undo2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';

export default function CreditNotesIndex({ creditNotes, filters }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Credit Notes"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Credit Notes' }]}
        >
            <Head title="Credit Notes" />
            <div className="space-y-4">
                <SearchInput
                    id="cn-search"
                    value={filters.search ?? ''}
                    onSearch={(s) => router.get(route('sales.credit-notes.index'), s ? { search: s } : {}, { preserveState: true, replace: true })}
                    placeholder="Credit note number or customer…"
                    className="w-72"
                />
                <DataTable
                    columns={[
                        { key: 'document_number', label: 'Credit Note', render: (d) => <span className="font-mono font-semibold text-slate-800">{d.document_number}</span> },
                        { key: 'customer', label: 'Customer', render: (d) => <span className="text-slate-700">{d.customer}</span> },
                        { key: 'against', label: 'Against', render: (d) => <span className="font-mono text-slate-500">{d.against ?? '—'}</span> },
                        { key: 'document_date', label: 'Date', render: (d) => <span className="tabular-nums text-slate-500">{d.document_date}</span> },
                        { key: 'credit_mode', label: 'Refund', render: (d) => <span className="text-slate-500">{d.credit_mode === 'refund_cash' ? 'Cash' : 'To account'}</span> },
                        { key: 'total_incl', label: 'Amount', align: 'right', render: (d) => <span className="text-red-600"><MoneyDisplay amount={d.total_incl} /></span> },
                    ]}
                    rows={creditNotes.data}
                    pagination={creditNotes}
                    rowHref={(d) => route('sales.credit-notes.show', d.id)}
                    emptyTitle="No credit notes"
                    emptyMessage="Credit notes are raised from a posted invoice."
                    emptyIcon={Undo2}
                />
            </div>
        </ModuleLayout>
    );
}
