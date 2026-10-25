import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRightLeft, Car, Loader2, MapPin, PackageSearch, Search, SearchX, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import EmptyState from '@/Components/EmptyState';
import EntityLink from '@/Components/EntityLink';
import { fmt, getJson, inclPrice, stockState, TONES } from '@/Components/Pos/posMath';

function Chip({ tone = 'slate', children, title }) {
    return <span title={title} className={`inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset ${TONES[tone]}`}>{children}</span>;
}

const SORTS = { category: 'Category', description: 'Description', price: 'Price', stock: 'Stock here' };

/**
 * The counter's killer feature: name the vehicle, see everything that fits —
 * priced, stocked and binned exactly as the till shows it (POS standard:
 * instant filtering, category chips, keyboard ↑↓ Enter).
 */
export default function FitmentIndex({ makes, models, variants, selection, vehicleLabel, results, branch, vatRate }) {
    const go = (next) => router.get(route('vehicle-ref.fitment'), Object.fromEntries(Object.entries(next).filter(([, v]) => v)), { preserveState: true, preserveScroll: true });

    function pick(field, value) {
        const next = { ...selection, [field]: value || null };
        if (field === 'make_id') { next.model_id = null; next.variant_id = null; }
        if (field === 'model_id') next.variant_id = null;
        go(next);
    }

    const select = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-400';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Fitment Lookup"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Fitment Lookup' },
            ]}
        >
            <Head title={vehicleLabel ? `Fits ${vehicleLabel}` : 'Fitment Lookup'} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-slate-600 rounded-xl flex items-center justify-center"><Car className="w-5 h-5 text-white" /></div>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">What fits this vehicle?</h1>
                            <p className="text-sm text-slate-500">Type the vehicle (e.g. <span className="font-mono">hilux 2.8</span>, <span className="font-mono">corolla 2012</span>, <span className="font-mono">1GD</span>) or pick it below.</p>
                        </div>
                    </div>

                    <VehicleSearch onPick={(v) => go({ make_id: v.make_id, model_id: v.model_id, variant_id: v.variant_id, year: v.year })} autoFocus={!selection.make_id} />

                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label htmlFor="fit-make" className="block text-xs font-medium text-slate-500 mb-1">Make</label>
                            <select id="fit-make" value={selection.make_id ?? ''} onChange={(e) => pick('make_id', e.target.value)} className={select}>
                                <option value="">Select make…</option>
                                {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="fit-model" className="block text-xs font-medium text-slate-500 mb-1">Model</label>
                            <select id="fit-model" value={selection.model_id ?? ''} onChange={(e) => pick('model_id', e.target.value)} disabled={!selection.make_id} className={select}>
                                <option value="">All models</option>
                                {models.map((m) => <option key={m.id} value={m.id}>{m.name}{m.year_from ? ` (${m.year_from}–${m.year_to ?? ''})` : ''}</option>)}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="fit-variant" className="block text-xs font-medium text-slate-500 mb-1">Variant</label>
                            <select id="fit-variant" value={selection.variant_id ?? ''} onChange={(e) => pick('variant_id', e.target.value)} disabled={!selection.model_id} className={select}>
                                <option value="">All variants</option>
                                {variants.map((v) => <option key={v.id} value={v.id}>{v.name}{v.engine_code ? ` · ${v.engine_code}` : ''}</option>)}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="fit-year" className="block text-xs font-medium text-slate-500 mb-1">Year</label>
                            <input
                                id="fit-year" key={selection.year ?? 'none'} type="number" min="1950" max="2100" placeholder="e.g. 2018"
                                defaultValue={selection.year ?? ''}
                                onBlur={(e) => String(e.target.value) !== String(selection.year ?? '') && pick('year', e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && pick('year', e.target.value)}
                                disabled={!selection.make_id} className={select}
                            />
                        </div>
                    </div>
                </div>

                {results === null ? (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                        <EmptyState icon={Search} title="Name a vehicle to begin" message="Type it in the box above, or choose at least the make. Narrow by model, variant and year for a precise fit list." />
                    </div>
                ) : (
                    <Results results={results} vehicleLabel={vehicleLabel} branch={branch} vatRate={vatRate} onClear={() => router.get(route('vehicle-ref.fitment'))} selection={selection} />
                )}
            </div>
        </ModuleLayout>
    );
}

/** Debounced type-a-vehicle box with ↑↓ Enter suggestions. */
function VehicleSearch({ onPick, autoFocus }) {
    const [q, setQ] = useState('');
    const [hits, setHits] = useState([]);
    const [loading, setLoading] = useState(false);
    const [hi, setHi] = useState(0);
    const [open, setOpen] = useState(false);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (q.trim().length < 2) { setHits([]); setLoading(false); return; }
        setLoading(true);
        timer.current = setTimeout(async () => {
            try {
                const json = await getJson('vehicle-ref.fitment.vehicles', { q });
                setHits(json.vehicles ?? []);
                setHi(0);
                setOpen(true);
            } catch { /* keep prior suggestions */ } finally { setLoading(false); }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [q]);

    function choose(v) { setOpen(false); setQ(''); setHits([]); onPick(v); }

    function onKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); setHi((h) => Math.min(h + 1, hits.length - 1)); }
        if (e.key === 'ArrowUp') { e.preventDefault(); setHi((h) => Math.max(h - 1, 0)); }
        if (e.key === 'Enter' && hits[hi]) { e.preventDefault(); choose(hits[hi]); }
        if (e.key === 'Escape') { setOpen(false); }
    }

    return (
        <div className="relative">
            <Car className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-orange-500" />
            <input
                value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={onKey} onFocus={() => hits.length && setOpen(true)} onBlur={() => setTimeout(() => setOpen(false), 150)}
                autoFocus={autoFocus} autoComplete="off" spellCheck={false} aria-label="Type a vehicle"
                placeholder="Type a vehicle — make, model, variant, engine code or year…"
                className="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-12 pr-10 text-base shadow-sm focus:border-orange-500 focus:ring-orange-500"
            />
            {loading && <Loader2 className="absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-slate-400" />}
            {open && q.trim().length >= 2 && !loading && (
                <div role="listbox" className="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                    {hits.length === 0 ? (
                        <div className="px-4 py-3 text-sm text-slate-500">No vehicle matches “{q}”. Try fewer words, or the make name.</div>
                    ) : hits.map((v, i) => (
                        <button
                            key={`${v.make_id}-${v.model_id}-${v.variant_id}`} type="button" role="option" aria-selected={i === hi}
                            onMouseEnter={() => setHi(i)} onMouseDown={(e) => { e.preventDefault(); choose(v); }}
                            className={`flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm ${i === hi ? 'bg-orange-50' : ''}`}
                        >
                            <span className="font-medium text-slate-800">{v.label}{v.year ? ` · ${v.year}` : ''}</span>
                            <span className="text-xs text-slate-400">{v.hint}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

function Results({ results, vehicleLabel, branch, vatRate, onClear, selection }) {
    const [q, setQ] = useState('');
    const [category, setCategory] = useState('');
    const [inStock, setInStock] = useState(false);
    const [sort, setSort] = useState('category');
    const [hi, setHi] = useState(0);
    const filterRef = useRef(null);

    // A new vehicle resets the narrowing.
    useEffect(() => { setQ(''); setCategory(''); setHi(0); }, [vehicleLabel]);

    const categories = useMemo(() => {
        const counts = {};
        results.forEach((p) => { const c = p.category ?? 'Uncategorised'; counts[c] = (counts[c] ?? 0) + 1; });
        return Object.entries(counts).sort((a, b) => b[1] - a[1]);
    }, [results]);

    const shown = useMemo(() => {
        const words = q.trim().toLowerCase().split(/\s+/).filter(Boolean);
        const list = results.filter((p) => (!category || (p.category ?? 'Uncategorised') === category)
            && (!inStock || p.available > 0)
            && words.every((w) => `${p.part_number} ${p.description} ${p.oem_number ?? ''} ${p.brand ?? ''} ${p.bin ?? ''}`.toLowerCase().includes(w)));
        const by = {
            category: (a, b) => (a.category ?? '').localeCompare(b.category ?? '') || a.description.localeCompare(b.description),
            description: (a, b) => a.description.localeCompare(b.description),
            price: (a, b) => a.price - b.price,
            stock: (a, b) => b.available - a.available,
        }[sort];
        return [...list].sort(by);
    }, [results, q, category, inStock, sort]);

    useEffect(() => { setHi(0); }, [q, category, inStock, sort]);

    // `/` jumps to the filter from anywhere on the page (POS habit).
    useEffect(() => {
        function onKey(e) {
            const t = e.target;
            if (e.key === '/' && !(t instanceof HTMLElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName))) { e.preventDefault(); filterRef.current?.focus(); }
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    function onFilterKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); setHi((h) => Math.min(h + 1, shown.length - 1)); }
        if (e.key === 'ArrowUp') { e.preventDefault(); setHi((h) => Math.max(h - 1, 0)); }
        if (e.key === 'Enter' && shown[hi]) { e.preventDefault(); router.visit(route('inventory.parts.show', shown[hi].id)); }
        if (e.key === 'Escape') { setQ(''); }
    }

    const inStockCount = results.filter((p) => p.available > 0).length;

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-slate-300">
                <span className="flex items-center gap-2">
                    <Car className="h-4 w-4 text-orange-400" />
                    <span className="font-semibold text-white">{vehicleLabel}</span>
                    <span>· {results.length.toLocaleString()} part{results.length === 1 ? '' : 's'} fit · {inStockCount.toLocaleString()} in stock at {branch?.name ?? 'this branch'}</span>
                </span>
                <span className="flex items-center gap-3">
                    {selection.model_id && <Link href={route('vehicle-ref.models.show', selection.model_id)} className="text-xs hover:text-white">Open model</Link>}
                    {!selection.model_id && <Link href={route('vehicle-ref.makes.show', selection.make_id)} className="text-xs hover:text-white">Open make</Link>}
                    <button onClick={onClear} className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs hover:bg-slate-800 hover:text-white"><X className="h-3.5 w-3.5" /> New vehicle</button>
                </span>
            </div>

            {results.length === 0 ? (
                <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                    <EmptyState icon={Car} title="No fitment records for this vehicle yet" message="Try the make only, a different year, or add fitments on each part’s Fitments tab." />
                </div>
            ) : (
                <>
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="relative flex-1 min-w-[16rem]">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                ref={filterRef} value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={onFilterKey} autoFocus
                                placeholder="Narrow down: part type, number, brand, bin…   ( / to focus · ↑↓ Enter to open )"
                                aria-label="Filter fitting parts" autoComplete="off"
                                className="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm focus:border-orange-500 focus:ring-orange-500"
                            />
                        </div>
                        <label className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 cursor-pointer">
                            <input type="checkbox" checked={inStock} onChange={(e) => setInStock(e.target.checked)} className="rounded border-slate-300 text-orange-600" /> In stock here
                        </label>
                        <select aria-label="Sort" value={sort} onChange={(e) => setSort(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            {Object.entries(SORTS).map(([k, v]) => <option key={k} value={k}>Sort: {v}</option>)}
                        </select>
                    </div>

                    {categories.length > 1 && (
                        <div className="flex flex-wrap gap-1.5">
                            <button onClick={() => setCategory('')} className={`rounded-full px-3 py-1 text-xs font-semibold ${!category ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300'}`}>All {results.length}</button>
                            {categories.map(([c, n]) => (
                                <button key={c} onClick={() => setCategory(c === category ? '' : c)} className={`rounded-full px-3 py-1 text-xs font-semibold ${category === c ? 'bg-orange-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300'}`}>
                                    {c} <span className="opacity-60">{n}</span>
                                </button>
                            ))}
                        </div>
                    )}

                    <div role="listbox" aria-label="Fitting parts" className="overflow-hidden rounded-xl border border-slate-200 bg-white divide-y divide-slate-100">
                        {shown.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 px-6 py-10 text-center text-sm text-slate-500">
                                <SearchX className="h-8 w-8 text-slate-300" />
                                Nothing matches — clear the filter, the category or “In stock here”.
                            </div>
                        ) : shown.map((p, i) => (
                            <FitRow key={p.id} part={p} active={i === hi} onHover={() => setHi(i)} vatRate={vatRate} showCategory={sort !== 'category' || !!category} />
                        ))}
                    </div>
                    {results.length >= 1500 && <p className="text-xs text-slate-400">Showing the first 1,500 fitting parts — narrow by model or year for the rest.</p>}
                </>
            )}
        </div>
    );
}

function FitRow({ part, active, onHover, vatRate, showCategory }) {
    const ref = useRef(null);
    const stock = stockState(part, true);
    useEffect(() => { if (active) ref.current?.scrollIntoView({ block: 'nearest' }); }, [active]);
    const fit = part.fits?.[0];

    return (
        <div
            ref={ref} role="option" aria-selected={active} onMouseEnter={onHover}
            onClick={() => router.visit(route('inventory.parts.show', part.id))}
            className={`flex cursor-pointer items-start gap-3 border-l-4 px-4 py-3 transition ${active ? 'border-orange-500 bg-orange-50/70' : 'border-transparent hover:bg-slate-50'}`}
        >
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-1.5">
                    <EntityLink type="part" id={part.id} label={part.part_number} mono className="text-sm font-bold" />
                    {showCategory && part.category && <Chip>{part.category}</Chip>}
                    {part.brand && <Chip tone={part.is_oem ? 'green' : 'slate'}>{part.brand}{part.is_oem ? ' · OEM' : ''}</Chip>}
                    {part.discontinued && <Chip tone="amber">Discontinued</Chip>}
                    {fit && !fit.confirmed && <Chip tone="amber" title="Fitment derived from the supplier's application text — not yet confirmed">Unconfirmed fit</Chip>}
                </div>
                <div className="mt-0.5 text-sm text-slate-600 line-clamp-2">{part.description}</div>
                <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                    {part.bin && <span className="inline-flex items-center gap-1 font-medium text-slate-600"><MapPin className="h-3 w-3" />Bin {part.bin}</span>}
                    {part.oem_number && <span>OEM {part.oem_number}</span>}
                    {fit && <span>Fits {fit.label}{fit.years ? ` ${fit.years}` : ''}{fit.engine_code ? ` · ${fit.engine_code}` : ''}{part.fits.length > 1 ? ` +${part.fits.length - 1}` : ''}</span>}
                    {part.superseded_by && <span className="inline-flex items-center gap-1 font-medium text-amber-700"><ArrowRightLeft className="h-3 w-3" />Superseded by {part.superseded_by}</span>}
                </div>
            </div>
            <div className="shrink-0 space-y-1 text-right">
                <div className="text-base font-bold tabular-nums text-slate-900">{part.priced ? `R ${fmt(inclPrice(part.price, vatRate))}` : <span className="text-sm font-medium text-slate-400">Not priced</span>}</div>
                <Chip tone={stock.tone}>{stock.label}</Chip>
                {part.all_branches > part.available && <div className="text-[11px] text-slate-400"><PackageSearch className="inline h-3 w-3" /> {fmt(part.all_branches).replace('.00', '')} all branches</div>}
            </div>
        </div>
    );
}
