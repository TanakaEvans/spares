import { Head } from '@inertiajs/react';
import { SlidersHorizontal } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

// Read-only: a posted adjustment is immutable (rule 3). Corrections are a new
// adjustment in the opposite direction, never an edit here.
export default function AdjustmentShow({ adjustment: a }) {
    const reason = a.reason_code.replace('_', ' ');

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={a.adjustment_number}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Adjustments', href: route('inventory.adjustments.index') },
                { label: a.adjustment_number },
            ]}
        >
            <Head title={a.adjustment_number} />

            <div className="max-w-5xl space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center">
                                <SlidersHorizontal className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2.5 flex-wrap">
                                    <h1 className="font-mono text-2xl font-bold text-slate-900">{a.adjustment_number}</h1>
                                    <StatusBadge status={a.status} />
                                    <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-600">{reason}</span>
                                </div>
                                <p className="text-sm text-slate-500 mt-0.5">
                                    <EntityLink type="branch" id={a.branch_id} label={a.branch} />
                                    {' · '}posted {a.posted_at ?? a.created_at}
                                    {a.created_by && <> by <EntityLink type="user" id={a.created_by_id} label={a.created_by} /></>}
                                </p>
                            </div>
                        </div>
                    </div>

                    <dl className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Lines</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-800">{a.lines.length}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Net stock value</dt>
                            <dd className={`text-lg font-bold tabular-nums ${a.net_value < 0 ? 'text-red-600' : 'text-emerald-600'}`}>
                                <MoneyDisplay amount={a.net_value} />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">GL journal</dt>
                            <dd className="text-sm font-medium">
                                <EntityLink type="journal" id={a.journal_id} label={a.journal_number ?? (a.journal_id ? `#${a.journal_id}` : '—')} mono />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Source</dt>
                            <dd className="text-sm font-medium">
                                {a.stock_take_id
                                    ? <EntityLink type="stock_take" id={a.stock_take_id} label={a.stock_take_number} mono />
                                    : <span className="text-slate-500">Manual</span>}
                            </dd>
                        </div>
                    </dl>

                    {a.notes && <p className="mt-4 rounded-lg bg-slate-50 px-4 py-2.5 text-sm text-slate-600 whitespace-pre-line">{a.notes}</p>}
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Part</th>
                                <th className="px-3 py-3">Direction</th>
                                <th className="px-3 py-3 text-right">Qty</th>
                                <th className="px-3 py-3 text-right">Unit cost</th>
                                <th className="px-6 py-3 text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {a.lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-6 py-2.5">
                                        <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" />
                                        <span className="block text-xs text-slate-400 truncate max-w-[22rem]">{l.description}</span>
                                    </td>
                                    <td className="px-3 py-2.5">
                                        <StatusBadge status={l.direction === 'in' ? 'active' : 'rejected'} label={l.direction === 'in' ? 'IN +' : 'OUT −'} />
                                    </td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.unit_cost} /></td>
                                    <td className={`px-6 py-2.5 text-right tabular-nums font-medium ${l.line_value < 0 ? 'text-red-600' : 'text-emerald-600'}`}>
                                        <MoneyDisplay amount={l.line_value} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <RelatedLinks
                    trace={[
                        ...(a.stock_take_id ? [{ type: 'stock_take', id: a.stock_take_id, label: a.stock_take_number }] : []),
                        { type: 'adjustment', id: a.id, label: a.adjustment_number, current: true },
                        a.journal_id
                            ? { type: 'journal', id: a.journal_id, label: a.journal_number ?? `Journal #${a.journal_id}` }
                            : { label: 'No journal', pending: true },
                    ]}
                    groups={[
                        { title: 'Branch', items: [{ type: 'branch', id: a.branch_id, label: a.branch }] },
                        { title: 'Posted by', items: a.created_by ? [{ type: 'user', id: a.created_by_id, label: a.created_by }] : [] },
                        { title: 'Parts', items: a.lines.map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
                        { title: 'Stock', items: a.lines.map((l) => ({ type: 'stock', number: l.part_number, label: `${l.part_number} levels` })) },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}
