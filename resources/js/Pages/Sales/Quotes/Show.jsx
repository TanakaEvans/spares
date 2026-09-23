import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import PrintButton from '@/Components/PrintButton';

export default function QuoteShow({ quote, repriceChanges }) {
    const [processing, setProcessing] = useState(false);
    const canConvert = quote.status === 'open' && !quote.is_expired;

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
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            {quote.customer && <Link href={route('customers.show', quote.customer.id)} className="font-medium text-slate-600 hover:text-orange-600">{quote.customer.name}</Link>}
                            {' · '}{quote.document_date}{quote.expiry_date && ` · valid to ${quote.expiry_date}`}
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
                    <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                        <div>
                            {repriceChanges.length} line{repriceChanges.length > 1 ? 's have' : ' has'} a different current price. You can keep the quoted prices or reprice on convert.
                        </div>
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
                            {quote.lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="px-4 py-2.5"><Link href={route('inventory.parts.show', l.part_id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{l.part_number}</Link><span className="block text-xs text-slate-400">{l.description}</span></td>
                                    <td className="px-2 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.unit_price.toFixed(2)}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.discount_pct > 0 ? `${l.discount_pct}%` : '—'}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-medium"><MoneyDisplay amount={l.line_total_incl} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between">
                    <dl className="w-56 space-y-1.5 text-sm">
                        <div className="flex justify-between text-slate-500"><dt>Subtotal (excl)</dt><dd className="tabular-nums">{quote.subtotal_excl.toFixed(2)}</dd></div>
                        <div className="flex justify-between text-slate-500"><dt>VAT</dt><dd className="tabular-nums">{quote.vat_amount.toFixed(2)}</dd></div>
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
            </div>
        </ModuleLayout>
    );
}
