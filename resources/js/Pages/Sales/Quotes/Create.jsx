import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Search, Trash2, UserRound, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';

const money = (n) => Number(n || 0).toFixed(2);

async function getJson(routeName, q, extra = {}) {
    const res = await fetch(route(routeName) + '?' + new URLSearchParams({ q, ...extra }), { headers: { Accept: 'application/json' } });
    return res.json();
}

export default function QuoteCreate({ walkIn }) {
    const [customer, setCustomer] = useState({ ...walkIn, is_walk_in: true });
    const [lines, setLines] = useState([]);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [custOpen, setCustOpen] = useState(false);
    const [custQuery, setCustQuery] = useState('');
    const [custResults, setCustResults] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);
    const timer = useRef(null);
    const ctimer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try { setResults((await getJson('sales.pos.part-lookup', query, { customer_id: customer.id })).parts ?? []); } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query, customer.id]);

    useEffect(() => {
        clearTimeout(ctimer.current);
        if (custQuery.trim().length < 2) { setCustResults([]); return; }
        ctimer.current = setTimeout(async () => {
            try { setCustResults((await getJson('sales.pos.customer-lookup', custQuery)).customers ?? []); } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(ctimer.current);
    }, [custQuery]);

    function addPart(p) {
        setLines((prev) => prev.some((l) => l.part_id === p.id)
            ? prev.map((l) => (l.part_id === p.id ? { ...l, qty: l.qty + 1 } : l))
            : [...prev, { part_id: p.id, part_number: p.part_number, description: p.description, qty: 1, unit_price: p.price, discount_pct: 0 }]);
        setQuery(''); setResults([]);
    }
    const setLine = (i, f, v) => setLines((prev) => prev.map((l, idx) => (idx === i ? { ...l, [f]: v } : l)));

    const calc = lines.map((l) => {
        const excl = Math.round((l.qty * l.unit_price * (1 - (l.discount_pct || 0) / 100)) * 100) / 100;
        return { excl, incl: Math.round(excl * 1.15 * 100) / 100 };
    });
    const subtotal = calc.reduce((n, c) => n + c.excl, 0);

    function submit() {
        setProcessing(true); setError(null);
        router.post(route('sales.quotes.store'), {
            customer_id: customer.id,
            lines: lines.map((l) => ({ part_id: l.part_id, qty: l.qty, unit_price: l.unit_price, discount_pct: l.discount_pct })),
        }, {
            onError: (e) => { setError(Object.values(e)[0]); setProcessing(false); },
            onFinish: () => setProcessing(false),
        });
    }

    const cell = 'w-full rounded border border-slate-300 px-2 py-1 text-right tabular-nums';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Quotation"
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Quotations', href: route('sales.quotes.index') },
                { label: 'New Quote' },
            ]}
        >
            <Head title="New Quotation" />

            {error && <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-4">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search part to add…" aria-label="Search part"
                            className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" />
                        {results.length > 0 && (
                            <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                {results.map((p) => (
                                    <li key={p.id}>
                                        <button type="button" onClick={() => addPart(p)} className="w-full flex items-center gap-3 px-3 py-2 text-sm text-left hover:bg-orange-50">
                                            <span className="font-mono font-semibold text-slate-800">{p.part_number}</span>
                                            <span className="flex-1 truncate text-slate-500">{p.description}</span>
                                            <span className="text-xs tabular-nums text-slate-400">{p.priced ? money(p.price) : 'no price'}</span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden min-h-[12rem]">
                        {lines.length === 0 ? (
                            <div className="flex h-48 items-center justify-center text-sm text-slate-400">Add parts to build the quote.</div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                        <th className="px-3 py-2">Part</th>
                                        <th className="px-2 py-2 text-right w-20">Qty</th>
                                        <th className="px-2 py-2 text-right w-24">Price</th>
                                        <th className="px-2 py-2 text-right w-20">Disc %</th>
                                        <th className="px-3 py-2 text-right w-24">Total</th>
                                        <th className="w-9"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {lines.map((l, i) => (
                                        <tr key={l.part_id}>
                                            <td className="px-3 py-2">
                                                <span className="font-mono font-semibold text-slate-800">{l.part_number}</span>
                                                <span className="block text-xs text-slate-400 truncate max-w-[16rem]">{l.description}</span>
                                            </td>
                                            <td className="px-2 py-2"><input type="number" min="0.01" step="0.01" value={l.qty} aria-label="Qty" onChange={(e) => setLine(i, 'qty', Number(e.target.value) || 0)} className={cell} /></td>
                                            <td className="px-2 py-2"><input type="number" min="0" step="0.01" value={l.unit_price} aria-label="Price" onChange={(e) => setLine(i, 'unit_price', Number(e.target.value) || 0)} className={cell} /></td>
                                            <td className="px-2 py-2"><input type="number" min="0" max="100" step="0.5" value={l.discount_pct} aria-label="Discount" onChange={(e) => setLine(i, 'discount_pct', Number(e.target.value) || 0)} className={cell} /></td>
                                            <td className="px-3 py-2 text-right tabular-nums font-medium">{money(calc[i].excl)}</td>
                                            <td className="px-2 py-2 text-right">
                                                <button type="button" onClick={() => setLines((prev) => prev.filter((_, idx) => idx !== i))} aria-label="Remove" className="p-1 text-slate-300 hover:text-red-600">
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

                <div className="space-y-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 min-w-0">
                                <UserRound className="w-4 h-4 text-slate-400" />
                                <div className="min-w-0">
                                    <div className="text-sm font-semibold text-slate-800 truncate">{customer.name}</div>
                                    <div className="text-xs text-slate-400">{customer.customer_number}</div>
                                </div>
                            </div>
                            {!customer.is_walk_in && <button onClick={() => setCustomer({ ...walkIn, is_walk_in: true })} aria-label="Reset" className="p-1 text-slate-300 hover:text-slate-600"><X className="w-4 h-4" /></button>}
                        </div>
                        {custOpen ? (
                            <div className="mt-3">
                                <input autoFocus value={custQuery} onChange={(e) => setCustQuery(e.target.value)} placeholder="Search customer…" aria-label="Search customer" className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
                                {custResults.length > 0 && (
                                    <ul className="mt-1 rounded-lg border border-slate-100 overflow-hidden">
                                        {custResults.map((c) => (
                                            <li key={c.id}>
                                                <button type="button" onClick={() => { setCustomer(c); setCustOpen(false); setCustQuery(''); setCustResults([]); }} className="w-full px-3 py-2 text-left text-sm hover:bg-orange-50">
                                                    {c.name} <span className="text-xs text-slate-400">{c.customer_number}</span>
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <button onClick={() => setCustOpen(false)} className="mt-2 text-xs text-slate-400 hover:text-slate-600">Close</button>
                            </div>
                        ) : (
                            <button onClick={() => setCustOpen(true)} className="mt-2 text-sm font-medium text-orange-600 hover:text-orange-700">Change customer →</button>
                        )}
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-2">
                        <div className="flex justify-between text-sm text-slate-500"><span>Subtotal (excl)</span><span className="tabular-nums">{money(subtotal)}</span></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 font-bold text-slate-900"><span>Est. incl VAT</span><span className="tabular-nums"><MoneyDisplay amount={subtotal * 1.15} /></span></div>
                        <button type="button" disabled={processing || lines.length === 0} onClick={submit}
                            className="mt-2 w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            {processing ? 'Saving…' : 'Save Quotation'}
                        </button>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
