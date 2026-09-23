import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Clock, CornerDownLeft, FileText, Search, User } from 'lucide-react';

/**
 * Ctrl+K command palette (docs/design/global-search-and-quick-actions.md).
 * Phase 0 sources: pages + users; entity sources plug in as modules land.
 * Recents: ids-only in localStorage, re-labelled from live results.
 */

const RECENTS_KEY = 'sparespro.palette.recents';

function loadRecents() {
    try {
        return JSON.parse(localStorage.getItem(RECENTS_KEY) ?? '[]');
    } catch {
        return [];
    }
}

function saveRecent(item) {
    try {
        const list = loadRecents().filter((r) => r.url !== item.url);
        list.unshift({ label: item.label, url: item.url, type: item.type });
        localStorage.setItem(RECENTS_KEY, JSON.stringify(list.slice(0, 10)));
    } catch {
        /* private mode etc. — recents are a convenience only */
    }
}

export default function CommandPalette() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [groups, setGroups] = useState([]);
    const [highlight, setHighlight] = useState(0);
    const inputRef = useRef(null);
    const timer = useRef(null);
    const abortRef = useRef(null);

    // Global hotkey.
    useEffect(() => {
        function onKey(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setOpen((o) => !o);
            }
            if (e.key === 'Escape') setOpen(false);
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    useEffect(() => {
        if (open) {
            setQuery('');
            setGroups([]);
            setHighlight(0);
            setTimeout(() => inputRef.current?.focus(), 10);
        }
    }, [open]);

    // Debounced fetch.
    useEffect(() => {
        if (!open) return;
        clearTimeout(timer.current);
        if (!query.trim()) {
            setGroups([]);
            return;
        }
        timer.current = setTimeout(async () => {
            abortRef.current?.abort();
            abortRef.current = new AbortController();
            try {
                const res = await fetch(`/search?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'application/json' },
                    signal: abortRef.current.signal,
                });
                const json = await res.json();
                setGroups(json.groups ?? []);
                setHighlight(0);
            } catch {
                /* aborted or offline — keep prior results */
            }
        }, 250);
        return () => clearTimeout(timer.current);
    }, [query, open]);

    const recents = useMemo(() => (query.trim() ? [] : loadRecents()), [open, query]);

    const flat = useMemo(() => {
        const items = [];
        if (recents.length) {
            for (const r of recents.slice(0, 5)) items.push({ ...r, _recent: true });
        }
        for (const g of groups) for (const item of g.items) items.push(item);
        return items;
    }, [groups, recents]);

    const go = useCallback((item, newTab = false) => {
        saveRecent(item);
        setOpen(false);
        if (newTab) window.open(item.url, '_blank', 'noopener');
        else router.visit(item.url);
    }, []);

    function onInputKey(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlight((h) => Math.min(h + 1, flat.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlight((h) => Math.max(h - 1, 0));
        } else if (e.key === 'Enter' && flat[highlight]) {
            e.preventDefault();
            go(flat[highlight], e.ctrlKey || e.metaKey);
        }
    }

    if (!open) return null;

    let flatIndex = -1;

    return (
        <div className="fixed inset-0 z-[70] flex items-start justify-center pt-[12vh] px-4" role="dialog" aria-modal="true" aria-label="Search">
            <div className="absolute inset-0 bg-slate-900/40" onClick={() => setOpen(false)} />
            <div className="relative w-full max-w-xl rounded-xl bg-white shadow-2xl overflow-hidden">
                <div className="flex items-center gap-3 border-b border-slate-100 px-4">
                    <Search className="w-4.5 h-4.5 text-slate-400" style={{ width: 18, height: 18 }} />
                    <input
                        ref={inputRef}
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onKeyDown={onInputKey}
                        placeholder="Search pages, settings… (try 'currencies' — or > for pages only)"
                        className="flex-1 py-3.5 text-sm outline-none placeholder:text-slate-400"
                        aria-label="Search"
                    />
                    <kbd className="text-[10px] text-slate-300">Esc</kbd>
                </div>

                <div className="max-h-[50vh] overflow-y-auto py-2">
                    {recents.length > 0 && !query.trim() && (
                        <>
                            <div className="px-4 pt-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                Recent
                            </div>
                            {recents.slice(0, 5).map((r) => {
                                flatIndex += 1;
                                const idx = flatIndex;
                                return (
                                    <button
                                        key={r.url}
                                        onClick={(e) => go(r, e.ctrlKey)}
                                        onMouseEnter={() => setHighlight(idx)}
                                        className={`w-full flex items-center gap-3 px-4 py-2 text-sm text-left ${
                                            highlight === idx ? 'bg-orange-50 text-orange-800' : 'text-slate-600'
                                        }`}
                                    >
                                        <Clock className="w-4 h-4 text-slate-300" />
                                        {r.label}
                                    </button>
                                );
                            })}
                        </>
                    )}

                    {groups.map((group) => (
                        <div key={group.group}>
                            <div className="px-4 pt-2 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                {group.group}
                            </div>
                            {group.items.map((item) => {
                                flatIndex += 1;
                                const idx = flatIndex;
                                const Icon = item.type === 'user' ? User : FileText;
                                return (
                                    <button
                                        key={item.url}
                                        onClick={(e) => go(item, e.ctrlKey)}
                                        onMouseEnter={() => setHighlight(idx)}
                                        className={`w-full flex items-center gap-3 px-4 py-2 text-sm text-left ${
                                            highlight === idx ? 'bg-orange-50 text-orange-800' : 'text-slate-700'
                                        }`}
                                    >
                                        <Icon className="w-4 h-4 text-slate-300 shrink-0" />
                                        <span className="flex-1 truncate">{item.label}</span>
                                        {item.hint && <span className="text-xs text-slate-400">{item.hint}</span>}
                                        {highlight === idx && <CornerDownLeft className="w-3.5 h-3.5 text-orange-400" />}
                                    </button>
                                );
                            })}
                        </div>
                    ))}

                    {query.trim() && groups.length === 0 && (
                        <p className="px-4 py-6 text-center text-sm text-slate-400">
                            Nothing matches "{query}" yet.
                        </p>
                    )}
                    {!query.trim() && recents.length === 0 && (
                        <p className="px-4 py-6 text-center text-sm text-slate-400">
                            Type to search pages and settings. Parts, customers and documents join as their modules go live.
                        </p>
                    )}
                </div>

                <div className="border-t border-slate-100 px-4 py-2 text-[10px] text-slate-400 flex gap-4">
                    <span>↑↓ navigate</span>
                    <span>Enter open</span>
                    <span>Ctrl+Enter new tab</span>
                </div>
            </div>
        </div>
    );
}
