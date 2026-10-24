import { useEffect, useRef, useState } from 'react';
import { Search, MapPin, Loader2 } from 'lucide-react';
import { fmt, inclPrice, stockState, TONES, getJson, parseQuery } from '@/Components/Pos/posMath';

/**
 * POS-grade part search for document builders (quotes, orders): the same
 * lookup, stock badges and pricing display as the till, plus `qty*CODE`
 * shorthand on Enter. onPick(part, qty) — the caller owns the lines.
 */
export default function PartPicker({ customerId, vatRate, onPick, placeholder = 'Search part — vehicle + type, number or OEM…', autoFocus = false, disabled = false }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [highlight, setHighlight] = useState(0);
    const [loading, setLoading] = useState(false);
    const timer = useRef(null);
    const boxRef = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); setLoading(false); return; }
        setLoading(true);
        timer.current = setTimeout(async () => {
            try {
                const { parts } = await getJson('sales.pos.part-lookup', { q: query, customer_id: customerId ?? '' });
                setResults(parts ?? []);
                setHighlight(0);
            } catch { /* keep last */ }
            setLoading(false);
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query, customerId]);

    async function onEnter() {
        const { qty, term } = parseQuery(query);
        if (qty > 1 || results.length === 0) {
            try {
                const { parts } = await getJson('sales.pos.part-lookup', { q: term, customer_id: customerId ?? '', exact: qty > 1 ? 1 : 0 });
                const part = parts?.[0];
                if (part && part.priced) { pick(part, qty); return; }
            } catch { /* fall through */ }
        }
        if (results[highlight]) pick(results[highlight], qty);
    }

    function pick(part, qty = 1) {
        onPick(part, qty);
        setQuery(''); setResults([]); boxRef.current?.focus();
    }

    return (
        <div className="relative">
            <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                {loading && <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-slate-300" />}
                <input
                    ref={boxRef}
                    autoFocus={autoFocus}
                    disabled={disabled}
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'ArrowDown') { e.preventDefault(); setHighlight((h) => Math.min(h + 1, results.length - 1)); }
                        else if (e.key === 'ArrowUp') { e.preventDefault(); setHighlight((h) => Math.max(h - 1, 0)); }
                        else if (e.key === 'Enter') { e.preventDefault(); onEnter(); }
                        else if (e.key === 'Escape') { setQuery(''); setResults([]); }
                    }}
                    placeholder={placeholder}
                    aria-label="Search parts"
                    className="w-full rounded-lg border border-slate-300 pl-9 pr-9 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500"
                />
            </div>

            {results.length > 0 && (
                <ul role="listbox" className="absolute z-30 mt-1 max-h-80 w-full overflow-y-auto rounded-xl bg-white shadow-xl border border-slate-100 divide-y divide-slate-50">
                    {results.map((p, i) => {
                        const stock = stockState(p, true);
                        return (
                            <li key={p.id} role="option" aria-selected={i === highlight}>
                                <button type="button" onMouseEnter={() => setHighlight(i)} onClick={() => pick(p)}
                                    className={`w-full flex items-center gap-3 px-3 py-2.5 text-left ${i === highlight ? 'bg-orange-50' : ''} ${!p.priced ? 'opacity-60' : ''}`}>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-mono text-sm font-bold text-slate-900">{p.part_number}</span>
                                            {p.category && <span className={`rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset ${TONES.slate}`}>{p.category}</span>}
                                            {p.discontinued && <span className={`rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset ${TONES.amber}`}>Discontinued</span>}
                                        </div>
                                        <div className="truncate text-xs text-slate-500">{p.description}</div>
                                        <div className="mt-0.5 flex items-center gap-3 text-[11px] text-slate-400">
                                            {p.bin && <span className="inline-flex items-center gap-0.5"><MapPin className="h-3 w-3" />{p.bin}</span>}
                                            <span className={stock.tone === 'green' ? 'text-emerald-600' : stock.tone === 'amber' ? 'text-amber-600' : 'text-red-600'}>{stock.label}</span>
                                            {!p.priced && <span className="font-medium text-red-500">no price</span>}
                                        </div>
                                    </div>
                                    <span className="shrink-0 text-sm font-bold tabular-nums text-slate-900">{p.priced ? fmt(inclPrice(p.price, vatRate)) : '—'}</span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
