import { Head, useForm } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';

// Capture the supplier's invoice against a GRN — the 3-way match runs on save.
export default function InvoiceCreate({ grn }) {
    const { data, setData, post, processing, errors } = useForm({
        supplier_ref: '',
        invoice_date: new Date().toISOString().slice(0, 10),
        subtotal: grn.value.toFixed(2),
        vat_amount: (grn.value * 0.15).toFixed(2),
    });

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const variance = Math.abs(Number(data.subtotal || 0) - grn.value);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Invoice for ${grn.grn_number}`}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Supplier Invoices', href: route('purchasing.invoices.index') },
                { label: grn.grn_number },
            ]}
        >
            <Head title={`Invoice for ${grn.grn_number}`} />

            <div className="max-w-3xl grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">
                        What we received — {grn.grn_number}
                    </h2>
                    <p className="text-sm text-slate-500 mb-3">
                        {grn.supplier} · PO {grn.po_number} · {grn.received_date}
                    </p>
                    <table className="w-full text-sm">
                        <tbody className="divide-y divide-slate-50">
                            {grn.lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="py-1.5 font-mono text-slate-700">{l.part_number}</td>
                                    <td className="py-1.5 text-right tabular-nums text-slate-500">{l.qty_received} × <MoneyDisplay amount={l.unit_cost} /></td>
                                    <td className="py-1.5 text-right tabular-nums"><MoneyDisplay amount={l.line_total} /></td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200">
                                <td colSpan={2} className="py-2 font-semibold text-slate-700">GRN value</td>
                                <td className="py-2 text-right font-bold tabular-nums"><MoneyDisplay amount={grn.value} /></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <form
                    onSubmit={(e) => { e.preventDefault(); post(route('purchasing.invoices.store', grn.id)); }}
                    className="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4"
                >
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Their invoice</h2>
                    <FormField label="Supplier invoice number" htmlFor="inv-ref" required error={errors.supplier_ref}>
                        <input id="inv-ref" value={data.supplier_ref} onChange={(e) => setData('supplier_ref', e.target.value)} className={`${input} font-mono`} />
                    </FormField>
                    <FormField label="Invoice date" htmlFor="inv-date" required error={errors.invoice_date}>
                        <input id="inv-date" type="date" value={data.invoice_date} onChange={(e) => setData('invoice_date', e.target.value)} className={input} />
                    </FormField>
                    <div className="grid grid-cols-2 gap-3">
                        <FormField label="Subtotal (excl VAT)" htmlFor="inv-sub" required error={errors.subtotal}>
                            <input id="inv-sub" type="number" step="0.01" value={data.subtotal} onChange={(e) => setData('subtotal', e.target.value)} className={`${input} text-right tabular-nums`} />
                        </FormField>
                        <FormField label="VAT" htmlFor="inv-vat" required error={errors.vat_amount}>
                            <input id="inv-vat" type="number" step="0.01" value={data.vat_amount} onChange={(e) => setData('vat_amount', e.target.value)} className={`${input} text-right tabular-nums`} />
                        </FormField>
                    </div>
                    <p className={`text-sm ${variance > Math.max(grn.value * 0.02, 10) ? 'text-amber-700' : 'text-slate-500'}`}>
                        {variance <= 0.005
                            ? 'Matches the GRN exactly — will post straight to accounts payable.'
                            : variance > Math.max(grn.value * 0.02, 10)
                                ? `Differs from the GRN by $${variance.toFixed(2)} — this will be captured as DISPUTED for buyer review.`
                                : `Differs from the GRN by $${variance.toFixed(2)} — within tolerance, will post.`}
                    </p>
                    <button
                        type="submit"
                        disabled={processing || !data.supplier_ref}
                        className="w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                    >
                        {processing ? 'Matching…' : 'Capture & Match Invoice'}
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
