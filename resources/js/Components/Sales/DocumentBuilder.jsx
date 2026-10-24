import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import CustomerPicker from '@/Components/Pos/CustomerPicker';
import PartPicker from '@/Components/Sales/PartPicker';
import LinesEditor from '@/Components/Sales/LinesEditor';
import { cartTotals, getJson } from '@/Components/Pos/posMath';

const read = (key) => { try { return JSON.parse(localStorage.getItem(key)); } catch { return null; } };

/**
 * Line-entry screen shared by quotation and order capture: till-grade part
 * search, customer-priced lines that re-resolve when the customer changes,
 * crash-safe draft, one-shot submit. The page supplies submit route and
 * the sidebar note describing what the document does to stock.
 */
export default function DocumentBuilder({ walkIn, vatRate, title, breadcrumbs, submitTo, draftKey, submitLabel, sideNote, emptyHint }) {
    const [customer, setCustomer] = useState(() => read(draftKey)?.customer ?? { ...walkIn, is_walk_in: true });
    const [lines, setLines] = useState(() => read(draftKey)?.lines ?? []);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        try { localStorage.setItem(draftKey, JSON.stringify({ customer, lines })); } catch { /* storage unavailable */ }
    }, [customer, lines]);

    // The customer's price list governs every line — re-resolve on change.
    async function changeCustomer(next) {
        setCustomer(next);
        const current = await Promise.all(lines.map(async (l) => {
            try {
                const { parts } = await getJson('sales.pos.part-lookup', { q: l.part_number, customer_id: next.id, exact: 1 });
                const p = parts?.[0];
                return p?.priced ? { part_id: l.part_id, price: p.price } : null;
            } catch { return null; }
        }));
        const map = Object.fromEntries(current.filter(Boolean).map((r) => [r.part_id, r.price]));
        setLines((prev) => prev.map((l) => ({ ...l, unit_price: map[l.part_id] ?? l.unit_price })));
    }

    function addPart(part, qty = 1) {
        setLines((prev) => prev.some((l) => l.part_id === part.id)
            ? prev.map((l) => (l.part_id === part.id ? { ...l, qty: Math.round((l.qty + qty) * 100) / 100 } : l))
            : [...prev, {
                part_id: part.id, part_number: part.part_number, description: part.description,
                qty, unit_price: part.price, discount_pct: 0,
                available: part.available, on_hand: part.on_hand, bin: part.bin, category: part.category,
            }]);
    }

    const totals = cartTotals(lines, vatRate);

    function submit() {
        setProcessing(true); setError(null);
        router.post(submitTo, {
            customer_id: customer.id,
            lines: lines.map((l) => ({ part_id: l.part_id, qty: l.qty, unit_price: l.unit_price, discount_pct: l.discount_pct })),
        }, {
            onSuccess: () => { try { localStorage.removeItem(draftKey); } catch { /* noop */ } },
            onError: (e) => { setError(Object.values(e)[0]); setProcessing(false); },
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <ModuleLayout navConfig={navConfig} title={title} breadcrumbs={breadcrumbs}>
            <div
                className="grid grid-cols-1 lg:grid-cols-3 gap-6"
                onKeyDown={(e) => { if (e.key === 'Enter' && e.ctrlKey) { e.preventDefault(); if (lines.length) submit(); } }}
            >
                <div className="lg:col-span-2 space-y-4">
                    <PartPicker customerId={customer.id} vatRate={vatRate} onPick={addPart} autoFocus
                        placeholder='Search or scan — e.g. "hilux brake pad", or 3*AF213' />
                    {error && <div role="alert" className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}
                    <LinesEditor lines={lines} setLines={setLines} vatRate={vatRate} emptyHint={emptyHint} />
                </div>

                <div className="space-y-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <CustomerPicker customer={customer} walkIn={walkIn} onChange={changeCustomer} />
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-3">
                        {sideNote}
                        <div className="flex justify-between text-sm text-slate-500"><span>Subtotal (excl)</span><span className="tabular-nums">{totals.subtotal.toFixed(2)}</span></div>
                        <div className="flex justify-between text-sm text-slate-500"><span>VAT</span><span className="tabular-nums">{totals.vat.toFixed(2)}</span></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-900"><span>Total</span><span className="tabular-nums"><MoneyDisplay amount={totals.total} /></span></div>
                        <button type="button" disabled={processing || lines.length === 0} onClick={submit}
                            className="mt-2 w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            {processing ? 'Saving…' : submitLabel}
                        </button>
                        <p className="text-center text-[11px] text-slate-400">Ctrl+Enter to save</p>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
