import { useEffect, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Search, Trash2, ScanBarcode, UserRound, X, AlertTriangle, Banknote } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';

const money = (n) => Number(n || 0).toFixed(2);

/** Debounced JSON GET against a Ziggy route. */
function useLookup(routeName, extra = {}) {
    return async (q) => {
        const params = new URLSearchParams({ q, ...extra });
        const res = await fetch(route(routeName) + '?' + params, { headers: { Accept: 'application/json' } });
        return res.json();
    };
}

export default function PosTerminal({ branch, walkIn, maxDiscount, vatRate }) {
    const { errors } = usePage().props;
    const [customer, setCustomer] = useState({ ...walkIn, is_walk_in: true });
    const [lines, setLines] = useState([]);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [highlight, setHighlight] = useState(0);
    const [tenderOpen, setTenderOpen] = useState(false);
    const scanRef = useRef(null);
    const timer = useRef(null);
    const partLookup = useLookup('sales.pos.part-lookup', { customer_id: customer.id });

    useEffect(() => { scanRef.current?.focus(); }, []);

    // Debounced part search.
    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try {
                const json = await partLookup(query);
                setResults(json.parts ?? []);
                setHighlight(0);
            } catch { /* offline — keep prior */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query, customer.id]);

    function addPart(part) {
        setLines((prev) => {
            const existing = prev.find((l) => l.part_id === part.id);
            if (existing) {
                return prev.map((l) => (l.part_id === part.id ? { ...l, qty: l.qty + 1 } : l));
            }
            return [...prev, {
                part_id: part.id, part_number: part.part_number, description: part.description,
                qty: 1, unit_price: part.price, discount_pct: 0, priced: part.priced, on_hand: part.on_hand,
            }];
        });
        setQuery('');
        setResults([]);
        scanRef.current?.focus();
    }

    function onKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); setHighlight((h) => Math.min(h + 1, results.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setHighlight((h) => Math.max(h - 1, 0)); }
        else if (e.key === 'Enter' && results[highlight]) { e.preventDefault(); addPart(results[highlight]); }
        else if (e.key === 'Escape') { setQuery(''); setResults([]); }
    }

    const setLine = (i, field, value) => setLines((prev) => prev.map((l, idx) => (idx === i ? { ...l, [field]: value } : l)));
    const removeLine = (i) => setLines((prev) => prev.filter((_, idx) => idx !== i));

    // Totals (mirrors PricingService::computeLine on the server).
    const calc = lines.map((l) => {
        const gross = l.qty * l.unit_price;
        const discount = Math.round((gross * (l.discount_pct || 0) / 100) * 100) / 100;
        const excl = Math.round((gross - discount) * 100) / 100;
        const vat = Math.round((excl * vatRate / 100) * 100) / 100;
        return { excl, vat, incl: Math.round((excl + vat) * 100) / 100 };
    });
    const subtotal = calc.reduce((n, c) => n + c.excl, 0);
    const vatTotal = calc.reduce((n, c) => n + c.vat, 0);
    const total = Math.round((subtotal + vatTotal) * 100) / 100;

    const hasUnpriced = lines.some((l) => !l.priced);
    const canCharge = lines.length > 0 && !hasUnpriced;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Point of Sale"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Point of Sale' }]}
        >
            <Head title="Point of Sale" />

            {(errors.sale || errors.discount || errors.lines) && (
                <div className="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                    <span>{errors.sale || errors.discount || errors.lines}</span>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Cart */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="relative">
                        <ScanBarcode className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-orange-500 pointer-events-none" />
                        <input
                            ref={scanRef}
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyDown={onKey}
                            placeholder="Scan barcode or search part number / name…"
                            aria-label="Scan or search part"
                            className="w-full rounded-xl border-2 border-slate-200 bg-white pl-11 pr-4 py-3 text-base focus:border-orange-500 focus:ring-orange-500"
                        />
                        {results.length > 0 && (
                            <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                {results.map((p, i) => (
                                    <li key={p.id}>
                                        <button
                                            type="button"
                                            onClick={() => addPart(p)}
                                            onMouseEnter={() => setHighlight(i)}
                                            className={`w-full flex items-center gap-3 px-3 py-2.5 text-sm text-left ${highlight === i ? 'bg-orange-50' : ''}`}
                                        >
                                            <span className="font-mono font-semibold text-slate-800">{p.part_number}</span>
                                            <span className="flex-1 truncate text-slate-500">{p.description}</span>
                                            <span className={`text-xs tabular-nums ${p.on_hand > 0 ? 'text-slate-400' : 'text-red-500'}`}>{money(p.on_hand)} on hand</span>
                                            <span className="w-16 text-right font-semibold tabular-nums text-slate-700">{p.priced ? money(p.price) : '—'}</span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden min-h-[16rem]">
                        {lines.length === 0 ? (
                            <div className="flex h-64 flex-col items-center justify-center text-center text-slate-400">
                                <ScanBarcode className="w-10 h-10 mb-3" />
                                <p className="text-sm">Scan or search to start a sale.</p>
                            </div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                        <th className="px-3 py-2">Part</th>
                                        <th className="px-2 py-2 text-right w-20">Qty</th>
                                        <th className="px-2 py-2 text-right w-24">Price</th>
                                        <th className="px-2 py-2 text-right w-20">Disc %</th>
                                        <th className="px-3 py-2 text-right w-28">Total</th>
                                        <th className="w-9"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {lines.map((l, i) => (
                                        <tr key={l.part_id} className={!l.priced ? 'bg-red-50' : ''}>
                                            <td className="px-3 py-2">
                                                <span className="font-mono font-semibold text-slate-800">{l.part_number}</span>
                                                <span className="block text-xs text-slate-400 truncate max-w-[16rem]">{l.description}</span>
                                                {!l.priced && <span className="text-xs text-red-600">No price — set one before selling.</span>}
                                                {l.qty > l.on_hand && <span className="text-xs text-amber-600">Only {money(l.on_hand)} in stock.</span>}
                                            </td>
                                            <td className="px-2 py-2">
                                                <input type="number" min="0.01" step="0.01" value={l.qty}
                                                    aria-label={`Qty ${l.part_number}`}
                                                    onChange={(e) => setLine(i, 'qty', Number(e.target.value) || 0)}
                                                    className="w-full rounded border border-slate-300 px-2 py-1 text-right tabular-nums" />
                                            </td>
                                            <td className="px-2 py-2 text-right tabular-nums text-slate-500">{money(l.unit_price)}</td>
                                            <td className="px-2 py-2">
                                                <input type="number" min="0" max="100" step="0.5" value={l.discount_pct}
                                                    aria-label={`Discount ${l.part_number}`}
                                                    onChange={(e) => setLine(i, 'discount_pct', Number(e.target.value) || 0)}
                                                    className={`w-full rounded border px-2 py-1 text-right tabular-nums ${l.discount_pct > maxDiscount ? 'border-amber-400 bg-amber-50' : 'border-slate-300'}`} />
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums font-medium">{money(calc[i].incl)}</td>
                                            <td className="px-2 py-2 text-right">
                                                <button type="button" onClick={() => removeLine(i)} aria-label="Remove" className="p-1 rounded text-slate-300 hover:text-red-600 hover:bg-red-50">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                {/* Sale panel */}
                <div className="space-y-4">
                    <CustomerPicker customer={customer} walkIn={walkIn} onChange={setCustomer} />

                    <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-2">
                        <div className="flex justify-between text-sm text-slate-500"><span>Subtotal (excl)</span><span className="tabular-nums">{money(subtotal)}</span></div>
                        <div className="flex justify-between text-sm text-slate-500"><span>VAT {vatRate}%</span><span className="tabular-nums">{money(vatTotal)}</span></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 text-lg font-bold text-slate-900"><span>Total</span><span className="tabular-nums"><MoneyDisplay amount={total} /></span></div>

                        <button
                            type="button"
                            disabled={!canCharge}
                            onClick={() => setTenderOpen(true)}
                            title={hasUnpriced ? 'Remove or price the flagged lines first' : undefined}
                            className="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-3 text-base font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <Banknote className="w-5 h-5" />
                            Charge {money(total)}
                        </button>
                    </div>
                </div>
            </div>

            {tenderOpen && (
                <TenderDialog
                    total={total}
                    customer={customer}
                    lines={lines}
                    maxDiscount={maxDiscount}
                    onClose={() => setTenderOpen(false)}
                />
            )}
        </ModuleLayout>
    );
}

function CustomerPicker({ customer, walkIn, onChange }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const lookup = useLookup('sales.pos.customer-lookup');
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try { setResults((await lookup(query)).customers ?? []); } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    return (
        <div className="rounded-xl border border-slate-100 bg-white p-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 min-w-0">
                    <UserRound className="w-4 h-4 text-slate-400 shrink-0" />
                    <div className="min-w-0">
                        <div className="text-sm font-semibold text-slate-800 truncate">{customer.name}</div>
                        <div className="text-xs text-slate-400">{customer.customer_number}{customer.on_hold ? ' · on hold' : ''}</div>
                    </div>
                </div>
                {!customer.is_walk_in && (
                    <button onClick={() => onChange({ ...walkIn, is_walk_in: true })} aria-label="Reset to walk-in" className="p-1 text-slate-300 hover:text-slate-600">
                        <X className="w-4 h-4" />
                    </button>
                )}
            </div>

            {open ? (
                <div className="mt-3">
                    <div className="relative">
                        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            autoFocus value={query} onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search customer…" aria-label="Search customer"
                            className="w-full rounded-lg border border-slate-300 pl-8 pr-3 py-1.5 text-sm"
                        />
                    </div>
                    {results.length > 0 && (
                        <ul className="mt-1 rounded-lg border border-slate-100 overflow-hidden">
                            {results.map((c) => (
                                <li key={c.id}>
                                    <button
                                        type="button"
                                        onClick={() => { onChange(c); setOpen(false); setQuery(''); setResults([]); }}
                                        className="w-full px-3 py-2 text-left text-sm hover:bg-orange-50"
                                    >
                                        <span className="font-medium text-slate-700">{c.name}</span>
                                        <span className="block text-xs text-slate-400">
                                            {c.customer_number}{c.on_hold ? ' · on hold' : ''}
                                            {!c.is_walk_in && ` · available ${money(c.available_credit)}`}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <button onClick={() => setOpen(false)} className="mt-2 text-xs text-slate-400 hover:text-slate-600">Close</button>
                </div>
            ) : (
                <button onClick={() => setOpen(true)} className="mt-2 text-sm font-medium text-orange-600 hover:text-orange-700">
                    Change customer →
                </button>
            )}
        </div>
    );
}

function TenderDialog({ total, customer, lines, maxDiscount, onClose }) {
    const [payments, setPayments] = useState([{ method: 'cash', amount: total, tendered: total, reference: '' }]);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    const paid = Math.round(payments.reduce((n, p) => n + Number(p.amount || 0), 0) * 100) / 100;
    const balance = Math.round((total - paid) * 100) / 100;
    const cashLine = payments.find((p) => p.method === 'cash');
    const change = cashLine && cashLine.tendered ? Math.max(0, Math.round((Number(cashLine.tendered) - Number(cashLine.amount)) * 100) / 100) : 0;
    const needsApproval = lines.some((l) => (l.discount_pct || 0) > maxDiscount);
    const accountBlocked = payments.some((p) => p.method === 'account') && (customer.is_walk_in || customer.on_hold);

    const setP = (i, field, value) => setPayments((prev) => prev.map((p, idx) => (idx === i ? { ...p, [field]: value } : p)));

    function submit() {
        if (Math.abs(balance) > 0.001) { setError('Payments must equal the total.'); return; }
        setProcessing(true);
        router.post(route('sales.pos.store'), {
            customer_id: customer.id,
            lines: lines.map((l) => ({ part_id: l.part_id, qty: l.qty, discount_pct: l.discount_pct })),
            payments: payments.map((p) => ({
                method: p.method, amount: Number(p.amount),
                tendered: p.method === 'cash' ? Number(p.tendered || p.amount) : null,
                reference: p.reference || null,
            })),
            discount_approved: needsApproval,
        }, {
            onError: (errs) => { setError(Object.values(errs)[0]); setProcessing(false); },
            onFinish: () => setProcessing(false),
        });
    }

    const method = 'w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm';

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Take payment">
            <div className="absolute inset-0 bg-slate-900/50" onClick={onClose} />
            <div className="relative w-full max-w-lg rounded-xl bg-white shadow-xl p-6 space-y-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-slate-900">Take payment</h2>
                    <span className="text-2xl font-bold tabular-nums text-slate-900">{money(total)}</span>
                </div>

                {customer.on_hold && (
                    <p className="rounded-lg bg-yellow-50 border border-yellow-200 px-3 py-2 text-xs text-yellow-800">
                        {customer.name} is on hold — account payment is blocked. Take cash or card.
                    </p>
                )}

                <div className="space-y-2">
                    {payments.map((p, i) => (
                        <div key={i} className="flex items-center gap-2">
                            <select value={p.method} onChange={(e) => setP(i, 'method', e.target.value)} className={`${method} w-28`}>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="eft">EFT</option>
                                <option value="account" disabled={customer.is_walk_in || customer.on_hold}>Account</option>
                            </select>
                            <input type="number" step="0.01" min="0" value={p.amount}
                                aria-label="Amount" onChange={(e) => setP(i, 'amount', e.target.value)}
                                className={`${method} flex-1 text-right tabular-nums`} />
                            {p.method === 'cash' ? (
                                <input type="number" step="0.01" min="0" value={p.tendered}
                                    aria-label="Cash tendered" placeholder="Tendered"
                                    onChange={(e) => setP(i, 'tendered', e.target.value)}
                                    className={`${method} w-28 text-right tabular-nums`} />
                            ) : (
                                <input value={p.reference} aria-label="Reference" placeholder="Ref"
                                    onChange={(e) => setP(i, 'reference', e.target.value)}
                                    className={`${method} w-28`} />
                            )}
                            {payments.length > 1 && (
                                <button onClick={() => setPayments((prev) => prev.filter((_, idx) => idx !== i))} aria-label="Remove payment" className="p-1 text-slate-300 hover:text-red-600">
                                    <X className="w-4 h-4" />
                                </button>
                            )}
                        </div>
                    ))}
                    <button
                        onClick={() => setPayments((prev) => [...prev, { method: 'card', amount: Math.max(0, balance), reference: '' }])}
                        className="text-xs font-medium text-orange-600 hover:text-orange-700"
                    >
                        + Split payment
                    </button>
                </div>

                <div className="rounded-lg bg-slate-50 px-4 py-3 text-sm space-y-1">
                    <div className="flex justify-between text-slate-500"><span>Paid</span><span className="tabular-nums">{money(paid)}</span></div>
                    <div className={`flex justify-between font-medium ${Math.abs(balance) > 0.001 ? 'text-amber-600' : 'text-emerald-600'}`}>
                        <span>{balance > 0 ? 'Balance due' : balance < 0 ? 'Over-tender' : 'Settled'}</span>
                        <span className="tabular-nums">{money(Math.abs(balance))}</span>
                    </div>
                    {change > 0 && <div className="flex justify-between text-slate-900 font-semibold"><span>Change</span><span className="tabular-nums">{money(change)}</span></div>}
                </div>

                {needsApproval && (
                    <p className="text-xs text-amber-600">A line discount exceeds {maxDiscount}% — posting records a supervisor override.</p>
                )}
                {error && <p className="text-sm text-red-600">{error}</p>}

                <div className="flex justify-end gap-3 pt-1">
                    <button onClick={onClose} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button
                        onClick={submit}
                        disabled={processing || Math.abs(balance) > 0.001 || accountBlocked}
                        className="rounded-lg bg-orange-600 px-6 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Posting…' : 'Complete sale'}
                    </button>
                </div>
            </div>
        </div>
    );
}
