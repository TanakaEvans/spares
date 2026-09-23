import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';

export default function CreditNoteCreate({ invoice }) {
    // Per invoice line: selected qty to credit + restock flag.
    const [rows, setRows] = useState(() => invoice.lines.map((l) => ({
        line_id: l.id, qty: 0, restock: true, max: l.qty_creditable,
        part_number: l.part_number, description: l.description, unit_price: l.unit_price,
    })));
    const form = useForm({ mode: 'refund_cash', reason: '' });
    const { data, setData, processing, errors } = form;

    function setRow(i, field, value) {
        setRows((prev) => prev.map((r, idx) => (idx === i ? { ...r, [field]: value } : r)));
    }

    const chosen = rows.filter((r) => r.qty > 0);
    const estExcl = chosen.reduce((n, r) => n + r.qty * r.unit_price, 0);

    function submit(e) {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            lines: chosen.map((r) => ({ line_id: r.line_id, qty: r.qty, restock: r.restock })),
        }));
        form.post(route('sales.credit-notes.store', invoice.id));
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Credit ${invoice.document_number}`}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Tax Invoices', href: route('sales.invoices.index') },
                { label: invoice.document_number, href: route('sales.invoices.show', invoice.id) },
                { label: 'Credit / Return' },
            ]}
        >
            <Head title={`Credit ${invoice.document_number}`} />

            <form onSubmit={submit} className="max-w-3xl space-y-6">
                <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Crediting against{' '}
                    <Link href={route('sales.invoices.show', invoice.id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{invoice.document_number}</Link>
                    {' · '}{invoice.customer?.name}{' · '}{invoice.document_date}
                </div>

                {Object.keys(errors).length > 0 && (
                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                        <span>{Object.values(errors)[0]}</span>
                    </div>
                )}

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Part</th>
                                <th className="px-2 py-2.5 text-right w-28">Creditable</th>
                                <th className="px-2 py-2.5 text-right w-28">Credit qty</th>
                                <th className="px-2 py-2.5 text-center w-24">Restock</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {rows.map((r, i) => (
                                <tr key={r.line_id}>
                                    <td className="px-4 py-2.5">
                                        <span className="font-mono font-semibold text-slate-700">{r.part_number}</span>
                                        <span className="block text-xs text-slate-400">{r.description}</span>
                                    </td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{r.max}</td>
                                    <td className="px-2 py-2.5">
                                        <input type="number" min="0" max={r.max} step="0.01" value={r.qty}
                                            aria-label={`Credit qty ${r.part_number}`}
                                            onChange={(e) => setRow(i, 'qty', Math.min(r.max, Math.max(0, Number(e.target.value) || 0)))}
                                            className="w-full rounded border border-slate-300 px-2 py-1 text-right tabular-nums" />
                                    </td>
                                    <td className="px-2 py-2.5 text-center">
                                        <input type="checkbox" checked={r.restock} onChange={(e) => setRow(i, 'restock', e.target.checked)}
                                            aria-label={`Restock ${r.part_number}`}
                                            className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1">
                        <label htmlFor="cn-mode" className="block text-sm font-medium text-slate-700">Refund method</label>
                        <select id="cn-mode" value={data.mode} onChange={(e) => setData('mode', e.target.value)}
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="refund_cash">Cash refund</option>
                            <option value="account">Credit to account</option>
                        </select>
                    </div>
                    <div className="space-y-1">
                        <label htmlFor="cn-reason" className="block text-sm font-medium text-slate-700">Reason</label>
                        <input id="cn-reason" value={data.reason} onChange={(e) => setData('reason', e.target.value)}
                            placeholder="e.g. Wrong part supplied"
                            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div className="flex items-center justify-between">
                    <div className="text-sm text-slate-500">
                        Credit (excl VAT): <span className="font-semibold text-slate-800 tabular-nums"><MoneyDisplay amount={estExcl} /></span>
                        <span className="text-slate-400"> · VAT added at each line's original rate</span>
                    </div>
                    <div className="flex gap-3">
                        <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button type="submit" disabled={processing || chosen.length === 0 || !data.reason}
                            className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            {processing ? 'Posting…' : 'Post Credit Note'}
                        </button>
                    </div>
                </div>
            </form>
        </ModuleLayout>
    );
}
