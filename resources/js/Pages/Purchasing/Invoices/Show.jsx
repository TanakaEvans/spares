import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, TriangleAlert, Wallet } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

// Supplier invoice detail (Module 3.4) — the supplier's bill matched against
// what we received, what it posted to AP, and what has been paid against it.
export default function SupplierInvoiceShow({ invoice }) {
    const [confirmPost, setConfirmPost] = useState(false);

    function resolveAndPost() {
        router.post(route('purchasing.invoices.post', invoice.id), {}, {
            preserveScroll: true,
            onFinish: () => setConfirmPost(false),
        });
    }

    const trace = [
        { type: 'purchase_order', id: invoice.po_id, label: invoice.po_number ?? 'Purchase order' },
        { type: 'grn', id: invoice.grn_id, label: invoice.grn_number ?? 'GRN' },
        { type: 'supplier_invoice', current: true, label: invoice.supplier_ref },
        ...(invoice.payments.length > 0
            ? invoice.payments.map((p) => ({ type: 'payment', id: p.id, label: p.payment_number }))
            : [{ type: 'payment', pending: true, label: 'Payment' }]),
    ];

    const groups = [
        { title: 'Supplier', items: [{ type: 'supplier', id: invoice.supplier_id, label: invoice.supplier }] },
        { title: 'Parts', items: invoice.lines.map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
        { title: 'GL journal', items: invoice.gl_journal_id ? [{ type: 'journal', id: invoice.gl_journal_id, label: invoice.journal_number ?? `Journal #${invoice.gl_journal_id}`, mono: true }] : [] },
        { title: 'Branch', items: invoice.branch ? [{ type: 'branch', id: invoice.branch_id, label: invoice.branch }] : [] },
    ];

    const variance = Math.abs(invoice.subtotal - invoice.grn_value);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={invoice.supplier_ref}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Supplier Invoices', href: route('purchasing.invoices.index') },
                { label: invoice.supplier_ref },
            ]}
        >
            <Head title={`Supplier invoice ${invoice.supplier_ref}`} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <h1 className="font-mono text-2xl font-bold text-slate-900">{invoice.supplier_ref}</h1>
                                <StatusBadge status={invoice.status} />
                                <span className="font-mono text-sm text-slate-400" title="Our internal reference">{invoice.invoice_number}</span>
                            </div>
                            <p className="text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-1.5">
                                <EntityLink type="supplier" id={invoice.supplier_id} label={invoice.supplier} className="font-medium" />
                                <span>· for</span>
                                <EntityLink type="grn" id={invoice.grn_id} label={invoice.grn_number} mono />
                                {invoice.po_number && (
                                    <>
                                        <span>· PO</span>
                                        <EntityLink type="purchase_order" id={invoice.po_id} label={invoice.po_number} mono />
                                    </>
                                )}
                                <span>· dated {invoice.invoice_date}</span>
                                {invoice.due_date && <span>· due {invoice.due_date}</span>}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {invoice.status === 'disputed' && (
                                <button
                                    onClick={() => setConfirmPost(true)}
                                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                                >
                                    <CheckCircle2 className="w-4 h-4" />
                                    Resolve &amp; Post
                                </button>
                            )}
                            {invoice.status === 'posted' && invoice.outstanding > 0 && route().has('finance.payments.create') && (
                                <Link
                                    href={route('finance.payments.create', { supplier_id: invoice.supplier_id })}
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    <Wallet className="w-4 h-4" />
                                    Pay Supplier
                                </Link>
                            )}
                        </div>
                    </div>

                    {invoice.status === 'disputed' && (
                        <div role="alert" className="mt-4 flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-200 px-5 py-3.5 text-sm text-amber-800">
                            <TriangleAlert className="w-5 h-5 shrink-0" />
                            <div>
                                <strong>Disputed — not yet in accounts payable.</strong>
                                <div>{invoice.dispute_reason}</div>
                            </div>
                        </div>
                    )}

                    <dl className="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Subtotal</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-800"><MoneyDisplay amount={invoice.subtotal} /></dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">VAT</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-800"><MoneyDisplay amount={invoice.vat_amount} /></dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Total</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-900"><MoneyDisplay amount={invoice.total} /></dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Paid</dt>
                            <dd className="text-lg font-bold tabular-nums text-green-700"><MoneyDisplay amount={invoice.paid} /></dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Outstanding</dt>
                            <dd className={`text-lg font-bold tabular-nums ${invoice.outstanding > 0 ? 'text-red-600' : 'text-slate-800'}`}><MoneyDisplay amount={invoice.outstanding} /></dd>
                        </div>
                    </dl>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                    <div className="px-6 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">
                            Matched against <EntityLink type="grn" id={invoice.grn_id} label={invoice.grn_number} mono />
                            {invoice.grn_received_date && <span className="normal-case tracking-normal font-normal"> · received {invoice.grn_received_date}</span>}
                        </h2>
                        <span className={`text-xs ${variance > 0.005 ? 'text-amber-700' : 'text-green-700'}`}>
                            {variance <= 0.005 ? 'Invoice subtotal matches the GRN exactly' : `Differs from GRN value by ${variance.toFixed(2)}`}
                        </span>
                    </div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Part</th>
                                <th className="px-3 py-3">Description</th>
                                <th className="px-3 py-3 text-right">Received</th>
                                <th className="px-3 py-3 text-right">Unit Cost</th>
                                <th className="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {invoice.lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-6 py-2.5"><EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" /></td>
                                    <td className="px-3 py-2.5 text-slate-600">{l.description}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{l.qty_received}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.unit_cost} /></td>
                                    <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.line_total} /></td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200">
                                <td colSpan={4} className="px-6 py-3 text-right font-semibold text-slate-700">GRN value (excl. VAT)</td>
                                <td className="px-6 py-3 text-right font-bold tabular-nums"><MoneyDisplay amount={invoice.grn_value} /></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {invoice.payments.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Payments allocated</h2>
                        <ul className="divide-y divide-slate-50 text-sm">
                            {invoice.payments.map((p) => (
                                <li key={p.id} className="py-2 flex items-center gap-3">
                                    <EntityLink type="payment" id={p.id} label={p.payment_number} mono className="font-semibold" />
                                    <span className="text-slate-400">{p.payment_date}</span>
                                    <span className="capitalize text-slate-500">{p.method}</span>
                                    {p.reference && <span className="font-mono text-xs text-slate-400">{p.reference}</span>}
                                    <span className="ml-auto tabular-nums"><MoneyDisplay amount={p.allocated} /></span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <RelatedLinks trace={trace} groups={groups} />
            </div>

            <ConfirmDialog
                open={confirmPost}
                title={`Post ${invoice.supplier_ref} despite the dispute?`}
                message="The invoice posts to accounts payable at the captured amounts. Only do this after the buyer has approved the variance."
                confirmLabel="Resolve & Post"
                onConfirm={resolveAndPost}
                onCancel={() => setConfirmPost(false)}
            />
        </ModuleLayout>
    );
}
