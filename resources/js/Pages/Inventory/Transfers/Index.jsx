import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import PartLinePicker from '@/Components/PartLinePicker';
import StatusBadge from '@/Components/StatusBadge';

export default function TransfersIndex({ transfers, branches }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        from_branch_id: branches[0]?.id ?? '',
        to_branch_id: branches[1]?.id ?? '',
        notes: '', lines: [],
    });
    const [lines, setLines] = useState([]);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function sync(next) {
        setLines(next);
        setData('lines', next.map(({ part_id, qty }) => ({ part_id, qty })));
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Stock Transfers"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Transfers' },
            ]}
        >
            <Head title="Stock Transfers" />

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div className="xl:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'transfer_number', label: 'Number', render: (t) => <span className="font-mono font-semibold text-slate-800">{t.transfer_number}</span> },
                            { key: 'from', label: 'From', render: (t) => <span className="text-slate-500">{t.from}</span> },
                            { key: 'to', label: 'To', render: (t) => <span className="text-slate-500">{t.to}</span> },
                            { key: 'lines', label: 'Lines', render: (t) => <span className="text-xs text-slate-400">{t.lines}</span> },
                            { key: 'status', label: 'Status', render: (t) => <StatusBadge status={t.status} /> },
                            {
                                key: 'actions', label: '',
                                render: (t) => (
                                    <div onClick={(e) => e.stopPropagation()}>
                                        {t.status === 'draft' && (
                                            <button
                                                onClick={() => router.post(route('inventory.transfers.dispatch', t.id), {}, { preserveScroll: true })}
                                                className="text-xs font-semibold text-orange-700 hover:underline"
                                            >
                                                Dispatch
                                            </button>
                                        )}
                                        {t.status === 'dispatched' && (
                                            <button
                                                onClick={() => router.post(route('inventory.transfers.receive', t.id), {}, { preserveScroll: true })}
                                                className="text-xs font-semibold text-green-700 hover:underline"
                                            >
                                                Receive
                                            </button>
                                        )}
                                    </div>
                                ),
                            },
                        ]}
                        rows={transfers.data}
                        pagination={transfers}
                        emptyTitle="No transfers yet"
                        emptyMessage="Move stock between branches — dispatch reduces the source, receive lands it at the destination."
                    />
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('inventory.transfers.store'), { preserveScroll: true, onSuccess: () => { reset(); setLines([]); } });
                    }}
                    className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
                >
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">New Transfer</h2>
                    <div className="grid grid-cols-2 gap-3">
                        <FormField label="From branch" htmlFor="tr-from" required error={errors.from_branch_id}>
                            <select id="tr-from" value={data.from_branch_id} onChange={(e) => setData('from_branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                        <FormField label="To branch" htmlFor="tr-to" required error={errors.to_branch_id}>
                            <select id="tr-to" value={data.to_branch_id} onChange={(e) => setData('to_branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                    </div>
                    <PartLinePicker onPick={(p) => {
                        if (lines.some((l) => l.part_id === p.id)) return;
                        sync([...lines, { part_id: p.id, part_number: p.part_number, qty: 1 }]);
                    }} />
                    {errors.lines && <p className="text-xs text-red-600">{errors.lines}</p>}
                    {errors.to_branch_id && <p className="text-xs text-red-600">{errors.to_branch_id}</p>}

                    {lines.map((l, i) => (
                        <div key={l.part_id} className="flex items-center gap-2 text-sm">
                            <span className="font-mono font-semibold text-slate-800 flex-1 truncate">{l.part_number}</span>
                            <input
                                type="number" step="0.01" min="0.01" value={l.qty}
                                aria-label={`Quantity for ${l.part_number}`}
                                onChange={(e) => sync(lines.map((x, idx) => idx === i ? { ...x, qty: Number(e.target.value) || 0 } : x))}
                                className="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-right tabular-nums"
                            />
                            <button type="button" onClick={() => sync(lines.filter((_, idx) => idx !== i))} aria-label="Remove line" className="p-1 rounded text-slate-300 hover:text-red-600">
                                <Trash2 className="w-4 h-4" />
                            </button>
                        </div>
                    ))}

                    <button
                        type="submit"
                        disabled={processing || lines.length === 0 || data.from_branch_id === data.to_branch_id}
                        title={data.from_branch_id === data.to_branch_id ? 'Branches must differ' : lines.length === 0 ? 'Add at least one line' : undefined}
                        className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Creating…' : 'Create Transfer'}
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
