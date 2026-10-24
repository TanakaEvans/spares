import { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, Minus, Plus, PackageOpen, Banknote, BookUser, ShieldAlert } from 'lucide-react';
import EntityLink from '@/Components/EntityLink';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { round2, fmt } from '@/Components/Pos/posMath';

const REASONS = [
    'Wrong part supplied', "Doesn't fit the vehicle", 'Defective / warranty',
    'Changed mind', 'Overcharged', 'Short-shipped', 'Other',
];
const methodLabel = { cash: 'Cash', card: 'Card', eft: 'EFT', account: 'On account' };

export default function CreditNoteCreate({ invoice }) {
    const [rows, setRows] = useState(() => invoice.lines.map((l) => ({
        line_id: l.id, qty: 0, restock: true, max: l.qty_creditable,
        part_id: l.part_id, part_number: l.part_number, description: l.description,
        unit_price: l.unit_price, discount_pct: l.discount_pct, vat_rate: l.vat_rate,
    })));
    const [preset, setPreset] = useState('');
    const form = useForm({ mode: 'refund_cash', reason: '' });
    const { data, setData, processing, errors } = form;

    // 2.5 rule 4: a cash refund needs original tender cash AND the same trading
    // day; anything else is credit to the customer's account (or approval).
    const paidInCash = invoice.payments.some((p) => p.method === 'cash');
    const hasAccount = invoice.payments.some((p) => p.method === 'account') || !invoice.customer?.is_walk_in;
    const cashOk = paidInCash && invoice.same_day;

    const setRow = (i, field, value) => setRows((prev) => prev.map((r, idx) => (idx === i ? { ...r, [field]: value } : r)));
    const chosen = rows.filter((r) => r.qty > 0);

    const totals = useMemo(() => chosen.reduce((t, r) => {
        const excl = round2(r.qty * r.unit_price * (1 - (r.discount_pct || 0) / 100));
        const vat = round2(excl * (r.vat_rate || 0) / 100);
        return { excl: round2(t.excl + excl), vat: round2(t.vat + vat), incl: round2(t.incl + excl + vat) };
    }, { excl: 0, vat: 0, incl: 0 }), [chosen]);

    function submit(e) {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            lines: chosen.map((r) => ({ line_id: r.line_id, qty: r.qty, restock: r.restock })),
        }));
        form.post(route('sales.credit-notes.store', invoice.id));
    }

    const cell = 'w-full rounded border border-slate-300 px-2 py-1 text-right tabular-nums';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Credit ${invoice.document_number}`}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Credit Notes', href: route('sales.credit-notes.index') },
                { label: invoice.document_number },
            ]}
        >
            <Head title={`Credit ${invoice.document_number}`} />

            <form onSubmit={submit} className="max-w-4xl space-y-6">
                {/* Original document */}
                <div className="rounded-xl border border-slate-100 bg-white p-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <span className="text-slate-500">Crediting against</span>
                    <Link href={route('sales.invoices.show', invoice.id)} className="font-mono font-bold text-slate-800 hover:text-orange-600">{invoice.document_number}</Link>
                    <EntityLink type="customer" id={invoice.customer?.id} label={invoice.customer?.name} className="font-medium" />
                    <span className="text-slate-400">{invoice.document_date}</span>
                    <span className="text-slate-500">Paid: <strong className="text-slate-700">{invoice.payments.map((p) => methodLabel[p.method] ?? p.method).join(' + ') || '—'}</strong></span>
                    <span className="text-slate-500">Invoice <MoneyDisplay amount={invoice.total_incl} /></span>
                    {invoice.total_credited > 0 && <span className="text-red-600">already credited −<MoneyDisplay amount={invoice.total_credited} /></span>}
                </div>

                {Object.keys(errors).length > 0 && (
                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                        <span>{Object.values(errors)[0]}</span>
                    </div>
                )}

                {/* Step 1: what is coming back */}
                <div>
                    <div className="mb-2 flex items-center justify-between">
                        <h2 className="text-sm font-semibold text-slate-700">1 · What is coming back</h2>
                        <button type="button" onClick={() => setRows((prev) => prev.map((r) => ({ ...r, qty: r.max })))}
                            className="text-xs font-medium text-orange-600 hover:text-orange-700">Return everything</button>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2.5">Part</th>
                                    <th className="px-2 py-2.5 text-right w-24">Can credit</th>
                                    <th className="px-2 py-2.5 text-right w-40">Credit qty</th>
                                    <th className="px-2 py-2.5 text-center w-24">Back to stock</th>
                                    <th className="px-4 py-2.5 text-right w-24">Credit (incl)</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {rows.map((r, i) => {
                                    const excl = round2(r.qty * r.unit_price * (1 - (r.discount_pct || 0) / 100));
                                    const incl = round2(excl * (1 + (r.vat_rate || 0) / 100));
                                    return (
                                        <tr key={r.line_id} className={r.qty > 0 ? 'bg-orange-50/40' : ''}>
                                            <td className="px-4 py-2.5">
                                                <EntityLink type="part" id={r.part_id} label={r.part_number ?? r.description} mono className="font-semibold" />
                                                <span className="block text-xs text-slate-400 truncate max-w-[20rem]">{r.description}</span>
                                                {r.max < invoice.lines.find((l) => l.id === r.line_id)?.qty && (
                                                    <span className="text-[11px] text-amber-600">part of this line was credited before</span>
                                                )}
                                            </td>
                                            <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{r.max}</td>
                                            <td className="px-2 py-2.5">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button type="button" onClick={() => setRow(i, 'qty', Math.max(0, round2(r.qty - 1)))} aria-label="Less" className="rounded border border-slate-300 p-1 text-slate-500 hover:bg-slate-50"><Minus className="h-3 w-3" /></button>
                                                    <input type="number" min="0" max={r.max} step="0.01" value={r.qty}
                                                        aria-label={`Credit qty ${r.part_number}`}
                                                        onChange={(e) => setRow(i, 'qty', Math.min(r.max, Math.max(0, Number(e.target.value) || 0)))}
                                                        className={`${cell} w-16`} />
                                                    <button type="button" onClick={() => setRow(i, 'qty', Math.min(r.max, round2(r.qty + 1)))} aria-label="More" className="rounded border border-slate-300 p-1 text-slate-500 hover:bg-slate-50"><Plus className="h-3 w-3" /></button>
                                                    <button type="button" onClick={() => setRow(i, 'qty', r.max)} className="rounded border border-slate-200 px-1.5 py-1 text-[10px] font-semibold text-slate-500 hover:bg-slate-50">all</button>
                                                </div>
                                            </td>
                                            <td className="px-2 py-2.5 text-center">
                                                <input type="checkbox" checked={r.restock} onChange={(e) => setRow(i, 'restock', e.target.checked)}
                                                    aria-label={`Restock ${r.part_number}`} title="Resellable — books the part back into stock"
                                                    className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums font-medium text-red-600">{r.qty > 0 ? fmt(incl) : '—'}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    {chosen.some((r) => r.restock) && (
                        <p className="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500">
                            <PackageOpen className="h-3.5 w-3.5" />
                            Ticked lines book back into stock (RETURN_IN at original cost). Untick for damaged/unresellable goods or price corrections.
                        </p>
                    )}
                </div>

                {/* Step 2: why */}
                <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-3">
                    <h2 className="text-sm font-semibold text-slate-700">2 · Why</h2>
                    <div className="flex flex-wrap gap-2">
                        {REASONS.map((r) => (
                            <button key={r} type="button"
                                onClick={() => { setPreset(r); if (r !== 'Other') setData('reason', r); else setData('reason', ''); }}
                                className={`rounded-full border px-3 py-1.5 text-xs font-medium transition ${preset === r ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50'}`}>
                                {r}
                            </button>
                        ))}
                    </div>
                    {preset === 'Other' && (
                        <input value={data.reason} onChange={(e) => setData('reason', e.target.value)} autoFocus
                            placeholder="Describe the reason…" className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    )}
                </div>

                {/* Step 3: how the customer is settled */}
                <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-3">
                    <h2 className="text-sm font-semibold text-slate-700">3 · How the customer is settled</h2>
                    <div className="grid grid-cols-2 gap-2 max-w-md">
                        <button type="button" onClick={() => setData('mode', 'refund_cash')}
                            className={`flex items-center gap-2 rounded-xl border-2 px-4 py-3 text-left transition ${data.mode === 'refund_cash' ? 'border-orange-500 bg-orange-50' : 'border-slate-200 hover:border-slate-300'}`}>
                            <Banknote className={`h-5 w-5 ${data.mode === 'refund_cash' ? 'text-orange-600' : 'text-slate-400'}`} />
                            <span>
                                <span className="block text-sm font-semibold text-slate-800">Cash refund</span>
                                <span className="block text-[11px] text-slate-500">{cashOk ? 'Cash sale, same day — allowed' : 'Outside the same-day rule'}</span>
                            </span>
                        </button>
                        <button type="button" onClick={() => setData('mode', 'account')} disabled={!hasAccount}
                            title={!hasAccount ? 'A walk-in customer has no account to credit.' : undefined}
                            className={`flex items-center gap-2 rounded-xl border-2 px-4 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50 ${data.mode === 'account' ? 'border-orange-500 bg-orange-50' : 'border-slate-200 hover:border-slate-300'}`}>
                            <BookUser className={`h-5 w-5 ${data.mode === 'account' ? 'text-orange-600' : 'text-slate-400'}`} />
                            <span>
                                <span className="block text-sm font-semibold text-slate-800">To account</span>
                                <span className="block text-[11px] text-slate-500">{hasAccount ? 'Reduces what they owe' : 'No account on file'}</span>
                            </span>
                        </button>
                    </div>
                    {data.mode === 'refund_cash' && !cashOk && (
                        <p className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            <ShieldAlert className="h-4 w-4 shrink-0 mt-0.5" />
                            {!paidInCash
                                ? 'The original sale was not paid in cash — a cash refund here should have supervisor sign-off.'
                                : 'The invoice is not from today — a cash refund here should have supervisor sign-off.'}
                        </p>
                    )}
                </div>

                {/* Footer */}
                <div className="flex items-center justify-between">
                    <dl className="flex items-center gap-6 text-sm">
                        <div className="text-slate-500">Excl <strong className="tabular-nums text-slate-800">{fmt(totals.excl)}</strong></div>
                        <div className="text-slate-500">VAT <strong className="tabular-nums text-slate-800">{fmt(totals.vat)}</strong></div>
                        <div className="text-base font-bold text-red-600">Credit <MoneyDisplay amount={totals.incl} /></div>
                    </dl>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button type="submit" disabled={processing || chosen.length === 0 || !data.reason}
                            className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            {processing ? 'Posting…' : 'Post Credit Note'}
                        </button>
                    </div>
                </div>
            </form>
        </ModuleLayout>
    );
}
