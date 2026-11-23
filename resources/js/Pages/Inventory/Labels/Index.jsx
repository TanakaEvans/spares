import { useEffect, useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Layers, Loader2, PackagePlus, Printer, ScanBarcode, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import EntityLink from '@/Components/EntityLink';
import EmptyState from '@/Components/EmptyState';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PartLinePicker from '@/Components/PartLinePicker';

const DRAFT_KEY = (branchId) => `labels.draft.${branchId}`;
const readDraft = (branchId) => { try { return JSON.parse(localStorage.getItem(DRAFT_KEY(branchId))) ?? []; } catch { return []; } };
const writeDraft = (branchId, lines) => {
    try {
        lines.length
            ? localStorage.setItem(DRAFT_KEY(branchId), JSON.stringify(lines.map(({ part_id, qty }) => ({ part_id, qty }))))
            : localStorage.removeItem(DRAFT_KEY(branchId));
    } catch { /* storage unavailable — the batch just isn't remembered */ }
};

async function getJson(params) {
    const res = await fetch(route('inventory.labels.lookup') + '?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

/**
 * Inventory › Print Labels (Operational). Build a batch of barcode labels —
 * from a part, a GRN, everything in stock or a hand-picked list — then print
 * on an A4 sheet, a label printer or the 80mm receipt roll. Every label
 * encodes something the till's scan recognises.
 */
export default function LabelsIndex({ branch, branches, lines: incoming, source, formats, defaults, categories }) {
    const [lines, setLines] = useState(incoming);
    const [format, setFormat] = useState(formats[defaults.format] ? defaults.format : 'a4');
    const [start, setStart] = useState(1);
    const [showPrice, setShowPrice] = useState(defaults.show_price);
    const [categoryId, setCategoryId] = useState('');
    const [busy, setBusy] = useState(false);
    const [notice, setNotice] = useState(null);

    // Restore an unfinished batch unless we arrived with lines (from a part or GRN).
    useEffect(() => {
        if (incoming.length) { writeDraft(branch.id, incoming); return; }
        const draft = readDraft(branch.id);
        if (!draft.length) return;
        getJson({ ids: draft.map((d) => d.part_id).join(','), branch_id: branch.id })
            .then(({ lines: fresh }) => {
                const qty = Object.fromEntries(draft.map((d) => [d.part_id, d.qty]));
                setLines(fresh.map((l) => ({ ...l, qty: qty[l.part_id] ?? l.qty })));
                setNotice({ tone: 'info', text: `Restored your unfinished batch (${fresh.length} part${fresh.length === 1 ? '' : 's'}).` });
            })
            .catch(() => {});
    }, []);
    useEffect(() => { writeDraft(branch.id, lines); }, [lines]);

    const fmt = formats[format];
    const total = lines.reduce((n, l) => n + (Number(l.qty) || 0), 0);
    const batches = Math.max(1, Math.ceil(total / defaults.max_per_print));
    const pages = format === 'a4' ? Math.ceil((total + start - 1) / fmt.per_page) : total;
    const tooLong = lines.filter((l) => l.fits && l.fits[format] === false);

    function merge(newLines, { replaceQty = false } = {}) {
        setLines((prev) => {
            const byId = new Map(prev.map((l) => [l.part_id, l]));
            for (const l of newLines) {
                const had = byId.get(l.part_id);
                byId.set(l.part_id, had ? { ...had, qty: replaceQty ? l.qty : had.qty } : l);
            }
            return [...byId.values()];
        });
    }

    async function addPart(part, pickedQty) {
        if (lines.some((l) => l.part_id === part.id)) {
            setNotice({ tone: 'info', text: `${part.part_number} is already in the batch — change its quantity below.` });
            return;
        }
        try {
            const { lines: [line] } = await getJson({ ids: part.id, branch_id: branch.id });
            if (line) merge([pickedQty > 1 ? { ...line, qty: pickedQty } : line]);
            setNotice(null);
        } catch { setNotice({ tone: 'error', text: 'Could not load that part — check the connection and try again.' }); }
    }

    async function addStocked() {
        setBusy(true);
        try {
            const params = { stocked: 1, branch_id: branch.id, ...(categoryId ? { category_id: categoryId } : {}) };
            const { lines: stocked } = await getJson(params);
            merge(stocked);
            setNotice({ tone: 'info', text: `Added ${stocked.length} part${stocked.length === 1 ? '' : 's'} in stock — one label per unit on hand.` });
        } catch { setNotice({ tone: 'error', text: 'Could not load stock — try again.' }); }
        setBusy(false);
    }

    const setQty = (id, qty) => setLines((prev) => prev.map((l) => (l.part_id === id ? { ...l, qty: Math.max(0, Math.min(9999, Math.round(Number(qty) || 0))) } : l)));
    const setAll = (fn) => setLines((prev) => prev.map((l) => ({ ...l, qty: fn(l) })));
    const remove = (id) => setLines((prev) => prev.filter((l) => l.part_id !== id));

    const items = useMemo(() => lines.filter((l) => l.qty > 0).map((l) => `${l.part_id}:${l.qty}`).join(','), [lines]);
    const printUrl = (batch = 1) => route('inventory.labels.print', {
        items, format, start: format === 'a4' ? start : 1, price: showPrice ? 1 : 0, batch, branch_id: branch.id,
    });
    const block = total === 0 ? 'Add parts and set how many labels each needs.' : null;
    const preview = lines.find((l) => l.qty > 0) ?? lines[0];

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Print Labels"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Print Labels' },
            ]}
        >
            <Head title="Print Labels" />

            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                {/* ── Batch ── */}
                <div className="xl:col-span-2 space-y-4">
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h1 className="flex items-center gap-2 text-lg font-semibold text-slate-900">
                                <ScanBarcode className="h-5 w-5 text-orange-500" /> Barcode labels
                            </h1>
                            {branches.length > 1 && (
                                <select aria-label="Branch" value={branch.id}
                                    onChange={(e) => router.get(route('inventory.labels.index'), { branch_id: e.target.value })}
                                    className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                                </select>
                            )}
                        </div>
                        {source?.type === 'grn' && (
                            <p className="text-sm text-slate-500">
                                One label per unit received on <EntityLink type="grn" id={source.id} label={source.label} mono />.
                            </p>
                        )}
                        <PartLinePicker lookupRoute="inventory.labels.lookup" branchId={branch.id} onPick={addPart}
                            placeholder="Scan or search a part to add — type 5*CODE for five labels" />
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                            <select aria-label="Category for stock run" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}
                                className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                <option value="">All categories</option>
                                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                            <button type="button" onClick={addStocked} disabled={busy}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                                {busy ? <Loader2 className="h-4 w-4 animate-spin" /> : <PackagePlus className="h-4 w-4" />}
                                Add everything in stock
                            </button>
                            {lines.length > 0 && (
                                <span className="ml-auto flex items-center gap-1 text-xs text-slate-500">
                                    Set all to
                                    <button type="button" onClick={() => setAll(() => 1)} className="rounded border border-slate-300 px-2 py-0.5 hover:bg-slate-50">1 each</button>
                                    <button type="button" onClick={() => setAll((l) => Math.max(1, Math.ceil(l.on_hand)))} className="rounded border border-slate-300 px-2 py-0.5 hover:bg-slate-50">stock on hand</button>
                                    <button type="button" onClick={() => setLines([])} className="rounded border border-red-200 px-2 py-0.5 text-red-600 hover:bg-red-50">clear</button>
                                </span>
                            )}
                        </div>
                        {notice && (
                            <p role="status" className={`rounded-lg px-3 py-2 text-sm ${notice.tone === 'error' ? 'bg-red-50 text-red-700' : 'bg-slate-50 text-slate-600'}`}>{notice.text}</p>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                        {lines.length === 0 ? (
                            <EmptyState title="No labels yet"
                                message="Scan or search a part, or add everything in stock. Opening this page from a part or a goods received note fills it in for you." />
                        ) : (
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2">Part</th>
                                        <th className="px-3 py-2">Barcode</th>
                                        <th className="px-3 py-2 text-right">Price incl.</th>
                                        <th className="px-3 py-2 text-right">On hand</th>
                                        <th className="px-3 py-2 text-right">Labels</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {lines.map((l) => (
                                        <tr key={l.part_id} className={l.qty === 0 ? 'opacity-50' : ''}>
                                            <td className="px-3 py-2 max-w-xs">
                                                <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" />
                                                <div className="truncate text-xs text-slate-500">{l.description}</div>
                                                {l.fits?.[format] === false && (
                                                    <div className="mt-0.5 flex items-center gap-1 text-xs text-amber-700">
                                                        <AlertTriangle className="h-3 w-3" /> Code too long for this label size — may not scan. Use a wider format.
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <img src={l.preview} alt={`Barcode ${l.value}`} className="h-7 w-32 object-fill" />
                                                <div className="font-mono text-[11px] text-slate-500">
                                                    {l.value} <span className="text-slate-400">· {l.symbology === 'ean13' ? 'EAN-13' : 'Code 128'}</span>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {l.price_incl != null
                                                    ? <><MoneyDisplay amount={l.price_incl} />{l.price_source === 'auto' && <div className="text-[10px] uppercase text-sky-600">Auto</div>}</>
                                                    : <span className="text-xs text-slate-400" title="No price list entry and no cost yet">no price</span>}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-slate-500">
                                                {Number(l.on_hand).toLocaleString('en-ZA')}
                                                {l.bin && <div className="text-[11px] text-slate-400">{l.bin}</div>}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <input type="number" min="0" max="9999" step="1" value={l.qty}
                                                    aria-label={`Labels for ${l.part_number}`}
                                                    onChange={(e) => setQty(l.part_id, e.target.value)}
                                                    className="w-20 rounded-lg border border-slate-300 px-2 py-1 text-right tabular-nums" />
                                            </td>
                                            <td className="px-2 py-2">
                                                <button type="button" onClick={() => remove(l.part_id)} aria-label={`Remove ${l.part_number}`}
                                                    className="rounded p-1 text-slate-300 hover:bg-red-50 hover:text-red-600"><Trash2 className="h-4 w-4" /></button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                {/* ── Print ── */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4 xl:sticky xl:top-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Print</h2>

                    <FormField label="Format" htmlFor="lb-format">
                        <div className="space-y-1.5" id="lb-format" role="radiogroup">
                            {Object.entries(formats).map(([key, f]) => (
                                <label key={key} className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm ${format === key ? 'border-orange-400 bg-orange-50' : 'border-slate-200'}`}>
                                    <input type="radio" name="format" value={key} checked={format === key} onChange={() => setFormat(key)} />
                                    {f.label}
                                </label>
                            ))}
                        </div>
                    </FormField>

                    {format === 'a4' && (
                        <FormField label="Start at label" htmlFor="lb-start" help="Reuse a part-used sheet: click the first empty label.">
                            <div id="lb-start" className="inline-grid gap-0.5 rounded border border-slate-200 p-1" style={{ gridTemplateColumns: `repeat(${fmt.cols}, 1.25rem)` }}>
                                {Array.from({ length: fmt.per_page }, (_, i) => (
                                    <button key={i} type="button" onClick={() => setStart(i + 1)} aria-label={`Start at label ${i + 1}`}
                                        className={`h-3.5 rounded-sm ${i + 1 < start ? 'bg-slate-200' : i + 1 === start ? 'bg-orange-500' : 'bg-orange-100 hover:bg-orange-200'}`} />
                                ))}
                            </div>
                        </FormField>
                    )}

                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" checked={showPrice} onChange={(e) => setShowPrice(e.target.checked)} />
                        Print the selling price
                    </label>

                    {preview && (
                        <div>
                            <div className="mb-1 text-xs text-slate-400">Preview · {fmt.cell_w} × {fmt.cell_h} mm</div>
                            <div className="relative rounded border border-dashed border-slate-300 bg-white p-2"
                                style={{ aspectRatio: `${fmt.cell_w} / ${fmt.cell_h}` }}>
                                <div className="font-mono text-sm font-bold leading-tight text-slate-900 truncate">{preview.part_number}</div>
                                <div className="truncate text-[10px] text-slate-600">{preview.description}</div>
                                <img src={preview.preview} alt="" className="mt-0.5 h-[38%] w-full object-fill" />
                                <div className="text-center font-mono text-[9px] text-slate-600">{preview.value}</div>
                                <div className="absolute inset-x-2 bottom-1 flex items-end justify-between">
                                    <span className="text-[10px] text-slate-600">{preview.bin}</span>
                                    {showPrice && preview.price_incl != null && <span className="text-sm font-bold"><MoneyDisplay amount={preview.price_incl} /></span>}
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="flex items-center gap-2 text-sm text-slate-600">
                        <Layers className="h-4 w-4 text-slate-400" />
                        {total.toLocaleString('en-ZA')} label{total === 1 ? '' : 's'}
                        {total > 0 && <> · {pages.toLocaleString('en-ZA')} {format === 'a4' ? `sheet${pages === 1 ? '' : 's'}` : `page${pages === 1 ? '' : 's'}`}</>}
                    </div>
                    {tooLong.length > 0 && (
                        <p className="text-xs text-amber-700">{tooLong.length} code{tooLong.length === 1 ? ' is' : 's are'} too long for this size — they print, but may not scan.</p>
                    )}

                    {batches === 1 ? (
                        <a href={block ? undefined : printUrl()} target="_blank" rel="noopener" aria-disabled={!!block} title={block ?? 'Opens the labels as a PDF — print from there'}
                            onClick={(e) => { if (block) e.preventDefault(); }}
                            className={`flex w-full items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 ${block ? 'cursor-not-allowed opacity-40' : ''}`}>
                            <Printer className="h-4 w-4" /> Print labels
                        </a>
                    ) : (
                        <div className="space-y-1.5">
                            <p className="text-xs text-slate-500">Large run — printed in {batches} parts of up to {defaults.max_per_print.toLocaleString('en-ZA')} so each PDF opens quickly.</p>
                            {Array.from({ length: batches }, (_, i) => (
                                <a key={i} href={printUrl(i + 1)} target="_blank" rel="noopener"
                                    className={`flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold ${i === 0 ? 'bg-orange-600 text-white hover:bg-orange-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50'}`}>
                                    <Printer className="h-4 w-4" /> Part {i + 1}: labels {(i * defaults.max_per_print + 1).toLocaleString('en-ZA')}–{Math.min(total, (i + 1) * defaults.max_per_print).toLocaleString('en-ZA')}
                                </a>
                            ))}
                        </div>
                    )}
                    <p className="text-xs text-slate-400">
                        Sizes, sheet layout and printer resolution are in{' '}
                        <a href={route('admin.settings.index')} className="underline hover:text-slate-600">Configuration Centre › Labels</a>.
                    </p>
                </div>
            </div>
        </ModuleLayout>
    );
}
