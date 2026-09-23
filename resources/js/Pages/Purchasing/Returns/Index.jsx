import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

export default function ReturnsIndex({ returns }) {
    const [action, setAction] = useState(null); // {type: 'ship'|'credit', return}
    const [value, setValue] = useState('');
    const [value2, setValue2] = useState('');

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
                <div className="flex">
                    <Link
                        href={route('purchasing.returns.create')}
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        New Return
                    </Link>
                </div>

                <DataTable
                    columns={[
                        { key: 'return_number', label: 'Return No', render: (r) => <span className="font-mono font-semibold text-slate-800">{r.return_number}</span> },
                        { key: 'supplier', label: 'Supplier', render: (r) => <span className="text-slate-700">{r.supplier}</span> },
                        { key: 'reason', label: 'Reason', render: (r) => <span className="capitalize text-slate-500">{r.reason.replace('_', ' ')}</span> },
                        { key: 'supplier_rma', label: 'RMA', render: (r) => <span className="font-mono text-slate-500">{r.supplier_rma ?? '—'}</span> },
                        { key: 'value', label: 'Stock value', align: 'right', render: (r) => <MoneyDisplay amount={r.value} /> },
                        { key: 'credit_total', label: 'Credited', align: 'right', render: (r) => r.credit_total !== null ? <MoneyDisplay amount={r.credit_total} /> : <span className="text-slate-300">—</span> },
                        { key: 'status', label: 'Status', render: (r) => <StatusBadge status={r.status} /> },
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
                    emptyTitle="No supplier returns"
                    emptyMessage="Return damaged, incorrect or excess stock and recover the money."
                />
            </div>

            {action && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                    <div className="absolute inset-0 bg-slate-900/50" onClick={() => setAction(null)} />
                    <div className="relative w-full max-w-md rounded-xl bg-white shadow-xl p-6 space-y-4">
                        <h2 className="text-lg font-semibold text-slate-900">
                            {action.type === 'ship' ? `Ship ${action.return.return_number}` : `Capture credit for ${action.return.return_number}`}
                        </h2>
                        {action.type === 'ship' ? (
                            <>
                                <p className="text-sm text-slate-500">Stock leaves the shelf when you ship. The supplier's RMA number is required.</p>
                                <input
                                    value={value}
                                    onChange={(e) => setValue(e.target.value)}
                                    placeholder="Supplier RMA number"
                                    aria-label="Supplier RMA number"
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono"
                                    autoFocus
                                />
                            </>
                        ) : (
                            <>
                                <p className="text-sm text-slate-500">The credit reduces what you owe this supplier.</p>
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
                                    placeholder="Credit amount"
                                    aria-label="Credit amount"
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-right tabular-nums"
                                />
                            </>
                        )}
                        <div className="flex justify-end gap-3">
                            <button onClick={() => setAction(null)} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                Cancel
                            </button>
                            <button
                                onClick={run}
                                disabled={!value || (action.type === 'credit' && !value2)}
                                className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                            >
                                {action.type === 'ship' ? 'Ship Return' : 'Capture Credit'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </ModuleLayout>
    );
}
