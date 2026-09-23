import { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { RefreshCw, ShoppingCart } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import EmptyState from '@/Components/EmptyState';

// The buyer's morning screen (Module 1.5): what needs ordering, and
// one click drafts the POs to each part's preferred supplier.
export default function ReorderIndex({ rows, branches, suppliers, filters }) {
    const [selected, setSelected] = useState(() => new Set(rows.map((r) => r.part_id)));
    const [qtys, setQtys] = useState(() => Object.fromEntries(rows.map((r) => [r.part_id, r.suggested_qty])));

    const branchId = filters.branch_id ?? branches[0]?.id;

    const chosen = useMemo(
        () => rows.filter((r) => selected.has(r.part_id)),
        [rows, selected]
    );
    const missingSupplier = chosen.filter((r) => !r.preferred_supplier_id);

    function toggle(partId) {
        setSelected((s) => {
            const next = new Set(s);
            next.has(partId) ? next.delete(partId) : next.add(partId);
            return next;
        });
    }

    function createPos() {
        router.post(route('inventory.reorder.create-pos'), {
            branch_id: branchId,
            rows: chosen.map((r) => ({
                part_id: r.part_id,
                qty: Number(qtys[r.part_id]) || r.suggested_qty,
                supplier_id: r.preferred_supplier_id,
            })),
        });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Reorder Report"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Reorder Report' },
            ]}
        >
            <Head title="Reorder Report" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <select
                        aria-label="Branch"
                        value={filters.branch_id ?? ''}
                        onChange={(e) => router.get(route('inventory.reorder.index'), e.target.value ? { branch_id: e.target.value } : {}, { preserveState: true })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <button
                        onClick={createPos}
                        disabled={chosen.length === 0 || missingSupplier.length > 0 || !branchId}
                        title={missingSupplier.length > 0
                            ? `No preferred supplier for: ${missingSupplier.map((r) => r.part_number).join(', ')}`
                            : chosen.length === 0 ? 'Select at least one part' : undefined}
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <ShoppingCart className="w-4 h-4" />
                        Create Draft PO{chosen.length > 1 ? 's' : ''} ({chosen.length})
                    </button>
                </div>

                {missingSupplier.length > 0 && (
                    <p className="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2.5 text-sm text-amber-800">
                        {missingSupplier.map((r) => r.part_number).join(', ')} — no preferred supplier set.
                        Deselect them, or set approved suppliers first.
                    </p>
                )}

                {rows.length === 0 ? (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                        <EmptyState
                            icon={RefreshCw}
                            title="Nothing below reorder point"
                            message="Set reorder points on the Stock Levels screen — parts appear here when availability falls to that level."
                        />
                    </div>
                ) : (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="pl-6 py-3 w-10"></th>
                                    <th className="px-3 py-3">Part</th>
                                    <th className="px-3 py-3">Branch</th>
                                    <th className="px-3 py-3 text-right">Available</th>
                                    <th className="px-3 py-3 text-right">Reorder at</th>
                                    <th className="px-3 py-3 text-right w-28">Order qty</th>
                                    <th className="px-6 py-3">Preferred supplier</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {rows.map((r) => (
                                    <tr key={r.stock_level_id} className={selected.has(r.part_id) ? '' : 'opacity-50'}>
                                        <td className="pl-6 py-2.5">
                                            <input
                                                type="checkbox"
                                                checked={selected.has(r.part_id)}
                                                onChange={() => toggle(r.part_id)}
                                                aria-label={`Include ${r.part_number}`}
                                                className="rounded border-slate-300 text-orange-600"
                                            />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <span className="font-mono font-semibold text-slate-800">{r.part_number}</span>
                                            <span className="block text-xs text-slate-400">{r.description}</span>
                                        </td>
                                        <td className="px-3 py-2.5 text-slate-500">{r.branch}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums text-red-600 font-semibold">{r.available}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums text-slate-500">{r.reorder_point}</td>
                                        <td className="px-3 py-2.5">
                                            <input
                                                type="number" step="1" min="1"
                                                value={qtys[r.part_id] ?? r.suggested_qty}
                                                aria-label={`Order quantity for ${r.part_number}`}
                                                onChange={(e) => setQtys((q) => ({ ...q, [r.part_id]: e.target.value }))}
                                                className="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-right tabular-nums"
                                            />
                                        </td>
                                        <td className="px-6 py-2.5">
                                            {r.preferred_supplier ? (
                                                <span className="text-slate-700">{r.preferred_supplier}</span>
                                            ) : (
                                                <select
                                                    aria-label={`Set preferred supplier for ${r.part_number}`}
                                                    defaultValue=""
                                                    onChange={(e) => e.target.value && router.post(route('inventory.reorder.preferred-supplier'), {
                                                        part_id: r.part_id, supplier_id: e.target.value,
                                                    }, { preserveScroll: true })}
                                                    className="rounded-lg border border-amber-300 bg-amber-50/50 px-2 py-1.5 text-xs"
                                                >
                                                    <option value="">set supplier…</option>
                                                    {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                                </select>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </ModuleLayout>
    );
}
