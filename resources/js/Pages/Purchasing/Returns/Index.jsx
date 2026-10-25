import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';

const PILLS = [
    { key: '', label: 'All' },
    { key: 'draft', label: 'Draft' },
    { key: 'shipped', label: 'Shipped' },
    { key: 'credited', label: 'Credited' },
];

export default function ReturnsIndex({ returns, stats, filters }) {
    const [action, setAction] = useState(null); // {type: 'ship'|'credit', return}
    const [value, setValue] = useState('');
    const [value2, setValue2] = useState('');
    const filter = useListFilter('purchasing.returns.index', filters);

    function run() {
        if (action.type === 'ship') {
            router.post(route('purchasing.returns.ship', action.return.id), { supplier_rma: value }, {
                preserveScroll: true, onSuccess: () => setAction(null),
            });
        } else {
            router.post(route('purchasing.returns.credit', action.return.id), {
                credit_note_ref: value, credit_total: value2,
            }, { preserveScroll: true, onSuccess: () => setAction(null) });
        }
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Returns to Supplier"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Returns to Supplier' },
            ]}
        >
            <Head title="Returns to Supplier" />

            <div className="space-y-4">
                <StatChips
                    items={[
                        { label: 'To ship', value: stats.to_ship, tone: stats.to_ship > 0 ? 'amber' : 'slate' },
                        { label: 'Awaiting credit', value: stats.awaiting_credit, tone: stats.awaiting_credit > 0 ? 'red' : 'emerald' },
                        { label: 'Value out for credit', value: `R${fmt(stats.awaiting_value)}`, tone: stats.awaiting_value > 0 ? 'orange' : 'slate' },
                    ]}
                />

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Return, RMA or supplier…"
                    searchClass="w-64"
                    dateRange
                    exportRoute="purchasing.returns.export"
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
                    <Link
                        href={route('purchasing.returns.create')}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        New Return
                    </Link>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'return_number', label: 'Return No', sortable: 'return_number', render: (r) => <EntityLink type="supplier_return" id={r.id} label={r.return_number} mono className="font-semibold" /> },
                        { key: 'supplier', label: 'Supplier', render: (r) => <EntityLink type="supplier" id={r.supplier_id} label={r.supplier} /> },
                        { key: 'reason', label: 'Reason', render: (r) => <span className="capitalize text-slate-500">{r.reason.replace('_', ' ')}</span> },
                        { key: 'supplier_rma', label: 'RMA', render: (r) => <span className="font-mono text-slate-500">{r.supplier_rma ?? '—'}</span> },
                        { key: 'value', label: 'Stock value', align: 'right', render: (r) => <MoneyDisplay amount={r.value} /> },
                        { key: 'credit_total', label: 'Credited', align: 'right', render: (r) => r.credit_total !== null ? <MoneyDisplay amount={r.credit_total} /> : <span className="text-slate-300">—</span> },
                        { key: 'status', label: 'Status', sortable: 'status', render: (r) => <StatusBadge status={r.status} /> },
                        {
                            key: 'actions', label: '',
                            render: (r) => (
                                <div onClick={(e) => e.stopPropagation()}>
                                    {r.status === 'draft' && (
                                        <button onClick={() => { setAction({ type: 'ship', return: r }); setValue(''); }} className="text-xs font-semibold text-orange-700 hover:underline">
                                            Ship (needs RMA)
                                        </button>
                                    )}
                                    {r.status === 'shipped' && (
                                        <button onClick={() => { setAction({ type: 'credit', return: r }); setValue(''); setValue2(String(r.value)); }} className="text-xs font-semibold text-green-700 hover:underline">
                                            Capture credit
                                        </button>
                                    )}
                                </div>
                            ),
                        },
                    ]}
                    rows={returns.data}
                    pagination={returns}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(r) => route('purchasing.returns.show', r.id)}
                    emptyTitle="No supplier returns"
                    emptyMessage="Return damaged, incorrect or excess stock and recover the money."
                />
            </div>

            <Dialog open={action !== null} onOpenChange={(open) => !open && setAction(null)}>
                <DialogContent className="bg-white rounded-xl sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {action?.type === 'ship' ? `Ship ${action?.return.return_number}` : `Capture credit for ${action?.return.return_number}`}
                        </DialogTitle>
                        <DialogDescription>
                            {action?.type === 'ship'
                                ? 'Stock leaves the shelf when you ship. The supplier\'s RMA number is required.'
                                : 'The credit reduces what you owe this supplier.'}
                        </DialogDescription>
                    </DialogHeader>
                    {action?.type === 'ship' ? (
                        <input
                            value={value}
                            onChange={(e) => setValue(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && value && run()}
                            placeholder="Supplier RMA number"
                            aria-label="Supplier RMA number"
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono"
                            autoFocus
                        />
                    ) : (
                        <div className="space-y-3">
                            <input
                                value={value}
                                onChange={(e) => setValue(e.target.value)}
                                placeholder="Supplier credit note ref"
                                aria-label="Credit note reference"
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono"
                                autoFocus
                            />
                            <input
                                type="number" step="0.01"
                                value={value2}
                                onChange={(e) => setValue2(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && value && value2 && run()}
                                placeholder="Credit amount"
                                aria-label="Credit amount"
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-right tabular-nums"
                            />
                        </div>
                    )}
                    <DialogFooter>
                        <button onClick={() => setAction(null)} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button
                            onClick={run}
                            disabled={!value || (action?.type === 'credit' && !value2)}
                            className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                        >
                            {action?.type === 'ship' ? 'Ship Return' : 'Capture Credit'}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </ModuleLayout>
    );
}
