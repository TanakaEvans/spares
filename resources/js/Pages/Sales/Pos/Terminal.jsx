import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    ScanBarcode, AlertTriangle, X, Banknote, CreditCard, BookUser, PauseCircle, PlayCircle, Eraser,
    Maximize2, Minimize2, Store, UserRound, Clock, Receipt, Keyboard,
} from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ConfirmDialog from '@/Components/ConfirmDialog';
import PartResults from '@/Components/Pos/PartResults';
import CartPanel from '@/Components/Pos/CartPanel';
import CustomerPicker from '@/Components/Pos/CustomerPicker';
import TenderDialog, { accountBlockReason } from '@/Components/Pos/TenderDialog';
import SaleCompleteDialog from '@/Components/Pos/SaleCompleteDialog';
import RecallDialog from '@/Components/Pos/RecallDialog';
import { cartTotals, fmt, getJson, parseQuery, round2, stockState, tillStore } from '@/Components/Pos/posMath';

const SHORTCUTS = [
    ['F2', 'Customer'], ['F3', 'Clear sale'], ['F4', 'Split pay'], ['F5', 'Cash'], ['F6', 'Card'], ['F7', 'Account'],
    ['F8', 'Suspend'], ['F9', 'Recall'], ['F10', 'Full screen'], ['↑↓', 'Pick line'], ['+ −', 'Qty'], ['Del', 'Remove line'],
];

/**
 * Counter sales till (Module 2.1) — keyboard-first, scan-focused: find → cart →
 * tender → receipt → next customer without leaving the screen. Prices,
 * availability, credit and discount authority are all re-verified server-side.
 */
export default function PosTerminal({ branch, walkIn, maxDiscount, vatRate, allowNegative, topSellers = [], categories = [], today, completedSale }) {
    const { errors, auth } = usePage().props;
    const walkInCustomer = useMemo(() => ({ ...walkIn, is_walk_in: true }), [walkIn]);

    // ── Cart (restored after a crash/refresh unless a sale just posted) ──
    const restored = useMemo(() => (completedSale ? null : tillStore.cart(branch.id)), []);
    const [customer, setCustomer] = useState(restored?.customer ?? walkInCustomer);
    const [lines, setLines] = useState(restored?.lines ?? []);
    const [selected, setSelected] = useState(null);
    const [notice, setNotice] = useState(restored?.lines?.length ? { tone: 'info', text: `Recovered an unfinished sale (${restored.lines.length} line${restored.lines.length === 1 ? '' : 's'}).` } : null);

    // ── Search / browse ──
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState(null);
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [highlight, setHighlight] = useState(0);

    // ── Dialogs / chrome ──
    const [tenderMode, setTenderMode] = useState(null);
    const [recallOpen, setRecallOpen] = useState(false);
    const [clearOpen, setClearOpen] = useState(false);
    const [customerOpenRequest, setCustomerOpenRequest] = useState(0);
    const [suspended, setSuspended] = useState(() => tillStore.suspended(branch.id));
    const [sale, setSale] = useState(completedSale ?? null);
    const [focusMode, setFocusMode] = useState(() => tillStore.focus());
    const [clock, setClock] = useState(() => new Date());

    const scanRef = useRef(null);
    const timer = useRef(null);
    const { qty: queryQty, term } = parseQuery(query);
    const totals = cartTotals(lines, vatRate);
    const dialogOpen = !!tenderMode || recallOpen || clearOpen || !!sale;

    const refocus = () => setTimeout(() => scanRef.current?.focus(), 0);
    const flash = (text, tone = 'info') => setNotice({ text, tone });

    useEffect(() => { refocus(); const t = setInterval(() => setClock(new Date()), 30000); return () => clearInterval(t); }, []);
    useEffect(() => { tillStore.setCart(branch.id, lines.length ? { customer, lines } : null); }, [lines, customer]);
    useEffect(() => { tillStore.setFocus(focusMode); }, [focusMode]);
    useEffect(() => { if (completedSale) { setSale(completedSale); tillStore.setCart(branch.id, null); } }, [completedSale?.id]);
    useEffect(() => { if (!dialogOpen) refocus(); }, [dialogOpen]);

    // ── Live search / category browse ──
    useEffect(() => {
        clearTimeout(timer.current);
        if (term.length < 2 && !category) { setResults([]); setLoading(false); return; }
        setLoading(true);
        timer.current = setTimeout(async () => {
            try {
                const json = await getJson('sales.pos.part-lookup', { q: term, customer_id: customer.id, ...(category ? { category_id: category.id } : {}) });
                setResults(json.parts ?? []);
                setHighlight(0);
            } catch { /* offline — keep prior results */ } finally { setLoading(false); }
        }, 180);
        return () => clearTimeout(timer.current);
    }, [term, category?.id, customer.id]);

    // ── Cart operations ──
    const availableText = (n) => fmt(Math.max(Number(n) || 0, 0)).replace('.00', '');

    function addPart(part, qty = 1) {
        if (!part.priced) { flash(`${part.part_number} has no selling price — it can't be sold until it is priced.`, 'error'); return false; }
        const avail = Number(part.available ?? part.on_hand ?? 0);
        const i = lines.findIndex((l) => l.part_id === part.id);
        const nextQty = round2((i >= 0 ? lines[i].qty : 0) + qty);
        if (stockState(part, allowNegative).blocked || (!allowNegative && nextQty > avail)) {
            flash(`${part.part_number}: only ${availableText(avail)} available here.`, 'error');
            return false;
        }
        if (i >= 0) {
            setLines(lines.map((l, idx) => (idx === i ? { ...l, qty: nextQty, available: part.available, on_hand: part.on_hand } : l)));
            setSelected(i);
        } else {
            setLines([...lines, {
                part_id: part.id, part_number: part.part_number, description: part.description, bin: part.bin,
                qty: nextQty, unit_price: part.price, discount_pct: 0, priced: part.priced,
                on_hand: part.on_hand, available: part.available,
            }]);
            setSelected(lines.length);
        }
        setQuery(''); setCategory(null); setResults([]); setNotice(null);
        refocus();
        return true;
    }

    /** Exact lookup by number/barcode (scanner Enter, supersession jump, tiles for trade pricing). */
    async function addByNumber(number, qty = 1) {
        try {
            const hit = (await getJson('sales.pos.part-lookup', { q: number, exact: 1, customer_id: customer.id })).parts?.[0];
            if (hit) {
                if (hit.superseded_by && stockState(hit, allowNegative).blocked) {
                    flash(`${hit.part_number} is out of stock and superseded by ${hit.superseded_by} — search that number.`, 'error');
                    return false;
                }
                const ok = addPart(hit, qty);
                if (ok && hit.superseded_by) flash(`${hit.part_number} is superseded by ${hit.superseded_by} — both can be sold.`, 'info');
                return ok;
            }
        } catch { /* fall through */ }
        flash(`No part matches "${number}". Try the OEM number, a description word, or browse a category.`, 'error');
        return false;
    }

    function setQty(i, qty) {
        const line = lines[i];
        if (!line) return;
        const avail = Number(line.available ?? line.on_hand ?? 0);
        if (!allowNegative && qty > avail && qty > line.qty) { flash(`${line.part_number}: only ${availableText(avail)} available here.`, 'error'); return; }
        setLines(lines.map((l, idx) => (idx === i ? { ...l, qty: round2(Math.max(qty, 0)) } : l)));
    }
    const setDiscount = (i, pct) => setLines((prev) => prev.map((l, idx) => (idx === i ? { ...l, discount_pct: pct } : l)));
    const removeLine = (i) => { setLines((prev) => prev.filter((_, idx) => idx !== i)); setSelected((s) => (s === null ? null : Math.max(0, Math.min(s, lines.length - 2)))); };
    const resetSale = () => { setLines([]); setCustomer(walkInCustomer); setSelected(null); setQuery(''); setCategory(null); };

    /** Customer or recall changed the price list → re-price every line (server would reject a stale total). */
    async function reprice(forCustomer, sourceLines, message) {
        if (sourceLines.length === 0) return;
        let changed = 0;
        const next = await Promise.all(sourceLines.map(async (l) => {
            try {
                const hit = (await getJson('sales.pos.part-lookup', { q: l.part_number, exact: 1, customer_id: forCustomer.id })).parts?.[0];
                if (hit) {
                    const priceChanged = Math.abs(hit.price - l.unit_price) > 0.001;
                    if (priceChanged) changed++;
                    return { ...l, unit_price: hit.price, priced: hit.priced, on_hand: hit.on_hand, available: hit.available, bin: hit.bin, priceChanged };
                }
            } catch { /* keep line */ }
            return l;
        }));
        setLines(next);
        flash(changed ? `${message} — ${changed} price${changed === 1 ? '' : 's'} updated; check the cart.` : message, changed ? 'warn' : 'info');
    }

    function changeCustomer(c) {
        setCustomer(c);
        reprice(c, lines, `Customer set to ${c.name}`);
        refocus();
    }

    // ── Suspend / recall ──
    function suspendSale() {
        if (lines.length === 0) return;
        const entry = { id: Date.now(), at: new Date().toISOString(), customer, lines, total: totals.total };
        const next = [entry, ...tillStore.suspended(branch.id)].slice(0, 20);
        tillStore.setSuspended(branch.id, next);
        setSuspended(next);
        resetSale();
        flash(`Sale suspended (${entry.lines.length} line${entry.lines.length === 1 ? '' : 's'}, ${fmt(entry.total)}). F9 to recall.`);
        refocus();
    }
    async function recallSale(entry) {
        if (lines.length > 0) { flash('Finish, suspend or clear the current sale before recalling another.', 'error'); return; }
        const next = tillStore.suspended(branch.id).filter((s) => s.id !== entry.id);
        tillStore.setSuspended(branch.id, next);
        setSuspended(next);
        setRecallOpen(false);
        setCustomer(entry.customer);
        setLines(entry.lines);
        await reprice(entry.customer, entry.lines, 'Sale recalled');
    }
    function discardSuspended(entry) {
        const next = tillStore.suspended(branch.id).filter((s) => s.id !== entry.id);
        tillStore.setSuspended(branch.id, next);
        setSuspended(next);
    }

    // ── Charge readiness: disabled-with-reason (ui-rules) ──
    const shortLine = !allowNegative && lines.find((l) => l.qty > Number(l.available ?? l.on_hand ?? 0));
    const chargeBlock = lines.length === 0 ? 'Add a part first.'
        : lines.some((l) => !l.priced) ? 'Remove the unpriced line(s) first.'
        : lines.some((l) => !(l.qty > 0)) ? 'Every line needs a quantity above 0.'
        : shortLine ? `Not enough stock for ${shortLine.part_number}.`
        : null;
    const accountBlock = accountBlockReason(customer);
    const openTender = (mode) => {
        if (chargeBlock) { flash(chargeBlock, 'error'); return; }
        if (mode === 'account' && accountBlock) { flash(accountBlock, 'error'); return; }
        setTenderMode(mode);
    };

    // ── Keyboard: scan box ──
    function onScanKey(e) {
        const hasResults = results.length > 0 && (term.length >= 2 || category);
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (hasResults) setHighlight((h) => Math.min(h + 1, results.length - 1));
            else if (lines.length) setSelected((s) => (s === null ? 0 : Math.min(s + 1, lines.length - 1)));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (hasResults) setHighlight((h) => Math.max(h - 1, 0));
            else if (lines.length) setSelected((s) => (s === null ? lines.length - 1 : Math.max(s - 1, 0)));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (term.length < 2) { if (hasResults && results[highlight]) addPart(results[highlight], queryQty); return; }
            const exact = results.find((p) => p.part_number.toLowerCase() === term.toLowerCase());
            if (exact) addPart(exact, queryQty);
            else if (hasResults && results[highlight] && !loading) addPart(results[highlight], queryQty);
            else { clearTimeout(timer.current); addByNumber(term, queryQty); }
        } else if (e.key === 'Escape') {
            if (query || category) { e.stopPropagation(); setQuery(''); setCategory(null); }
        } else if (query === '' && selected !== null && lines[selected]) {
            if (e.key === '+' || e.key === '=') { e.preventDefault(); setQty(selected, lines[selected].qty + 1); }
            else if (e.key === '-') { e.preventDefault(); if (lines[selected].qty > 1) setQty(selected, lines[selected].qty - 1); }
            else if (e.key === 'Delete') { e.preventDefault(); removeLine(selected); }
        }
    }

    // ── Keyboard: global function keys (2.1: operable without a mouse) ──
    useEffect(() => {
        function onKey(e) {
            if (dialogOpen) return; // Radix dialogs own the keyboard while open
            const map = {
                F2: () => setCustomerOpenRequest((n) => n + 1),
                F3: () => lines.length && setClearOpen(true),
                F4: () => openTender('split'),
                F5: () => openTender('cash'),
                F6: () => openTender('card'),
                F7: () => openTender('account'),
                F8: () => suspendSale(),
                F9: () => setRecallOpen(true),
                F10: () => setFocusMode((f) => !f),
            };
            if (map[e.key]) { e.preventDefault(); map[e.key](); }
            else if (e.key === 'Escape') setNotice(null);
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    });

    const serverError = errors.sale || errors.discount || errors.lines;
    const toneClass = { info: 'border-slate-200 bg-white text-slate-700', warn: 'border-amber-200 bg-amber-50 text-amber-800', error: 'border-red-200 bg-red-50 text-red-700' };

    const till = (
        <div className={`${focusMode ? 'fixed inset-0 z-40 bg-slate-100 p-3' : 'h-[calc(100vh-7.5rem)]'} flex flex-col gap-3`}>
            {/* Header strip: branch · cashier · today · clock · tools */}
            <div className="flex flex-wrap items-center gap-x-5 gap-y-1 rounded-xl bg-slate-900 px-4 py-2.5 text-sm text-slate-300">
                <span className="flex items-center gap-1.5 font-semibold text-white"><Store className="h-4 w-4 text-orange-400" />{branch.name}</span>
                <span className="flex items-center gap-1.5"><UserRound className="h-4 w-4" />{auth?.user?.name}</span>
                <span className="flex items-center gap-1.5" title="Your posted sales today">
                    <Receipt className="h-4 w-4" />{today.count} sale{today.count === 1 ? '' : 's'} today · <MoneyDisplay amount={today.total} className="font-semibold text-white" />
                </span>
                <span className="flex items-center gap-1.5"><Clock className="h-4 w-4" />{clock.toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit' })}</span>
                <span className="ml-auto flex items-center gap-2">
                    {suspended.length > 0 && (
                        <button onClick={() => setRecallOpen(true)} className="rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-300 hover:bg-amber-500/30">
                            {suspended.length} suspended · F9
                        </button>
                    )}
                    <a href={route('reports.cashup')} target="_blank" rel="noreferrer" className="rounded-md px-2 py-1 text-xs hover:bg-slate-800 hover:text-white">Cash-up (X/Z)</a>
                    <button onClick={() => setFocusMode((f) => !f)} title="Full-screen till (F10)" className="rounded-md p-1.5 hover:bg-slate-800 hover:text-white">
                        {focusMode ? <Minimize2 className="h-4 w-4" /> : <Maximize2 className="h-4 w-4" />}
                    </button>
                </span>
            </div>

            {(serverError || notice) && (
                <div className={`flex items-start justify-between gap-2 rounded-lg border px-4 py-2 text-sm ${serverError ? toneClass.error : toneClass[notice.tone]}`} role="status">
                    <span className="flex items-start gap-2">{(serverError || notice?.tone === 'error') && <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />}{serverError || notice.text}</span>
                    {!serverError && <button onClick={() => setNotice(null)} aria-label="Dismiss" className="opacity-50 hover:opacity-100"><X className="h-4 w-4" /></button>}
                </div>
            )}

            <div className="grid min-h-0 flex-1 grid-cols-1 gap-3 lg:grid-cols-12">
                {/* ── Find ── */}
                <section className="flex min-h-0 flex-col gap-3 lg:col-span-7">
                    <div className="relative">
                        <ScanBarcode className="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-orange-500" />
                        <input
                            ref={scanRef} value={query} onChange={(e) => setQuery(e.target.value)} onKeyDown={onScanKey}
                            placeholder="Scan a barcode, or type part number / OEM / vehicle + part…"
                            aria-label="Scan or search part" autoComplete="off" spellCheck={false}
                            className="w-full rounded-xl border-2 border-slate-200 bg-white py-3.5 pl-14 pr-28 text-lg shadow-sm focus:border-orange-500 focus:ring-orange-500"
                        />
                        {queryQty !== 1 && term && (
                            <span className="absolute right-3 top-1/2 -translate-y-1/2 rounded-md bg-orange-100 px-2 py-1 text-xs font-bold text-orange-700">× {queryQty}</span>
                        )}
                    </div>
                    <div className="min-h-0 flex-1 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <PartResults
                            term={term} results={results} loading={loading} highlight={highlight} setHighlight={setHighlight}
                            category={category} onClearCategory={() => { setCategory(null); refocus(); }}
                            onAdd={(p) => (customer.is_walk_in ? addPart(p, queryQty) : addByNumber(p.part_number, queryQty))}
                            onUseReplacement={(n) => addByNumber(n, queryQty)}
                            topSellers={topSellers} categories={categories}
                            onBrowse={(c) => { setCategory(c); refocus(); }}
                            allowNegative={allowNegative} vatRate={vatRate}
                        />
                    </div>
                </section>

                {/* ── Sale ── */}
                <section className="flex min-h-0 flex-col gap-3 lg:col-span-5">
                    <div className="rounded-xl border border-slate-200 bg-white p-3">
                        <CustomerPicker customer={customer} walkIn={walkInCustomer} onChange={changeCustomer} openRequest={customerOpenRequest} />
                    </div>

                    <div className="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div className="flex items-center justify-between border-b border-slate-100 px-3 py-2 text-xs text-slate-500">
                            <span className="font-semibold uppercase tracking-wide">Sale · {lines.length} line{lines.length === 1 ? '' : 's'} · {fmt(totals.items).replace('.00', '')} item{totals.items === 1 ? '' : 's'}</span>
                            <span>Prices incl. VAT</span>
                        </div>
                        <div className="min-h-0 flex-1">
                            <CartPanel lines={lines} calc={totals.calc} selected={selected} onSelect={(i) => { setSelected(i); }}
                                onQty={setQty} onDiscount={setDiscount} onRemove={removeLine}
                                maxDiscount={maxDiscount} allowNegative={allowNegative} vatRate={vatRate}
                                suspendedCount={suspended.length} onRecall={() => setRecallOpen(true)} />
                        </div>

                        <div className="border-t border-slate-200 bg-slate-50 px-4 py-3 space-y-1 text-sm">
                            <div className="flex justify-between text-slate-500"><span>Subtotal excl. VAT</span><span className="tabular-nums">{fmt(totals.subtotal)}</span></div>
                            {totals.discount > 0 && <div className="flex justify-between text-emerald-700"><span>Discounts (excl.)</span><span className="tabular-nums">−{fmt(totals.discount)}</span></div>}
                            <div className="flex justify-between text-slate-500"><span>VAT {vatRate}%</span><span className="tabular-nums">{fmt(totals.vat)}</span></div>
                            <div className="flex items-end justify-between pt-1">
                                <span className="text-base font-semibold text-slate-700">Total</span>
                                <span className="text-4xl font-extrabold tabular-nums text-slate-900"><MoneyDisplay amount={totals.total} /></span>
                            </div>
                        </div>
                    </div>

                    {/* Tender + sale actions */}
                    <div className="space-y-2">
                        <div className="grid grid-cols-3 gap-2">
                            {[['cash', 'Cash', Banknote, 'F5', null], ['card', 'Card', CreditCard, 'F6', null], ['account', 'Account', BookUser, 'F7', accountBlock]].map(([mode, label, Icon, kbd, block]) => (
                                <button key={mode} type="button" onClick={() => openTender(mode)} disabled={!!chargeBlock || !!block} title={chargeBlock ?? block ?? `Pay by ${label.toLowerCase()} (${kbd})`}
                                    className="flex flex-col items-center gap-0.5 rounded-xl border-2 border-slate-300 bg-white py-2.5 text-sm font-bold text-slate-800 hover:border-orange-500 hover:text-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                                    <Icon className="h-5 w-5" />{label}<kbd className="font-mono text-[10px] font-normal text-slate-400">{kbd}</kbd>
                                </button>
                            ))}
                        </div>
                        <button type="button" onClick={() => openTender('cash')} disabled={!!chargeBlock} title={chargeBlock ?? 'Take payment'}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 py-3.5 text-lg font-bold text-white shadow-sm hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                            Take payment <MoneyDisplay amount={totals.total} />
                        </button>
                        <div className="grid grid-cols-3 gap-2 text-xs font-medium">
                            <button type="button" onClick={suspendSale} disabled={lines.length === 0} title={lines.length ? 'Park this sale (F8)' : 'Nothing to suspend'}
                                className="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-300 bg-white py-2 text-slate-600 hover:bg-slate-50 disabled:opacity-40">
                                <PauseCircle className="h-4 w-4" />Suspend <kbd className="text-slate-400">F8</kbd>
                            </button>
                            <button type="button" onClick={() => setRecallOpen(true)} disabled={suspended.length === 0} title={suspended.length ? 'Recall a parked sale (F9)' : 'No suspended sales on this till'}
                                className="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-300 bg-white py-2 text-slate-600 hover:bg-slate-50 disabled:opacity-40">
                                <PlayCircle className="h-4 w-4" />Recall{suspended.length ? ` (${suspended.length})` : ''} <kbd className="text-slate-400">F9</kbd>
                            </button>
                            <button type="button" onClick={() => setClearOpen(true)} disabled={lines.length === 0} title={lines.length ? 'Start over (F3)' : 'Nothing to clear'}
                                className="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-300 bg-white py-2 text-slate-600 hover:border-red-300 hover:text-red-600 disabled:opacity-40">
                                <Eraser className="h-4 w-4" />Clear <kbd className="text-slate-400">F3</kbd>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div className="hidden xl:flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400">
                <Keyboard className="h-3.5 w-3.5" />
                {SHORTCUTS.map(([k, label]) => <span key={k}><kbd className="rounded border border-slate-300 bg-white px-1 font-mono text-slate-500">{k}</kbd> {label}</span>)}
            </div>
        </div>
    );

    return (
        <ModuleLayout navConfig={navConfig} title="Point of Sale"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Point of Sale' }]}>
            <Head title="Point of Sale" />
            {till}

            <TenderDialog open={!!tenderMode} initialMode={tenderMode} total={totals.total} customer={customer} lines={lines} maxDiscount={maxDiscount}
                onClose={() => setTenderMode(null)}
                onPosted={() => { setTenderMode(null); resetSale(); tillStore.setCart(branch.id, null); }} />
            <SaleCompleteDialog sale={sale} onNext={() => { setSale(null); setNotice(null); }} />
            <RecallDialog open={recallOpen} suspended={suspended} onRecall={recallSale} onDiscard={discardSuspended} onClose={() => setRecallOpen(false)} />
            <ConfirmDialog open={clearOpen} title="Clear this sale?" message={`${lines.length} line${lines.length === 1 ? '' : 's'} (${fmt(totals.total)}) will be removed. Nothing has been posted.`}
                confirmLabel="Clear sale" destructive onCancel={() => setClearOpen(false)}
                onConfirm={() => { resetSale(); setClearOpen(false); flash('Sale cleared.'); }} />
        </ModuleLayout>
    );
}
