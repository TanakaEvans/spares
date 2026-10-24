import { useEffect, useRef, useState } from 'react';
import { Search, Loader2, MapPin } from 'lucide-react';
import { parseQuery, fmt } from '@/Components/Pos/posMath';

/**
 * Shared part search → pick for document lines (PO, returns, adjustments,
 * transfers). Debounced lookup against purchasing.orders.part-lookup;
 * Enter picks the highlighted result (scanner-friendly, ui-rules §2) and
 * `qty*CODE` sets quantity on pick. `branchId` adds on-hand/bin per row.
 */
export default function PartLinePicker({ supplierId = null, branchId = null, onPick, placeholder = 'Search part to add…' }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [highlight, setHighlight] = useState(0);
    const [loading, setLoading] = useState(false);
    const timer = useRef(null);
    const inputRef = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) {
            setResults([]);
            setLoading(false);
            return;
        }
        setLoading(true);
        timer.current = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q: query });
                if (supplierId) params.set('supplier_id', supplierId);
                if (branchId) params.set('branch_id', branchId);
                const res = await fetch(route('purchasing.orders.part-lookup') + '?' + params, {
                    headers: { Accept: 'application/json' },
                });
                const json = await res.json();
                setResults(json.parts ?? []);
                setHighlight(0);
            } catch {
                /* offline — keep prior results */
            }
            setLoading(false);
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query, supplierId, branchId]);

    function pick(part) {
        const { qty } = parseQuery(query);
        onPick(part, qty);
        setQuery('');
        setResults([]);
        inputRef.current?.focus();
    }

    function onKey(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlight((h) => Math.min(h + 1, results.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlight((h) => Math.max(h - 1, 0));
        } else if (e.key === 'Enter' && results[highlight]) {
            e.preventDefault();
            pick(results[highlight]);
        } else if (e.key === 'Escape') {
            setQuery('');
            setResults([]);
        }
    }

    return (
        <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            {loading && <Loader2 className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-slate-300" />}
            <input
                ref={inputRef}
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onKeyDown={onKey}
                placeholder={placeholder}
                aria-label="Search part"
                className="w-full rounded-lg border border-slate-300 pl-9 pr-9 py-2 text-sm focus:border-orange-500 focus:ring-orange-500"
            />
            {results.length > 0 && (
                <ul role="listbox" className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-xl border border-slate-100 overflow-hidden max-h-80 overflow-y-auto">
                    {results.map((p, i) => (
                        <li key={p.id} role="option" aria-selected={highlight === i}>
                            <button
                                type="button"
                                onClick={() => pick(p)}
                                onMouseEnter={() => setHighlight(i)}
                                className={`w-full flex items-center gap-3 px-3 py-2 text-sm text-left ${
                                    highlight === i ? 'bg-orange-50' : ''
                                }`}
                            >
                                <span className="font-mono font-semibold text-slate-800">{p.part_number}</span>
                                <span className="flex-1 min-w-0 truncate text-slate-500">{p.description}</span>
                                {p.on_hand != null && (
                                    <span className={`inline-flex items-center gap-1 text-[11px] tabular-nums ${p.on_hand > 0 ? 'text-slate-400' : 'text-red-500'}`}>
                                        {p.bin && <span className="inline-flex items-center gap-0.5"><MapPin className="w-3 h-3" />{p.bin}</span>}
                                        {fmt(p.on_hand)} on hand
                                    </span>
                                )}
                                <span className="text-xs text-slate-400 tabular-nums shrink-0">
                                    {Number(p.suggested_cost).toFixed(2)} <span className="text-slate-300">({p.cost_source})</span>
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
