import { Head, Link } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PrintButton from '@/Components/PrintButton';

export default function CreditNoteShow({ creditNote }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title={creditNote.document_number}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Credit Notes', href: route('sales.credit-notes.index') },
                { label: creditNote.document_number },
            ]}
        >
            <Head title={creditNote.document_number} />

            <div className="max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">{creditNote.document_number}</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {creditNote.customer && (
                                <Link href={route('customers.show', creditNote.customer.id)} className="font-medium text-slate-600 hover:text-orange-600">{creditNote.customer.name}</Link>
                            )}
                            {' · '}{creditNote.document_date}
                            {creditNote.against && <> · against <Link href={route('sales.invoices.show', creditNote.against.id)} className="font-mono text-slate-600 hover:text-orange-600">{creditNote.against.document_number}</Link></>}
                        </p>
                        <p className="mt-1 text-sm text-slate-500">
                            {creditNote.credit_mode === 'refund_cash' ? 'Cash refund' : 'Credited to account'} · {creditNote.reason}
                        </p>
                    </div>
                    <PrintButton href={route('sales.credit-notes.print', creditNote.id)} label="Print" />
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Part</th>
                                <th className="px-2 py-2.5 text-right">Qty</th>
                                <th className="px-2 py-2.5 text-right">Price</th>
                                <th className="px-4 py-2.5 text-right">Total (incl)</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {creditNote.lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="px-4 py-2.5">
                                        <Link href={route('inventory.parts.show', l.part_id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{l.part_number}</Link>
                                        <span className="block text-xs text-slate-400">{l.description}</span>
                                    </td>
                                    <td className="px-2 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.unit_price.toFixed(2)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-medium text-red-600"><MoneyDisplay amount={l.line_total_incl} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex justify-end">
                    <dl className="w-64 space-y-1.5 text-sm">
                        <div className="flex justify-between text-slate-500"><dt>Subtotal (excl)</dt><dd className="tabular-nums">{creditNote.subtotal_excl.toFixed(2)}</dd></div>
                        <div className="flex justify-between text-slate-500"><dt>VAT</dt><dd className="tabular-nums">{creditNote.vat_amount.toFixed(2)}</dd></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-red-600"><dt>Total credited</dt><dd className="tabular-nums"><MoneyDisplay amount={creditNote.total_incl} /></dd></div>
                    </dl>
                </div>
            </div>
        </ModuleLayout>
    );
}
