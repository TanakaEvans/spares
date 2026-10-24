import { Head, Link } from '@inertiajs/react';
import { Undo2, Receipt } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import PrintButton from '@/Components/PrintButton';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const methodLabel = { cash: 'Cash', card: 'Card', eft: 'EFT', account: 'On account' };

export default function InvoiceShow({ invoice, creditNotes, related = {} }) {
    const anyCreditable = invoice.lines.some((l) => l.qty_creditable > 0);
    const onAccount = invoice.payments.some((p) => p.method === 'account');

    // Quote → Order → Invoice (here) → Credit notes / Receipts.
    const trace = [
        related.quote ? { type: 'quote', id: related.quote.id, label: related.quote.document_number } : null,
        related.order ? { type: 'order', id: related.order.id, label: related.order.document_number } : null,
        related.job ? { type: 'job', id: related.job.id, label: related.job.job_number } : null,
        { type: 'invoice', current: true, label: invoice.document_number },
        ...creditNotes.map((c) => ({ type: 'credit_note', id: c.id, label: c.document_number })),
        ...(related.receipts ?? []).map((r) => ({ type: 'receipt', id: r.id, label: r.receipt_number })),
        onAccount && (related.receipts ?? []).length === 0 ? { type: 'receipt', pending: true, label: 'Receipt' } : null,
    ].filter(Boolean);

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
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{invoice.document_number}</h1>
                            <StatusBadge status={invoice.status} />
                            <StatusBadge
                                status={onAccount ? 'on_hold' : 'paid'}
                                label={onAccount ? 'on account' : 'paid in full'}
                            />
                            {creditNotes.length > 0 && <StatusBadge status="partial" label={`${creditNotes.length} credit note${creditNotes.length === 1 ? '' : 's'}`} />}
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            <EntityLink type="customer" id={invoice.customer?.id} label={invoice.customer?.name} className="font-medium" />
                            {' · '}{invoice.document_date}
                            {' · '}<EntityLink type="branch" id={invoice.branch_id} label={invoice.branch} muted />
                            {invoice.salesperson && <> · <EntityLink type="user" id={invoice.salesperson_id} label={invoice.salesperson} muted /></>}
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
                                        <EntityLink type="part" id={l.part_id} label={l.part_number ?? l.description} mono className="font-semibold" />
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
                                <li key={c.id} className="inline-flex items-center gap-2 text-slate-600">
                                    <Receipt className="w-3.5 h-3.5 text-slate-400" />
                                    <EntityLink type="credit_note" id={c.id} label={c.document_number} mono />
                                    <span className="text-slate-400">· {c.document_date} · −{c.total_incl.toFixed(2)}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <RelatedLinks
                    trace={trace}
                    groups={[
                        { title: 'Customer', items: invoice.customer ? [{ type: 'customer', id: invoice.customer.id, label: invoice.customer.name }] : [] },
                        { title: 'Parts', items: invoice.lines.filter((l) => l.part_id).map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
                        { title: 'Receipts', items: (related.receipts ?? []).map((r) => ({ type: 'receipt', id: r.id, label: `${r.receipt_number} · ${r.amount.toFixed(2)}`, mono: true })) },
                        { title: 'GL journal', items: related.journal ? [{ type: 'journal', id: related.journal.id, label: related.journal.journal_number, mono: true }] : [] },
                        { title: 'Workshop job', items: related.job ? [{ type: 'job', id: related.job.id, label: related.job.job_number, mono: true }] : [] },
                        { title: 'Branch', items: invoice.branch ? [{ type: 'branch', id: invoice.branch_id, label: invoice.branch }] : [] },
                        { title: 'Salesperson', items: invoice.salesperson ? [{ type: 'user', id: invoice.salesperson_id, label: invoice.salesperson }] : [] },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}
