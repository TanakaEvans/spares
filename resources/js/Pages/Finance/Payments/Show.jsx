import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import StatusBadge from '@/Components/StatusBadge';
import MoneyDisplay from '@/Components/MoneyDisplay';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const methodLabel = { cash: 'Cash', eft: 'EFT', cheque: 'Cheque', bank_transfer: 'Bank transfer' };

export default function PaymentShow({ payment }) {
    const invoices = payment.allocations.map((a) => ({ type: 'supplier_invoice', id: a.supplier_invoice_id, label: a.invoice_number, mono: true }));
    const trace = [
        ...(invoices.length > 0 ? invoices : [{ label: 'No supplier invoice allocated (on account)', pending: true }]),
        { type: 'payment', id: payment.id, label: payment.payment_number, current: true },
    ];
    const totalAllocated = payment.allocations.reduce((n, a) => n + a.amount, 0);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={payment.payment_number}
            breadcrumbs={[
                { label: 'Finance & Accounts', href: route('modules.show', 'finance') },
                { label: 'Supplier Payments', href: route('finance.payments.index') },
                { label: payment.payment_number },
            ]}
        >
            <Head title={payment.payment_number} />

            <div className="max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{payment.payment_number}</h1>
                            <StatusBadge status="posted" />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            {payment.supplier
                                ? <EntityLink type="supplier" id={payment.supplier.id} label={payment.supplier.name} className="font-medium" />
                                : '—'}
                            {' · '}{payment.payment_date}
                            {' · '}{methodLabel[payment.method] ?? payment.method}
                            {payment.branch && <>{' · '}<EntityLink type="branch" id={payment.branch.id} label={payment.branch.name} muted /></>}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={route('finance.payments.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            <Plus className="w-4 h-4" /> New Payment
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">Amount paid</div>
                        <div className="mt-1 text-2xl font-bold tabular-nums text-slate-900"><MoneyDisplay amount={payment.amount} /></div>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">Allocated to invoices</div>
                        <div className="mt-1 text-2xl font-bold tabular-nums text-emerald-600"><MoneyDisplay amount={payment.allocated} /></div>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">Unallocated (prepayment)</div>
                        <div className={`mt-1 text-2xl font-bold tabular-nums ${payment.unallocated > 0 ? 'text-amber-600' : 'text-slate-400'}`}><MoneyDisplay amount={payment.unallocated} /></div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                    <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Allocations</h2></div>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Supplier invoice</th>
                                    <th className="px-2 py-2">Supplier ref</th>
                                    <th className="px-2 py-2">Date</th>
                                    <th className="px-2 py-2 text-right">Invoice total</th>
                                    <th className="px-4 py-2 text-right">Allocated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {payment.allocations.length === 0 ? (
                                    <tr><td colSpan={5} className="px-4 py-6 text-center text-slate-400">Nothing allocated — the full amount sits as a prepayment on the supplier's account.</td></tr>
                                ) : payment.allocations.map((a) => (
                                    <tr key={a.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2"><EntityLink type="supplier_invoice" id={a.supplier_invoice_id} label={a.invoice_number} mono className="font-semibold" /></td>
                                        <td className="px-2 py-2 font-mono text-xs text-slate-500">{a.supplier_ref ?? '—'}</td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{a.invoice_date ?? '—'}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500"><MoneyDisplay amount={a.invoice_total} /></td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-800"><MoneyDisplay amount={a.amount} /></td>
                                    </tr>
                                ))}
                            </tbody>
                            {payment.allocations.length > 0 && (
                                <tfoot>
                                    <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                                        <td colSpan={4} className="px-4 py-2.5 text-right">Total allocated</td>
                                        <td className="px-4 py-2.5 text-right tabular-nums"><MoneyDisplay amount={totalAllocated} /></td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Details</h2>
                        <dl className="space-y-2 text-sm">
                            <Row label="Supplier">{payment.supplier ? <EntityLink type="supplier" id={payment.supplier.id} label={payment.supplier.name} /> : '—'}</Row>
                            <Row label="Supplier no."><span className="font-mono">{payment.supplier?.supplier_number ?? '—'}</span></Row>
                            <Row label="Method">{methodLabel[payment.method] ?? payment.method}</Row>
                            <Row label="Reference"><span className="font-mono">{payment.reference ?? '—'}</span></Row>
                            <Row label="Batch">{payment.batch_ref ? <span className="font-mono">{payment.batch_ref}</span> : <span className="text-slate-400">single payment</span>}</Row>
                            <Row label="Branch">{payment.branch ? <EntityLink type="branch" id={payment.branch.id} label={payment.branch.name} /> : '—'}</Row>
                            <Row label="Paid by">{payment.paid_by ? <EntityLink type="user" id={payment.paid_by.id} label={payment.paid_by.name} /> : '—'}</Row>
                            <Row label="Posted">{payment.posted_at ?? '—'}</Row>
                            <Row label="GL journal">{payment.journal ? <EntityLink type="journal" id={payment.journal.id} label={payment.journal.journal_number} mono /> : '—'}</Row>
                            {payment.notes && <Row label="Notes">{payment.notes}</Row>}
                        </dl>
                    </div>
                </div>

                <RelatedLinks
                    trace={trace}
                    groups={[
                        { title: 'Supplier', items: payment.supplier ? [{ type: 'supplier', id: payment.supplier.id, label: payment.supplier.name }] : [] },
                        { title: 'Supplier invoices', items: invoices },
                        { title: 'Journal', items: payment.journal ? [{ type: 'journal', id: payment.journal.id, label: payment.journal.journal_number, mono: true }] : [] },
                        { title: 'Paid by', items: payment.paid_by ? [{ type: 'user', id: payment.paid_by.id, label: payment.paid_by.name }] : [] },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}

function Row({ label, children }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <dt className="text-slate-400">{label}</dt>
            <dd className="text-right text-slate-700">{children}</dd>
        </div>
    );
}
