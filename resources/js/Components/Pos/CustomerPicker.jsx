import { useEffect, useRef, useState } from 'react';
import { Search, UserRound, X, ShieldAlert } from 'lucide-react';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { fmt, getJson } from './posMath';

/** Current customer (default Cash Customer) — F2 to change; trade customers switch price list and enable account tender. */
export default function CustomerPicker({ customer, walkIn, onChange, openRequest = 0 }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [highlight, setHighlight] = useState(0);
    const timer = useRef(null);

    useEffect(() => { if (openRequest > 0) setOpen(true); }, [openRequest]);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try { setResults((await getJson('sales.pos.customer-lookup', { q: query })).customers ?? []); setHighlight(0); } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    const pick = (c) => { onChange(c); setOpen(false); setQuery(''); setResults([]); };

    function onKey(e) {
        if (e.key === 'Escape') { e.stopPropagation(); setOpen(false); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); setHighlight((h) => Math.min(h + 1, results.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setHighlight((h) => Math.max(h - 1, 0)); }
        else if (e.key === 'Enter' && results[highlight]) { e.preventDefault(); pick(results[highlight]); }
    }

    return (
        <div className="relative">
            <div className="flex items-center gap-2.5">
                <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${customer.is_walk_in ? 'bg-slate-100 text-slate-500' : 'bg-orange-100 text-orange-700'}`}>
                    <UserRound className="h-4 w-4" />
                </div>
                <div className="min-w-0 flex-1">
                    {customer.is_walk_in ? (
                        <div className="text-sm font-semibold text-slate-800 truncate">{customer.name}</div>
                    ) : (
                        <a href={route('customers.show', customer.id)} target="_blank" rel="noreferrer" className="block text-sm font-semibold text-slate-800 truncate hover:text-orange-600">{customer.name}</a>
                    )}
                    <div className="text-xs text-slate-400 truncate">
                        {customer.customer_number}
                        {!customer.is_walk_in && <> · available <span className={(customer.available_credit ?? 0) <= 0 ? 'text-red-600' : 'text-emerald-600'}>{fmt(customer.available_credit)}</span></>}
                    </div>
                </div>
                {!customer.is_walk_in && (
                    <button onClick={() => onChange({ ...walkIn, is_walk_in: true })} aria-label="Back to Cash Customer" title="Back to Cash Customer" className="p-1 text-slate-300 hover:text-slate-600"><X className="h-4 w-4" /></button>
                )}
                <button onClick={() => setOpen((o) => !o)} className="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    Change <kbd className="ml-1 font-mono text-[10px] text-slate-400">F2</kbd>
                </button>
            </div>

            {!customer.is_walk_in && (
                <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <span className="text-slate-500">Balance <strong className="tabular-nums text-slate-700"><MoneyDisplay amount={customer.ar_balance ?? 0} /></strong></span>
                    {customer.on_hold && <span className="inline-flex items-center gap-1 font-medium text-amber-700"><ShieldAlert className="h-3.5 w-3.5" /> On credit hold — no account sales</span>}
                    <a href={route('customers.statement', customer.id)} target="_blank" rel="noreferrer" className="font-medium text-orange-600 hover:text-orange-700">Statement</a>
                    <a href={route('finance.receipts.index')} target="_blank" rel="noreferrer" className="font-medium text-orange-600 hover:text-orange-700">Take a payment</a>
                    <a href={route('sales.laybys.index')} target="_blank" rel="noreferrer" className="font-medium text-orange-600 hover:text-orange-700">Lay-by</a>
                </div>
            )}

            {open && (
                <div className="absolute left-0 right-0 top-full z-30 mt-2 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                    <div className="relative">
                        <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input autoFocus value={query} onChange={(e) => setQuery(e.target.value)} onKeyDown={onKey}
                            placeholder="Name, account number or phone…" aria-label="Search customer"
                            className="w-full rounded-lg border border-slate-300 py-2 pl-8 pr-3 text-sm" />
                    </div>
                    {results.length > 0 ? (
                        <ul className="mt-1 max-h-72 overflow-y-auto">
                            {results.map((c, i) => (
                                <li key={c.id}>
                                    <button type="button" onClick={() => pick(c)} onMouseEnter={() => setHighlight(i)}
                                        className={`w-full rounded-md px-3 py-2 text-left text-sm ${i === highlight ? 'bg-orange-50' : ''}`}>
                                        <span className="font-medium text-slate-800">{c.name}</span>
                                        <span className="block text-xs text-slate-400">
                                            {c.customer_number}{c.on_hold ? ' · on hold' : ''}{!c.is_walk_in && ` · available ${fmt(c.available_credit)}`}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="px-2 py-2 text-xs text-slate-400">{query.trim().length < 2 ? 'Type at least 2 characters.' : 'No customer found.'}</p>
                    )}
                </div>
            )}
        </div>
    );
}
