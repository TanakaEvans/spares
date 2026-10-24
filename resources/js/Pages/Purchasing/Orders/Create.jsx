import { useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Minus, Plus, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PartLinePicker from '@/Components/PartLinePicker';
import EntityLink from '@/Components/EntityLink';

const DRAFT_KEY = 'sparespro.draft.purchase-order';

export default function OrderCreate({ suppliers, branches, preselectedSupplierId }) {
    const { data, setData, post, processing, errors } = useForm({
        supplier_id: preselectedSupplierId ?? '',
        branch_id: branches[0]?.id ?? '',
        expected_date: '',
        supplier_ref: '',
        notes: '',
        lines: [],
    });
    const [lines, setLines] = useState([]);
    const [hydrated, setHydrated] = useState(false);

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const subtotal = lines.reduce((n, l) => n + l.qty * l.unit_cost, 0);
    const supplier = suppliers.find((s) => String(s.id) === String(data.supplier_id));

    // An unfinished order survives a refresh or a dead machine.
    useEffect(() => {
        try {
            const saved = JSON.parse(sessionStorage.getItem(DRAFT_KEY));
            if (saved && !preselectedSupplierId) {
                setLines(saved.lines ?? []);
                setData((d) => ({ ...d, ...saved.data, lines: (saved.lines ?? []).map(({ part_id, qty, unit_cost }) => ({ part_id, qty, unit_cost })) }));
            }
        } catch { /* fresh draft */ }
        setHydrated(true);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (!hydrated) return;
        sessionStorage.setItem(DRAFT_KEY, JSON.stringify({
            lines,
            data: { supplier_id: data.supplier_id, branch_id: data.branch_id, expected_date: data.expected_date, supplier_ref: data.supplier_ref, notes: data.notes },
        }));
    }, [lines, data.supplier_id, data.branch_id, data.expected_date, data.supplier_ref, data.notes, hydrated]);

    // Expected date defaults to today + the supplier's lead time.
    useEffect(() => {
        if (supplier?.lead_time_days != null && data.expected_date === '') {
            const d = new Date();
            d.setDate(d.getDate() + Number(supplier.lead_time_days));
            setData('expected_date', d.toISOString().slice(0, 10));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.supplier_id]);

    function sync(next) {
        setLines(next);
        setData('lines', next.map(({ part_id, qty, unit_cost }) => ({ part_id, qty, unit_cost })));
    }

    function addLine(part, qty = 1) {
        if (lines.some((l) => l.part_id === part.id)) {
            sync(lines.map((l) => (l.part_id === part.id ? { ...l, qty: l.qty + qty } : l)));
            return;
        }
        sync([...lines, {
            part_id: part.id, part_number: part.part_number, description: part.description,
            qty, unit_cost: part.suggested_cost, cost_source: part.cost_source,
        }]);
    }

    function updateLine(i, field, value) {
        sync(lines.map((l, idx) => (idx === i ? { ...l, [field]: Number(value) || 0 } : l)));
    }

    function onKey(e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            submit();
        }
    }

    function submit() {
        post(route('purchasing.orders.store'), {
            onSuccess: () => sessionStorage.removeItem(DRAFT_KEY),
        });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Purchase Order"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Purchase Orders', href: route('purchasing.orders.index') },
                { label: 'New Order' },
            ]}
        >
            <Head title="New Purchase Order" />

            <form
                onSubmit={(e) => { e.preventDefault(); submit(); }}
                onKeyDown={onKey}
                className="max-w-4xl space-y-6"
            >
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <FormField label="Supplier" htmlFor="po-supplier" required error={errors.supplier_id}>
                            <select id="po-supplier" value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} className={input}>
                                <option value="">Select supplier…</option>
                                {suppliers.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.name}{s.lead_time_days != null ? ` — ${s.lead_time_days}d lead` : ''}
                                    </option>
                                ))}
                            </select>
                        </FormField>
                        <FormField label="Deliver to branch" htmlFor="po-branch" required error={errors.branch_id}>
                            <select id="po-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                        <FormField
                            label={supplier?.lead_time_days != null ? `Expected (lead ${supplier.lead_time_days}d)` : 'Expected date'}
                            htmlFor="po-expected" error={errors.expected_date}
                        >
                            <input id="po-expected" type="date" value={data.expected_date} onChange={(e) => setData('expected_date', e.target.value)} className={input} />
                        </FormField>
                        <FormField label="Supplier ref" htmlFor="po-ref" error={errors.supplier_ref}>
                            <input id="po-ref" value={data.supplier_ref} onChange={(e) => setData('supplier_ref', e.target.value)} className={input} placeholder="Their quote/order ref" />
                        </FormField>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Lines</h2>
                    <PartLinePicker
                        supplierId={data.supplier_id || null}
                        branchId={data.branch_id || null}
                        onPick={addLine}
                        placeholder="Search part — cost pre-fills from the supplier's price list, 3*CODE for qty…"
                    />
                    {errors.lines && <p className="text-xs text-red-600">{errors.lines}</p>}
                    {!data.supplier_id && (
                        <p className="text-xs text-amber-700">Pick the supplier first — their price-list costs fill each line.</p>
                    )}

                    {lines.length > 0 && (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="py-2">Part</th>
                                    <th className="py-2 text-right w-36">Qty</th>
                                    <th className="py-2 text-right w-32">Unit Cost</th>
                                    <th className="py-2 text-right w-28">Total</th>
                                    <th className="w-10"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {lines.map((l, i) => (
                                    <tr key={l.part_id}>
                                        <td className="py-2">
                                            <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" />
                                            <span className="block text-xs text-slate-400">{l.description} · cost from {l.cost_source}</span>
                                        </td>
                                        <td className="py-2">
                                            <div className="flex items-center justify-end gap-1">
                                                <button type="button" onClick={() => updateLine(i, 'qty', Math.max(0, l.qty - 1))} aria-label="Decrease" className="p-1 rounded border border-slate-200 text-slate-400 hover:bg-slate-50">
                                                    <Minus className="w-3.5 h-3.5" />
                                                </button>
                                                <input
                                                    type="number" step="0.01" min="0.01" value={l.qty}
                                                    aria-label={`Quantity for ${l.part_number}`}
                                                    onChange={(e) => updateLine(i, 'qty', e.target.value)}
                                                    className="w-16 rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-right tabular-nums"
                                                />
                                                <button type="button" onClick={() => updateLine(i, 'qty', l.qty + 1)} aria-label="Increase" className="p-1 rounded border border-slate-200 text-slate-400 hover:bg-slate-50">
                                                    <Plus className="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                        <td className="py-2">
                                            <input
                                                type="number" step="0.0001" min="0" value={l.unit_cost}
                                                aria-label={`Unit cost for ${l.part_number}`}
                                                onChange={(e) => updateLine(i, 'unit_cost', e.target.value)}
                                                className="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm text-right tabular-nums"
                                            />
                                        </td>
                                        <td className="py-2 text-right tabular-nums font-medium">
                                            <MoneyDisplay amount={l.qty * l.unit_cost} />
                                        </td>
                                        <td className="py-2 text-right">
                                            <button type="button" onClick={() => sync(lines.filter((_, idx) => idx !== i))} aria-label="Remove line" className="p-1.5 rounded text-slate-300 hover:text-red-600 hover:bg-red-50">
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-slate-200">
                                    <td colSpan={3} className="py-2.5 text-right font-semibold text-slate-700">Order total</td>
                                    <td className="py-2.5 text-right font-bold text-lg tabular-nums"><MoneyDisplay amount={subtotal} /></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    )}
                </div>

                <div className="flex items-center justify-between">
                    <p className="text-xs text-slate-400">Ctrl+Enter creates the draft · the draft survives a refresh</p>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing || !data.supplier_id || lines.length === 0}
                            title={lines.length === 0 ? 'Add at least one line' : undefined}
                            className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            {processing ? 'Creating…' : 'Create Draft PO'}
                        </button>
                    </div>
                </div>
            </form>
        </ModuleLayout>
    );
}
