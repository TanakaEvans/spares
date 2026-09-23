import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import PartLinePicker from '@/Components/PartLinePicker';
import StatusBadge from '@/Components/StatusBadge';

export default function AdjustmentsIndex({ adjustments, branches }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        branch_id: branches[0]?.id ?? '', reason_code: 'correction', notes: '', lines: [],
    });
    const [lines, setLines] = useState([]);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function sync(next) {
        setLines(next);
        setData('lines', next.map(({ part_id, direction, qty, unit_cost }) => ({ part_id, direction, qty, unit_cost })));
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Stock Adjustments"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Adjustments' },
            ]}
        >
            <Head title="Stock Adjustments" />

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div className="xl:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'adjustment_number', label: 'Number', render: (a) => <span className="font-mono font-semibold text-slate-800">{a.adjustment_number}</span> },
                            { key: 'branch', label: 'Branch', render: (a) => <span className="text-slate-500">{a.branch}</span> },
                            { key: 'reason_code', label: 'Reason', render: (a) => <span className="capitalize text-slate-500">{a.reason_code.replace('_', ' ')}</span> },
                            { key: 'lines', label: 'Lines', render: (a) => <span className="text-xs text-slate-400">{a.lines}</span> },
                            { key: 'status', label: 'Status', render: (a) => <StatusBadge status={a.status} /> },
                        ]}
                        rows={adjustments.data}
                        pagination={adjustments}
                        emptyTitle="No adjustments yet"
                        emptyMessage="Post write-offs, corrections and found stock — each one hits the ledger and GL."
                    />
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('inventory.adjustments.store'), { preserveScroll: true, onSuccess: () => { reset(); setLines([]); } });
                    }}
                    className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
                >
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">New Adjustment</h2>
                    <div className="grid grid-cols-2 gap-3">
                        <FormField label="Branch" htmlFor="adj-branch" required error={errors.branch_id}>
                            <select id="adj-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                        <FormField label="Reason" htmlFor="adj-reason" required error={errors.reason_code}>
                            <select id="adj-reason" value={data.reason_code} onChange={(e) => setData('reason_code', e.target.value)} className={input}>
                                {['correction', 'damage', 'write_off', 'found', 'theft', 'expiry'].map((r) => (
                                    <option key={r} value={r}>{r.replace('_', ' ')}</option>
                                ))}
                            </select>
                        </FormField>
                    </div>
                    <PartLinePicker onPick={(p) => {
                        if (lines.some((l) => l.part_id === p.id)) return;
                        sync([...lines, { part_id: p.id, part_number: p.part_number, direction: 'out', qty: 1, unit_cost: p.suggested_cost }]);
                    }} />
                    {errors.lines && <p className="text-xs text-red-600">{errors.lines}</p>}

                    {lines.map((l, i) => (
                        <div key={l.part_id} className="flex items-center gap-2 text-sm">
                            <span className="font-mono font-semibold text-slate-800 w-28 truncate">{l.part_number}</span>
                            <select
                                value={l.direction}
                                aria-label={`Direction for ${l.part_number}`}
                                onChange={(e) => sync(lines.map((x, idx) => idx === i ? { ...x, direction: e.target.value } : x))}
                                className="w-20 rounded-lg border border-slate-300 px-2 py-1.5"
                            >
                                <option value="out">OUT −</option>
                                <option value="in">IN +</option>
                            </select>
                            <input
                                type="number" step="0.01" min="0.01" value={l.qty}
                                aria-label={`Quantity for ${l.part_number}`}
                                onChange={(e) => sync(lines.map((x, idx) => idx === i ? { ...x, qty: Number(e.target.value) || 0 } : x))}
                                className="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-right tabular-nums"
                            />
                            {l.direction === 'in' && (
                                <input
                                    type="number" step="0.0001" min="0" value={l.unit_cost}
                                    aria-label={`Unit cost for ${l.part_number}`}
                                    title="Unit cost (required for IN)"
                                    onChange={(e) => sync(lines.map((x, idx) => idx === i ? { ...x, unit_cost: Number(e.target.value) || 0 } : x))}
                                    className="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-right tabular-nums"
                                />
                            )}
                            <button type="button" onClick={() => sync(lines.filter((_, idx) => idx !== i))} aria-label="Remove line" className="p-1 rounded text-slate-300 hover:text-red-600">
                                <Trash2 className="w-4 h-4" />
                            </button>
                        </div>
                    ))}

                    <button
                        type="submit"
                        disabled={processing || lines.length === 0}
                        title={lines.length === 0 ? 'Add at least one line' : undefined}
                        className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Posting…' : 'Post Adjustment'}
                    </button>
                    <p className="text-xs text-slate-400">
                        Posting writes the stock ledger and the write-off journal immediately.
                    </p>
                </form>
            </div>
        </ModuleLayout>
    );
}
