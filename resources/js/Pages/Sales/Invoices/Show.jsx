import { Head, Link } from '@inertiajs/react';
import { Undo2, Receipt } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PrintButton from '@/Components/PrintButton';

const methodLabel = { cash: 'Cash', card: 'Card', eft: 'EFT', account: 'On account' };

export default function InvoiceShow({ invoice, creditNotes }) {
    const anyCreditable = invoice.lines.some((l) => l.qty_creditable > 0);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={invoice.document_number}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Tax Invoices', href: route('sales.invoices.index') },
                { label: invoice.document_number },
            ]}
        >
            <Head title={invoice.document_number} />

            <div className="max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">{invoice.document_number}</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {invoice.customer
                                ? <Link href={route('customers.show', invoice.customer.id)} className="font-medium text-slate-600 hover:text-orange-600">{invoice.customer.name}</Link>
                                : '—'}
                            {' · '}{invoice.document_date}{' · '}{invoice.branch}
                            {invoice.salesperson && ` · ${invoice.salesperson}`}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <PrintButton href={route('sales.invoices.print', invoice.id)} label="A4 Invoice" />
                        <PrintButton href={route('sales.invoices.receipt', invoice.id)} label="Receipt" />
                        {anyCreditable && (
                            <Link href={route('sales.credit-notes.create', invoice.id)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <Undo2 className="w-4 h-4" />
                                Credit / Return
                            </Link>
                        )}
                    </div>
                </div>

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
                            {invoice.lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-4 py-2.5">
                                        <Link href={route('inventory.parts.show', l.part_id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{l.part_number}</Link>
                                        <span className="block text-xs text-slate-400">{l.description}</span>
                                        {l.qty_credited > 0 && <span className="text-xs text-red-500">{l.qty_credited} credited</span>}
                                    </td>
                                    <td className="px-2 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.unit_price.toFixed(2)}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.discount_pct > 0 ? `${l.discount_pct}%` : '—'}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-medium"><MoneyDisplay amount={l.line_total_incl} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Payment</h2>
                        <ul className="space-y-1.5 text-sm">
                            {invoice.payments.map((p, i) => (
                                <li key={i} className="flex justify-between text-slate-600">
                                    <span>{methodLabel[p.method] ?? p.method}{p.reference && <span className="text-slate-400"> · {p.reference}</span>}</span>
                                    <span className="tabular-nums">{p.amount.toFixed(2)}</span>
                                </li>
                            ))}
                            {invoice.payments.some((p) => p.change_given > 0) && (
                                <li className="flex justify-between text-slate-400 pt-1 border-t border-slate-50">
                                    <span>Change given</span>
                                    <span className="tabular-nums">{invoice.payments.reduce((n, p) => n + p.change_given, 0).toFixed(2)}</span>
                                </li>
                            )}
                        </ul>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <dl className="space-y-1.5 text-sm">
                            <div className="flex justify-between text-slate-500"><dt>Subtotal (excl)</dt><dd className="tabular-nums">{invoice.subtotal_excl.toFixed(2)}</dd></div>
                            {invoice.discount_amount > 0 && <div className="flex justify-between text-slate-500"><dt>Discount</dt><dd className="tabular-nums">−{invoice.discount_amount.toFixed(2)}</dd></div>}
                            <div className="flex justify-between text-slate-500"><dt>VAT</dt><dd className="tabular-nums">{invoice.vat_amount.toFixed(2)}</dd></div>
                            <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900"><dt>Total</dt><dd className="tabular-nums"><MoneyDisplay amount={invoice.total_incl} /></dd></div>
                        </dl>
                    </div>
                </div>

                {creditNotes.length > 0 && (
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-2">Credit notes against this invoice</h2>
                        <ul className="space-y-1 text-sm">
                            {creditNotes.map((c) => (
                                <li key={c.id}>
                                    <Link href={route('sales.credit-notes.show', c.id)} className="inline-flex items-center gap-2 font-mono text-slate-600 hover:text-orange-600">
                                        <Receipt className="w-3.5 h-3.5 text-slate-400" />
                                        {c.document_number}
                                        <span className="text-slate-400">· {c.document_date} · −{c.total_incl.toFixed(2)}</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </ModuleLayout>
    );
}
