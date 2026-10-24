import { Minus, Plus, Trash2 } from 'lucide-react';
import EntityLink from '@/Components/EntityLink';
import { cartTotals, fmt, stockState, TONES } from '@/Components/Pos/posMath';

const cell = 'w-full rounded border border-slate-300 px-2 py-1 text-right tabular-nums';

/**
 * The line-entry table shared by quote and order capture: qty steppers,
 * price and discount inputs, stock context per line and live VAT totals
 * (identical maths to the till, so the on-screen total is what posts).
 */
export default function LinesEditor({ lines, setLines, vatRate, showStock = true, priceEditable = true, emptyHint = 'Add parts to build the document.' }) {
    const setLine = (i, field, value) => setLines((prev) => prev.map((l, idx) => (idx === i ? { ...l, [field]: value } : l)));
    const bump = (i, delta) => setLines((prev) => prev.map((l, idx) => (idx === i ? { ...l, qty: Math.max(0.01, Math.round((l.qty + delta) * 100) / 100) } : l)));
    const remove = (i) => setLines((prev) => prev.filter((_, idx) => idx !== i));
    const totals = cartTotals(lines, vatRate);

    if (lines.length === 0) {
        return <div className="flex h-48 items-center justify-center rounded-xl border border-slate-100 bg-white text-sm text-slate-400">{emptyHint}</div>;
    }

    return (
        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
            <table className="w-full text-sm">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                        <th className="px-3 py-2">Part</th>
                        <th className="px-2 py-2 text-right w-32">Qty</th>
                        <th className="px-2 py-2 text-right w-24">Price</th>
                        <th className="px-2 py-2 text-right w-20">Disc %</th>
                        <th className="px-3 py-2 text-right w-24">Total</th>
                        <th className="w-9"></th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                    {lines.map((l, i) => {
                        const stock = showStock ? stockState({ available: l.available ?? l.on_hand ?? 1 }, true) : null;
                        const short = showStock && l.available != null && l.qty > l.available;
                        return (
                            <tr key={l.part_id} className={short ? 'bg-amber-50/50' : ''}>
                                <td className="px-3 py-2">
                                    <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" />
                                    <span className="block text-xs text-slate-400 truncate max-w-[18rem]">{l.description}</span>
                                    {showStock && (
                                        <span className="mt-0.5 inline-flex items-center gap-1.5 text-[11px]">
                                            {l.bin && <span className="text-slate-400">Bin {l.bin}</span>}
                                            <span className={`rounded px-1 py-px font-medium ring-1 ring-inset ${TONES[stock.tone]}`}>{stock.label}</span>
                                            {short && <span className={`rounded px-1 py-px font-medium ring-1 ring-inset ${TONES.amber}`}>{fmt(l.qty - l.available).replace('.00', '')} short</span>}
                                        </span>
                                    )}
                                </td>
                                <td className="px-2 py-2">
                                    <div className="flex items-center justify-end gap-1">
                                        <button type="button" onClick={() => bump(i, -1)} aria-label="Decrease" className="rounded border border-slate-300 p-1 text-slate-500 hover:bg-slate-50"><Minus className="h-3 w-3" /></button>
                                        <input type="number" min="0.01" step="0.01" value={l.qty} aria-label="Qty"
                                            onChange={(e) => setLine(i, 'qty', Math.max(0.01, Number(e.target.value) || 0))}
                                            className={`${cell} w-16`} />
                                        <button type="button" onClick={() => bump(i, 1)} aria-label="Increase" className="rounded border border-slate-300 p-1 text-slate-500 hover:bg-slate-50"><Plus className="h-3 w-3" /></button>
                                    </div>
                                </td>
                                <td className="px-2 py-2">
                                    <input type="number" min="0" step="0.01" value={l.unit_price} aria-label="Price" disabled={!priceEditable}
                                        onChange={(e) => setLine(i, 'unit_price', Math.max(0, Number(e.target.value) || 0))}
                                        className={`${cell} disabled:bg-slate-50 disabled:text-slate-500`} />
                                </td>
                                <td className="px-2 py-2">
                                    <input type="number" min="0" max="100" step="0.5" value={l.discount_pct} aria-label="Discount"
                                        onChange={(e) => setLine(i, 'discount_pct', Math.min(100, Math.max(0, Number(e.target.value) || 0)))}
                                        className={cell} />
                                </td>
                                <td className="px-3 py-2 text-right tabular-nums font-medium">{fmt(totals.calc[i].incl)}</td>
                                <td className="px-2 py-2 text-right">
                                    <button type="button" onClick={() => remove(i)} aria-label="Remove" className="p-1 text-slate-300 hover:text-red-600">
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>

            <div className="flex items-center justify-between gap-6 border-t border-slate-100 bg-slate-50/60 px-4 py-3">
                <span className="text-xs text-slate-400">{totals.items} unit{totals.items === 1 ? '' : 's'} · prices excl. VAT</span>
                <dl className="flex items-center gap-6 text-sm">
                    <div className="flex items-baseline gap-2 text-slate-500"><dt>Subtotal</dt><dd className="tabular-nums font-medium">{fmt(totals.subtotal)}</dd></div>
                    {totals.discount > 0 && <div className="flex items-baseline gap-2 text-emerald-700"><dt>Saved</dt><dd className="tabular-nums font-medium">−{fmt(totals.discount)}</dd></div>}
                    <div className="flex items-baseline gap-2 text-slate-500"><dt>VAT</dt><dd className="tabular-nums font-medium">{fmt(totals.vat)}</dd></div>
                    <div className="flex items-baseline gap-2 font-bold text-slate-900"><dt>Total</dt><dd className="tabular-nums text-base">{fmt(totals.total)}</dd></div>
                </dl>
            </div>
        </div>
    );
}
