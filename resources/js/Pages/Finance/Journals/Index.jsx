import { Head, Link, router } from '@inertiajs/react';
import { Plus, PenLine } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function JournalsIndex({ journals, filters }) {
    function filter(params) {
        router.get(route('finance.journals.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Journals"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Journals' }]}
        >
            <Head title="Journals" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="jnl-search" value={filters.search ?? ''} onSearch={(s) => filter({ search: s })} placeholder="Journal number or description…" className="w-72" />
                    <select aria-label="Type" value={filters.type ?? ''} onChange={(e) => filter({ type: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All types</option>
                        {['manual', 'sales', 'purchase', 'receipt', 'payment', 'adjustment'].map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                    <Link href={route('finance.journals.create')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" />
                        New Journal
                    </Link>
                </div>
                <DataTable
                    columns={[
                        { key: 'journal_number', label: 'Number', render: (j) => <span className="font-mono font-semibold text-slate-800">{j.journal_number}</span> },
                        { key: 'journal_date', label: 'Date', render: (j) => <span className="tabular-nums text-slate-500">{j.journal_date}</span> },
                        { key: 'journal_type', label: 'Type', render: (j) => <span className="capitalize text-slate-500">{j.journal_type}</span> },
                        { key: 'description', label: 'Description', render: (j) => <span className="text-slate-600">{j.description}</span> },
                        { key: 'total', label: 'Amount', align: 'right', render: (j) => <MoneyDisplay amount={j.total} /> },
                        { key: 'status', label: 'Status', render: (j) => <StatusBadge status={j.status} /> },
                    ]}
                    rows={journals.data}
                    pagination={journals}
                    rowHref={(j) => route('finance.journals.show', j.id)}
                    emptyTitle="No journals"
                    emptyMessage="Every posted invoice, receipt and payment journalises here automatically."
                    emptyIcon={PenLine}
                />
            </div>
        </ModuleLayout>
    );
}
