import { Head, Link } from '@inertiajs/react';
import { FileSpreadsheet } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

// Goods received note detail (Module 3.3) — what arrived, where it was binned,
// and its place in the procure-to-pay chain: PO → GRN → invoice → payment.
export default function GrnShow({ grn }) {
    const trace = [
        { type: 'purchase_order', id: grn.po_id, label: grn.po_number ?? 'Purchase order' },
        { type: 'grn', current: true, label: grn.grn_number },
        ...(grn.invoices.length > 0
            ? grn.invoices.map((i) => ({ type: 'supplier_invoice', id: i.id, label: i.supplier_ref }))
            : [{ type: 'supplier_invoice', pending: true, label: 'Supplier invoice' }]),
        ...(grn.payments.length > 0
            ? grn.payments.map((p) => ({ type: 'payment', id: p.id, label: p.payment_number }))
            : [{ type: 'payment', pending: true, label: 'Payment' }]),
    ];

    const groups = [
        { title: 'Supplier', items: [{ type: 'supplier', id: grn.supplier_id, label: grn.supplier }] },
        { title: 'Parts', items: grn.lines.map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
        { title: 'GL journal', items: grn.gl_journal_id ? [{ type: 'journal', id: grn.gl_journal_id, label: grn.journal_number ?? `Journal #${grn.gl_journal_id}`, mono: true }] : [] },
        { title: 'Branch', items: [{ type: 'branch', id: grn.branch_id, label: grn.branch }] },
        { title: 'Received by', items: grn.received_by ? [{ type: 'user', id: grn.received_by_id, label: grn.received_by }] : [] },
        { title: 'Bins', items: grn.lines.filter((l) => l.bin).map((l) => ({ type: 'bin', id: l.bin_id, label: l.bin, mono: true })) },
    ];

    const rejected = grn.lines.reduce((n, l) => n + l.qty_rejected, 0);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={grn.grn_number}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Goods Receiving', href: route('purchasing.grns.index') },
                { label: grn.grn_number },
            ]}
        >
            <Head title={grn.grn_number} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <h1 className="font-mono text-2xl font-bold text-slate-900">{grn.grn_number}</h1>
                                <StatusBadge status={grn.status} />
                            </div>
                            <p className="text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-1.5">
                                <EntityLink type="supplier" id={grn.supplier_id} label={grn.supplier} className="font-medium" />
                                <span>· against</span>
                                <EntityLink type="purchase_order" id={grn.po_id} label={grn.po_number} mono />
                                <span>· into</span>
                                <EntityLink type="branch" id={grn.branch_id} label={grn.branch} />
                                <span>· received {grn.received_date}</span>
                                {grn.received_by && (
                                    <>
                                        <span>by</span>
                                        <EntityLink type="user" id={grn.received_by_id} label={grn.received_by} />
                                    </>
                                )}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {grn.can_invoice && (
                                <Link
                                    href={route('purchasing.invoices.create', grn.id)}
                                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                                >
                                    <FileSpreadsheet className="w-4 h-4" />
                                    Capture Supplier Invoice
                                </Link>
                            )}
                        </div>
                    </div>

                    <dl className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Accepted value</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-800"><MoneyDisplay amount={grn.accepted_value} /></dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Rejected units</dt>
                            <dd className={`text-lg font-bold tabular-nums ${rejected > 0 ? 'text-red-600' : 'text-slate-800'}`}>{rejected}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Delivery note</dt>
                            <dd className="text-lg font-bold font-mono text-slate-800">{grn.delivery_note_number ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Posted</dt>
                            <dd className="text-sm font-semibold text-slate-800 mt-1">{grn.posted_at ?? 'Not posted'}</dd>
                        </div>
                    </dl>
                    {grn.notes && <p className="mt-3 text-sm text-slate-600 whitespace-pre-line">{grn.notes}</p>}
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Part</th>
                                <th className="px-3 py-3">Description</th>
                                <th className="px-3 py-3 text-right">Received</th>
                                <th className="px-3 py-3 text-right">Rejected</th>
                                <th className="px-3 py-3">Bin</th>
                                <th className="px-3 py-3 text-right">Unit Cost</th>
                                <th className="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {grn.lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-6 py-2.5">
                                        <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" />
                                    </td>
                                    <td className="px-3 py-2.5 text-slate-600">{l.description}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums text-green-700">{l.qty_received}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">
                                        {l.qty_rejected > 0 ? (
                                            <span className="text-red-600" title={l.rejection_reason ?? undefined}>
                                                {l.qty_rejected}
                                                {l.rejection_reason && <span className="block text-[11px] text-slate-400 font-normal">{l.rejection_reason}</span>}
                                            </span>
                                        ) : <span className="text-slate-300">—</span>}
                                    </td>
                                    <td className="px-3 py-2.5"><EntityLink type="bin" id={l.bin_id} label={l.bin ?? '—'} mono muted /></td>
                                    <td className="px-3 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.unit_cost} /></td>
                                    <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.line_total} /></td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200">
                                <td colSpan={6} className="px-6 py-3 text-right font-semibold text-slate-700">Accepted value (excl. VAT)</td>
                                <td className="px-6 py-3 text-right font-bold text-lg tabular-nums"><MoneyDisplay amount={grn.accepted_value} /></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {grn.invoices.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Supplier invoices</h2>
                        <ul className="divide-y divide-slate-50 text-sm">
                            {grn.invoices.map((i) => (
                                <li key={i.id} className="py-2 flex items-center gap-3">
                                    <EntityLink type="supplier_invoice" id={i.id} label={i.supplier_ref} mono className="font-semibold" />
                                    <span className="text-slate-400">{i.invoice_date}</span>
                                    <StatusBadge status={i.status} />
                                    <span className="ml-auto tabular-nums"><MoneyDisplay amount={i.total} /></span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <RelatedLinks trace={trace} groups={groups} />
            </div>
        </ModuleLayout>
    );
}
