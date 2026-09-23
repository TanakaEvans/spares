import { Head, Link, router } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function SystemLogs({ journals, filters, types }) {
    function filter(params) {
        router.get(route('system.logs'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Activity Log"
            breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Activity Log' }]}
        >
            <Head title="Activity Log" />

            <div className="space-y-4">
                <p className="text-sm text-slate-500">
                    The audited trail of every posted financial event — who posted it, when, and a link back to the journal.
                </p>
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="log-search" value={filters.search ?? ''} onSearch={(s) => filter({ search: s })} placeholder="Journal number, description or reference…" className="w-80" />
                    <select aria-label="Type" value={filters.type ?? ''} onChange={(e) => filter({ type: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All events</option>
                        {types.map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                </div>
                <DataTable
                    columns={[
                        { key: 'at', label: 'When', render: (j) => <span className="tabular-nums text-slate-500">{j.at}</span> },
                        { key: 'user', label: 'User', render: (j) => <span className="text-slate-700">{j.user}</span> },
                        { key: 'type', label: 'Event', render: (j) => <StatusBadge status={j.type === 'manual' ? 'submitted' : j.type === 'opening' ? 'special_order' : 'posted'} label={j.type} /> },
                        { key: 'description', label: 'Description', render: (j) => <span className="text-slate-600">{j.description}</span> },
                        { key: 'journal_number', label: 'Journal', render: (j) => <Link href={route('finance.journals.show', j.id)} onClick={(e) => e.stopPropagation()} className="font-mono text-slate-500 hover:text-orange-600">{j.journal_number}</Link> },
                        { key: 'branch', label: 'Branch', render: (j) => <span className="text-slate-400">{j.branch ?? '—'}</span> },
                    ]}
                    rows={journals.data}
                    pagination={journals}
                    rowHref={(j) => route('finance.journals.show', j.id)}
                    emptyTitle="No activity yet"
                    emptyMessage="Posted sales, purchases, receipts, payments and journals appear here."
                    emptyIcon={ScrollText}
                />
            </div>
        </ModuleLayout>
    );
}
