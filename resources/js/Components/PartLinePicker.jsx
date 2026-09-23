import { useEffect, useRef, useState } from 'react';
import { Search } from 'lucide-react';

/**
 * Shared part search → pick for document lines (PO, returns, adjustments,
 * transfers). Debounced lookup against purchasing.orders.part-lookup;
 * Enter picks the highlighted result (scanner-friendly, ui-rules §2).
 */
export default function PartLinePicker({ supplierId = null, onPick, placeholder = 'Search part to add…' }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [highlight, setHighlight] = useState(0);
    const timer = useRef(null);
    const inputRef = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) {
            setResults([]);
            return;
        }
        timer.current = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ q: query });
                if (supplierId) params.set('supplier_id', supplierId);
                const res = await fetch(route('purchasing.orders.part-lookup') + '?' + params, {
                    headers: { Accept: 'application/json' },
                });
                const json = await res.json();
                setResults(json.parts ?? []);
                setHighlight(0);
            } catch {
                /* offline — keep prior results */
            }
        }, 250);
        return () => clearTimeout(timer.current);
    }, [query, supplierId]);

    function pick(part) {
        onPick(part);
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
            <input
                ref={inputRef}
                type="text"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onKeyDown={onKey}
                placeholder={placeholder}
                aria-label="Search part"
                className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500"
            />
            {results.length > 0 && (
                <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                    {results.map((p, i) => (
                        <li key={p.id}>
                            <button
                                type="button"
                                onClick={() => pick(p)}
                                onMouseEnter={() => setHighlight(i)}
                                className={`w-full flex items-center gap-3 px-3 py-2 text-sm text-left ${
                                    highlight === i ? 'bg-orange-50' : ''
                                }`}
                            >
                                <span className="font-mono font-semibold text-slate-800">{p.part_number}</span>
                                <span className="flex-1 truncate text-slate-500">{p.description}</span>
                                <span className="text-xs text-slate-400 tabular-nums">
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
