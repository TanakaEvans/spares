import { useEffect, useRef } from 'react';
import { MapPin, ArrowRightLeft, Flame, LayoutGrid, X, SearchX, Loader2 } from 'lucide-react';
import { fmt, inclPrice, stockState, TONES } from './posMath';

function Chip({ tone = 'slate', children }) {
    return <span className={`inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset ${TONES[tone]}`}>{children}</span>;
}

/** One sellable row: what it is, where it lives, can I sell it, what it costs (incl VAT). */
function ResultRow({ part, active, onHover, onAdd, onUseReplacement, allowNegative, vatRate }) {
    const ref = useRef(null);
    const stock = stockState(part, allowNegative);
    const blocked = stock.blocked || !part.priced;
    const reason = !part.priced ? 'No selling price on any applicable price list.' : stock.reason;

    useEffect(() => { if (active) ref.current?.scrollIntoView({ block: 'nearest' }); }, [active]);

    return (
        <div
            ref={ref}
            role="option"
            aria-selected={active}
            aria-disabled={blocked}
            title={blocked ? reason : 'Enter or click to add'}
            onMouseEnter={onHover}
            onClick={() => !blocked && onAdd(part)}
            className={`flex items-start gap-3 px-4 py-3 border-l-4 transition ${active ? 'border-orange-500 bg-orange-50/70' : 'border-transparent'} ${blocked ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-slate-50'}`}
        >
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-1.5">
                    <a href={route('inventory.parts.show', part.id)} target="_blank" rel="noreferrer" onClick={(e) => e.stopPropagation()}
                        className="font-mono text-sm font-bold text-slate-900 hover:text-orange-600">{part.part_number}</a>
                    {part.category && <Chip>{part.category}</Chip>}
                    {part.discontinued && <Chip tone="amber">Discontinued</Chip>}
                </div>
                <div className="mt-0.5 text-sm text-slate-600 line-clamp-2">{part.description}</div>
                <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                    {part.bin && <span className="inline-flex items-center gap-1 font-medium text-slate-600"><MapPin className="h-3 w-3" />Bin {part.bin}</span>}
                    {part.oem_number && <span>OEM {part.oem_number}</span>}
                    {part.superseded_by && (
                        <button type="button" onClick={(e) => { e.stopPropagation(); onUseReplacement(part.superseded_by); }}
                            className="inline-flex items-center gap-1 font-medium text-amber-700 hover:text-amber-800">
                            <ArrowRightLeft className="h-3 w-3" /> Superseded by {part.superseded_by} — add that instead
                        </button>
                    )}
                    {blocked && <span className="font-medium text-red-600">{reason}</span>}
                </div>
            </div>
            <div className="shrink-0 text-right space-y-1">
                <div className="text-base font-bold tabular-nums text-slate-900">{part.priced ? fmt(inclPrice(part.price, vatRate)) : '—'}</div>
                <Chip tone={stock.tone}>{stock.label}</Chip>
            </div>
        </div>
    );
}

export default function PartResults({
    term, results, loading, highlight, setHighlight, category, onClearCategory,
    onAdd, onUseReplacement, topSellers, categories, onBrowse, allowNegative, vatRate,
}) {
    const searching = term.length >= 2 || category;

    if (searching) {
        return (
            <div className="flex h-full flex-col">
                <div className="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                    <span className="flex items-center gap-2">
                        {loading && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                        {results.length} result{results.length === 1 ? '' : 's'}{term.length >= 2 && <> for <strong className="text-slate-700">"{term}"</strong></>}
                        {category && (
                            <button onClick={onClearCategory} className="inline-flex items-center gap-1 rounded-full bg-slate-900 px-2 py-0.5 text-white">
                                {category.name} <X className="h-3 w-3" />
                            </button>
                        )}
                    </span>
                    <span>↑↓ choose · Enter add · in-stock first</span>
                </div>
                <div role="listbox" aria-label="Matching parts" className="flex-1 overflow-y-auto divide-y divide-slate-100">
                    {results.length === 0 && !loading ? (
                        <div className="flex h-full flex-col items-center justify-center p-8 text-center text-slate-400">
                            <SearchX className="mb-3 h-10 w-10" />
                            <p className="text-sm font-medium text-slate-600">No part matches.</p>
                            <p className="mt-1 text-xs">Try fewer words, the vehicle + part type ("hilux brake pad"), the OEM number, or browse a category.</p>
                        </div>
                    ) : results.map((p, i) => (
                        <ResultRow key={p.id} part={p} active={i === highlight} onHover={() => setHighlight(i)}
                            onAdd={onAdd} onUseReplacement={onUseReplacement} allowNegative={allowNegative} vatRate={vatRate} />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="h-full overflow-y-auto p-4 space-y-6">
            {topSellers.length > 0 && (
                <section>
                    <h3 className="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500"><Flame className="h-3.5 w-3.5 text-orange-500" /> Top sellers · last 30 days</h3>
                    <div className="grid grid-cols-2 xl:grid-cols-3 gap-2">
                        {topSellers.map((p) => {
                            const stock = stockState(p, allowNegative);
                            const blocked = stock.blocked || !p.priced;
                            return (
                                <button key={p.id} type="button" disabled={blocked} title={blocked ? (!p.priced ? 'No selling price' : stock.reason) : 'Add to sale'}
                                    onClick={() => onAdd(p)}
                                    className="rounded-lg border border-slate-200 bg-white p-2.5 text-left hover:border-orange-400 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50">
                                    <div className="font-mono text-xs font-bold text-slate-900">{p.part_number}</div>
                                    <div className="mt-0.5 text-xs text-slate-500 line-clamp-2 min-h-[2rem]">{p.description}</div>
                                    <div className="mt-1 flex items-center justify-between">
                                        <span className="text-sm font-semibold tabular-nums">{p.priced ? fmt(inclPrice(p.price, vatRate)) : '—'}</span>
                                        <span className={`h-2 w-2 rounded-full ${stock.tone === 'green' ? 'bg-emerald-500' : stock.tone === 'amber' ? 'bg-amber-500' : 'bg-red-500'}`} />
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </section>
            )}

            <section>
                <h3 className="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500"><LayoutGrid className="h-3.5 w-3.5" /> Browse by category</h3>
                {categories.length === 0 ? (
                    <p className="text-sm text-slate-400">No parts loaded yet — load them under System Administration › Data Upload.</p>
                ) : (
                    <div className="grid grid-cols-2 xl:grid-cols-3 gap-2">
                        {categories.map((c) => (
                            <button key={c.id} type="button" onClick={() => onBrowse(c)}
                                className="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-left text-sm font-medium text-slate-700 hover:border-orange-400 hover:text-orange-700">
                                <span className="truncate">{c.name}</span>
                                <span className="ml-2 text-xs tabular-nums text-slate-400">{c.count.toLocaleString()}</span>
                            </button>
                        ))}
                    </div>
                )}
            </section>

            <p className="text-xs text-slate-400">
                Tip: type <kbd className="rounded border border-slate-300 bg-white px-1 font-mono">3*AF213</kbd> to sell three at once ·
                search by vehicle + part type, e.g. <em>"corolla water pump"</em>.
            </p>
        </div>
    );
}
