import { Head, Link } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import StatusBadge from '@/Components/StatusBadge';
import MoneyDisplay from '@/Components/MoneyDisplay';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const methodLabel = { cash: 'Cash', card: 'Card', eft: 'EFT', bank_transfer: 'Bank transfer' };

export default function ReceiptShow({ receipt }) {
    const invoices = receipt.allocations.map((a) => ({ type: 'invoice', id: a.document_id, label: a.document_number, mono: true }));
    const trace = [
        ...(invoices.length > 0 ? invoices : [{ label: 'No invoice allocated (on account)', pending: true }]),
        { type: 'receipt', id: receipt.id, label: receipt.receipt_number, current: true },
    ];
    const totalAllocated = receipt.allocations.reduce((n, a) => n + a.amount, 0);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={receipt.receipt_number}
            breadcrumbs={[
                { label: 'Finance & Accounts', href: route('modules.show', 'finance') },
                { label: 'Customer Receipts', href: route('finance.receipts.index') },
                { label: receipt.receipt_number },
            ]}
        >
            <Head title={receipt.receipt_number} />

            <div className="max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{receipt.receipt_number}</h1>
                            <StatusBadge status="posted" />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            {receipt.customer
                                ? <EntityLink type="customer" id={receipt.customer.id} label={receipt.customer.name} className="font-medium" />
                                : '—'}
                            {' · '}{receipt.receipt_date}
                            {' · '}{methodLabel[receipt.method] ?? receipt.method}
                            {receipt.branch && <>{' · '}<EntityLink type="branch" id={receipt.branch.id} label={receipt.branch.name} muted /></>}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {receipt.customer && (
                            <a href={route('customers.statement', receipt.customer.id)} target="_blank" rel="noreferrer"
                                className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <FileText className="w-4 h-4" /> Customer statement
                            </a>
                        )}
                        <Link href={route('finance.receipts.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            <Plus className="w-4 h-4" /> New Receipt
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">Amount received</div>
                        <div className="mt-1 text-2xl font-bold tabular-nums text-slate-900"><MoneyDisplay amount={receipt.amount} /></div>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">Allocated to invoices</div>
                        <div className="mt-1 text-2xl font-bold tabular-nums text-emerald-600"><MoneyDisplay amount={receipt.allocated} /></div>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">Unallocated (credit on account)</div>
                        <div className={`mt-1 text-2xl font-bold tabular-nums ${receipt.unallocated > 0 ? 'text-amber-600' : 'text-slate-400'}`}><MoneyDisplay amount={receipt.unallocated} /></div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                    <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Allocations</h2></div>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Invoice</th>
                                    <th className="px-2 py-2">Date</th>
                                    <th className="px-2 py-2 text-right">Invoice total</th>
                                    <th className="px-4 py-2 text-right">Allocated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {receipt.allocations.length === 0 ? (
                                    <tr><td colSpan={4} className="px-4 py-6 text-center text-slate-400">Nothing allocated — the full amount sits as credit on the customer's account.</td></tr>
                                ) : receipt.allocations.map((a) => (
                                    <tr key={a.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2"><EntityLink type="invoice" id={a.document_id} label={a.document_number} mono className="font-semibold" /></td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{a.document_date ?? '—'}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500"><MoneyDisplay amount={a.document_total} /></td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-800"><MoneyDisplay amount={a.amount} /></td>
                                    </tr>
                                ))}
                            </tbody>
                            {receipt.allocations.length > 0 && (
                                <tfoot>
                                    <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                                        <td colSpan={3} className="px-4 py-2.5 text-right">Total allocated</td>
                                        <td className="px-4 py-2.5 text-right tabular-nums"><MoneyDisplay amount={totalAllocated} /></td>
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>

                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Details</h2>
                        <dl className="space-y-2 text-sm">
                            <Row label="Customer">{receipt.customer ? <EntityLink type="customer" id={receipt.customer.id} label={receipt.customer.name} /> : '—'}</Row>
                            <Row label="Account no."><span className="font-mono">{receipt.customer?.customer_number ?? '—'}</span></Row>
                            <Row label="Method">{methodLabel[receipt.method] ?? receipt.method}</Row>
                            <Row label="Reference"><span className="font-mono">{receipt.reference ?? '—'}</span></Row>
                            <Row label="Branch">{receipt.branch ? <EntityLink type="branch" id={receipt.branch.id} label={receipt.branch.name} /> : '—'}</Row>
                            <Row label="Received by">{receipt.received_by ? <EntityLink type="user" id={receipt.received_by.id} label={receipt.received_by.name} /> : '—'}</Row>
                            <Row label="Posted">{receipt.posted_at ?? '—'}</Row>
                            <Row label="GL journal">{receipt.journal ? <EntityLink type="journal" id={receipt.journal.id} label={receipt.journal.journal_number} mono /> : '—'}</Row>
                            {receipt.notes && <Row label="Notes">{receipt.notes}</Row>}
                        </dl>
                    </div>
                </div>

                <RelatedLinks
                    trace={trace}
                    groups={[
                        { title: 'Customer', items: receipt.customer ? [{ type: 'customer', id: receipt.customer.id, label: receipt.customer.name }] : [] },
                        { title: 'Invoices', items: invoices },
                        { title: 'Journal', items: receipt.journal ? [{ type: 'journal', id: receipt.journal.id, label: receipt.journal.journal_number, mono: true }] : [] },
                        { title: 'Received by', items: receipt.received_by ? [{ type: 'user', id: receipt.received_by.id, label: receipt.received_by.name }] : [] },
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
