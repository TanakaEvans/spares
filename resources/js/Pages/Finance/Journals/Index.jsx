import { Head, Link } from '@inertiajs/react';
import { Plus, PenLine } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function JournalsIndex({ journals, filters }) {
    const filter = useListFilter('finance.journals.index', filters);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Journals"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Journals' }]}
        >
            <Head title="Journals" />
            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Journal number or description…"
                    searchClass="w-72"
                    dateRange
                    exportRoute="finance.journals.export"
                >
                    <select aria-label="Type" value={filters.type ?? ''} onChange={(e) => filter({ type: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All types</option>
                        {['manual', 'sales', 'purchase', 'receipt', 'payment', 'adjustment'].map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                    <Link href={route('finance.journals.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Journal
                    </Link>
                </ListToolbar>
                <DataTable
                    columns={[
                        { key: 'journal_number', label: 'Number', sortable: 'journal_number', render: (j) => <EntityLink type="journal" id={j.id} label={j.journal_number} mono className="font-semibold" /> },
                        { key: 'journal_date', label: 'Date', sortable: 'journal_date', render: (j) => <span className="tabular-nums text-slate-500">{j.journal_date}</span> },
                        { key: 'journal_type', label: 'Type', sortable: 'journal_type', render: (j) => <span className="capitalize text-slate-500">{j.journal_type}</span> },
                        { key: 'source', label: 'Source', render: (j) => j.source
                            ? <EntityLink type={j.source.type} id={j.source.id} label={j.source.label} mono />
                            : <span className="text-slate-400">{j.reference ? <span className="font-mono">{j.reference}</span> : '—'}</span> },
                        { key: 'description', label: 'Description', render: (j) => <span className="text-slate-600">{j.description}</span> },
                        { key: 'total', label: 'Amount', align: 'right', render: (j) => <MoneyDisplay amount={j.total} /> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (j) => <StatusBadge status={j.status} /> },
                    ]}
                    rows={journals.data}
                    pagination={journals}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(j) => route('finance.journals.show', j.id)}
                    emptyTitle="No journals"
                    emptyMessage="Every posted invoice, receipt and payment journalises here automatically."
                    emptyIcon={PenLine}
                />
            </div>
        </ModuleLayout>
    );
}
