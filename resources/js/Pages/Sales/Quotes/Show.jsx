import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, AlertTriangle, TrendingUp, TrendingDown, Clock } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import PrintButton from '@/Components/PrintButton';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const money = (n) => Number(n || 0).toFixed(2);

export default function QuoteShow({ quote, repriceChanges }) {
    const [processing, setProcessing] = useState(false);
    const canConvert = quote.status === 'open' && !quote.is_expired;
    const changedIds = new Set(repriceChanges.map((c) => c.line_id));
    const daysLeft = quote.expiry_date
        ? Math.ceil((new Date(quote.expiry_date) - new Date(new Date().toDateString())) / 86400000)
        : null;
    const trace = [
        { type: 'quote', current: true, label: quote.document_number },
        quote.order ? { type: 'order', id: quote.order.id, label: quote.order.document_number } : { type: 'order', pending: true, label: 'Order' },
        quote.invoice ? { type: 'invoice', id: quote.invoice.id, label: quote.invoice.document_number } : { type: 'invoice', pending: true, label: 'Invoice' },
    ];

    function convert(reprice) {
        setProcessing(true);
        router.post(route('sales.quotes.convert', quote.id), { reprice }, { onFinish: () => setProcessing(false) });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={quote.document_number}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Quotations', href: route('sales.quotes.index') },
                { label: quote.document_number },
            ]}
        >
            <Head title={quote.document_number} />

            <div className="max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{quote.document_number}</h1>
                            <StatusBadge status={quote.status} />
                            {quote.status === 'open' && daysLeft != null && !quote.is_expired && (
                                <span className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold ${daysLeft <= 5 ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200' : 'bg-slate-100 text-slate-600'}`}>
                                    <Clock className="h-3 w-3" /> {daysLeft} day{daysLeft === 1 ? '' : 's'} left
                                </span>
                            )}
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            <EntityLink type="customer" id={quote.customer?.id} label={quote.customer?.name} className="font-medium" />
                            {' · '}{quote.document_date}{quote.expiry_date && ` · valid to ${quote.expiry_date}`}
                            {quote.branch && <> · <EntityLink type="branch" id={quote.branch.id} label={quote.branch.name} muted /></>}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <PrintButton href={route('sales.quotes.print', quote.id)} label="Print" />
                        {quote.order && (
                            <Link href={route('sales.orders.show', quote.order.id)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                View order {quote.order.document_number}
                            </Link>
                        )}
                    </div>
                </div>

                {quote.is_expired && quote.status === 'open' && (
                    <div className="flex items-start gap-2 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                        This quote has expired and cannot be converted. Create a fresh quote at current prices.
                    </div>
                )}

                {canConvert && repriceChanges.length > 0 && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 overflow-hidden">
                        <div className="flex items-start gap-2 px-4 py-3 text-sm text-amber-800">
                            <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                            {repriceChanges.length} line{repriceChanges.length > 1 ? 's have' : ' has'} moved since this quote. Convert keeps the quoted prices; convert &amp; reprice takes today&apos;s.
                        </div>
                        <table className="w-full text-xs border-t border-amber-200">
                            <tbody className="divide-y divide-amber-100">
                                {repriceChanges.map((c) => {
                                    const line = quote.lines.find((l) => l.id === c.line_id);
                                    const up = c.now > c.was;
                                    return (
                                        <tr key={c.line_id} className="text-amber-900">
                                            <td className="px-4 py-1.5 font-mono font-semibold">{line?.part_number}</td>
                                            <td className="px-2 py-1.5 text-slate-500">{line?.description}</td>
                                            <td className="px-2 py-1.5 text-right tabular-nums">{money(c.was)}</td>
                                            <td className="px-2 py-1.5 w-6 text-center">{up ? <TrendingUp className="inline h-3.5 w-3.5 text-red-600" /> : <TrendingDown className="inline h-3.5 w-3.5 text-emerald-600" />}</td>
                                            <td className="px-4 py-1.5 text-right tabular-nums font-semibold">{money(c.now)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Part</th>
                                <th className="px-2 py-2.5 text-right">Qty</th>
                                <th className="px-2 py-2.5 text-right">Price</th>
                                <th className="px-2 py-2.5 text-right">Disc</th>
                                <th className="px-4 py-2.5 text-right">Total (incl)</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {quote.lines.map((l) => (
                                <tr key={l.id} className={changedIds.has(l.id) ? 'bg-amber-50/40' : ''}>
                                    <td className="px-4 py-2.5"><EntityLink type="part" id={l.part_id} label={l.part_number ?? l.description} mono className="font-semibold" /><span className="block text-xs text-slate-400">{l.description}</span></td>
                                    <td className="px-2 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{money(l.unit_price)}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.discount_pct > 0 ? `${l.discount_pct}%` : '—'}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-medium"><MoneyDisplay amount={l.line_total_incl} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between">
                    <dl className="w-56 space-y-1.5 text-sm">
                        <div className="flex justify-between text-slate-500"><dt>Subtotal (excl)</dt><dd className="tabular-nums">{money(quote.subtotal_excl)}</dd></div>
                        <div className="flex justify-between text-slate-500"><dt>VAT</dt><dd className="tabular-nums">{money(quote.vat_amount)}</dd></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900"><dt>Total</dt><dd className="tabular-nums"><MoneyDisplay amount={quote.total_incl} /></dd></div>
                    </dl>

                    {canConvert && (
                        <div className="flex gap-2">
                            {repriceChanges.length > 0 && (
                                <button onClick={() => convert(true)} disabled={processing} className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40">
                                    Convert &amp; reprice
                                </button>
                            )}
                            <button onClick={() => convert(false)} disabled={processing}
                                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                                {processing ? 'Converting…' : 'Convert to Order'}
                                <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                    )}
                </div>

                <RelatedLinks
                    trace={trace}
                    groups={[
                        { title: 'Customer', items: quote.customer ? [{ type: 'customer', id: quote.customer.id, label: quote.customer.name }] : [] },
                        { title: 'Parts', items: quote.lines.filter((l) => l.part_id).map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
                        { title: 'Branch', items: quote.branch ? [{ type: 'branch', id: quote.branch.id, label: quote.branch.name }] : [] },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}
