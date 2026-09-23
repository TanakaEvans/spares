import { Head, Link, router } from '@inertiajs/react';
import { FileMinus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';

export default function SupplierCreditsIndex({ credits, filters }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Supplier Credit Notes"
            breadcrumbs={[{ label: 'Purchasing', href: route('modules.show', 'purchasing') }, { label: 'Supplier Credit Notes' }]}>
            <Head title="Supplier Credit Notes" />
            <div className="space-y-4">
                <p className="text-sm text-slate-500">Credits received from suppliers against returns — each one reduced Accounts Payable.</p>
                <SearchInput id="scr-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('purchasing.supplier-credits.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Return, credit ref or supplier…" className="w-72" />
                <DataTable
                    columns={[
                        { key: 'return_number', label: 'Return', render: (c) => <span className="font-mono font-semibold text-slate-800">{c.return_number}</span> },
                        { key: 'credit_note_ref', label: 'Supplier credit ref', render: (c) => <span className="font-mono text-slate-600">{c.credit_note_ref ?? '—'}</span> },
                        { key: 'supplier', label: 'Supplier', render: (c) => <Link href={route('suppliers.show', c.supplier_id)} onClick={(e) => e.stopPropagation()} className="text-slate-700 hover:text-orange-600">{c.supplier}</Link> },
                        { key: 'reason', label: 'Reason', render: (c) => <span className="text-slate-500">{c.reason}</span> },
                        { key: 'credit_total', label: 'Credit', align: 'right', render: (c) => <span className="text-red-600"><MoneyDisplay amount={c.credit_total} /></span> },
                    ]}
                    rows={credits.data}
                    pagination={credits}
                    rowHref={(c) => route('purchasing.returns.index')}
                    emptyTitle="No supplier credit notes"
                    emptyMessage="Credits appear here once a return to supplier is credited."
                    emptyIcon={FileMinus}
                />
            </div>
        </ModuleLayout>
    );
}
