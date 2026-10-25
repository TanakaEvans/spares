import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import FormField from '@/Components/FormField';
import ListToolbar from '@/Components/ListToolbar';
import PartLinePicker from '@/Components/PartLinePicker';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';

export default function AdjustmentsIndex({ adjustments, branches, filters = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        branch_id: branches[0]?.id ?? '', reason_code: 'correction', notes: '', lines: [],
    });
    const [lines, setLines] = useState([]);
    const filter = useListFilter('inventory.adjustments.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const selectCls = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

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

            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Number, reason or notes…"
                searchClass="w-64"
                dateRange
                exportRoute="inventory.adjustments.export"
            >
                <select aria-label="Filter by branch" value={filters.branch_id ?? ''} onChange={(e) => filter({ branch_id: e.target.value })} className={selectCls}>
                    <option value="">All branches</option>
                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                </select>
                <select aria-label="Filter by reason" value={filters.reason ?? ''} onChange={(e) => filter({ reason: e.target.value })} className={selectCls}>
                    <option value="">All reasons</option>
                    {['correction', 'damage', 'write_off', 'found', 'theft', 'expiry'].map((r) => <option key={r} value={r}>{r.replace('_', ' ')}</option>)}
                </select>
                <select aria-label="Filter by status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className={selectCls}>
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                </select>
            </ListToolbar>

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start mt-4">
                <div className="xl:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'adjustment_number', label: 'Number', sortable: 'adjustment_number', render: (a) => <EntityLink type="adjustment" id={a.id} label={a.adjustment_number} mono className="font-semibold" /> },
                            { key: 'branch', label: 'Branch', render: (a) => <EntityLink type="branch" id={a.branch_id} label={a.branch} muted /> },
                            { key: 'reason_code', label: 'Reason', sortable: 'reason_code', render: (a) => <span className="capitalize text-slate-500">{a.reason_code.replace('_', ' ')}</span> },
                            {
                                key: 'lines', label: 'Lines',
                                render: (a) => (
                                    <span className="text-xs text-slate-400">
                                        {a.lines.map((l, i) => (
                                            <span key={l.part_id ?? i}>
                                                {i > 0 && ', '}
                                                <EntityLink type="part" id={l.part_id} label={l.part_number} mono muted /> {l.direction} {l.qty}
                                            </span>
                                        ))}
                                    </span>
                                ),
                            },
                            { key: 'status', label: 'Status', sortable: 'status', render: (a) => <StatusBadge status={a.status} /> },
                        ]}
                        rows={adjustments.data}
                        pagination={adjustments}
                        sorting={{ filters, onFilter: filter }}
                        rowHref={(a) => route('inventory.adjustments.show', a.id)}
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
