import { Head, useForm } from '@inertiajs/react';
import { PackageX, Receipt } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import EntityLink from '@/Components/EntityLink';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

// Return-to-supplier detail (Module 3.5): draft → shipped (RMA, stock leaves)
// → credited (supplier credit note reduces AP).
export default function SupplierReturnShow({ supplierReturn: r }) {
    const trace = [
        { type: 'supplier_return', current: true, label: r.return_number },
        r.status === 'credited'
            ? { type: 'supplier_credit', id: r.id, label: r.credit_note_ref ?? 'Supplier credit' }
            : { type: 'supplier_credit', pending: true, label: 'Supplier credit note' },
    ];

    const groups = [
        { title: 'Supplier', items: [{ type: 'supplier', id: r.supplier_id, label: r.supplier }] },
        { title: 'Parts', items: r.lines.map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
        { title: 'GL journal', items: r.gl_journal_id ? [{ type: 'journal', id: r.gl_journal_id, label: r.journal_number ?? `Journal #${r.gl_journal_id}`, mono: true }] : [] },
        { title: 'Branch', items: [{ type: 'branch', id: r.branch_id, label: r.branch }] },
    ];

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={r.return_number}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Returns to Supplier', href: route('purchasing.returns.index') },
                { label: r.return_number },
            ]}
        >
            <Head title={r.return_number} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex items-center gap-2.5 flex-wrap">
                        <h1 className="font-mono text-2xl font-bold text-slate-900">{r.return_number}</h1>
                        <StatusBadge status={r.status} />
                    </div>
                    <p className="text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-1.5">
                        <EntityLink type="supplier" id={r.supplier_id} label={r.supplier} className="font-medium" />
                        <span>· from</span>
                        <EntityLink type="branch" id={r.branch_id} label={r.branch} />
                        <span className="capitalize">· {r.reason.replace('_', ' ')}</span>
                        {r.created_at && <span>· raised {r.created_at}</span>}
                        {r.shipped_at && <span>· shipped {r.shipped_at}</span>}
                    </p>

                    <dl className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Stock value</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-800"><MoneyDisplay amount={r.value} /></dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Supplier RMA</dt>
                            <dd className="text-lg font-bold font-mono text-slate-800">{r.supplier_rma ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Credit note ref</dt>
                            <dd className="text-lg font-bold font-mono text-slate-800">{r.credit_note_ref ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-slate-400 uppercase tracking-wide">Credited</dt>
                            <dd className="text-lg font-bold tabular-nums text-slate-800">
                                {r.credit_total !== null ? <MoneyDisplay amount={r.credit_total} /> : '—'}
                            </dd>
                        </div>
                    </dl>
                    {r.notes && <p className="mt-3 text-sm text-slate-600 whitespace-pre-line">{r.notes}</p>}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
                    <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-6 py-3">Part</th>
                                    <th className="px-3 py-3">Description</th>
                                    <th className="px-3 py-3">Condition</th>
                                    <th className="px-3 py-3 text-right">Qty</th>
                                    <th className="px-3 py-3 text-right">Unit Cost</th>
                                    <th className="px-6 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {r.lines.map((l) => (
                                    <tr key={l.id}>
                                        <td className="px-6 py-2.5"><EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" /></td>
                                        <td className="px-3 py-2.5 text-slate-600">{l.description}</td>
                                        <td className="px-3 py-2.5 capitalize text-slate-500">{l.condition}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums">{l.qty}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.unit_cost} /></td>
                                        <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.line_total} /></td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-slate-200">
                                    <td colSpan={5} className="px-6 py-3 text-right font-semibold text-slate-700">Stock value</td>
                                    <td className="px-6 py-3 text-right font-bold text-lg tabular-nums"><MoneyDisplay amount={r.value} /></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {r.status === 'draft' && <ShipCard supplierReturn={r} />}
                    {r.status === 'shipped' && <CreditCard supplierReturn={r} />}
                </div>

                <RelatedLinks trace={trace} groups={groups} />
            </div>
        </ModuleLayout>
    );
}

function ShipCard({ supplierReturn: r }) {
    const { data, setData, post, processing, errors } = useForm({ supplier_rma: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono';

    return (
        <form
            onSubmit={(e) => { e.preventDefault(); post(route('purchasing.returns.ship', r.id), { preserveScroll: true }); }}
            className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
        >
            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Ship to supplier</h2>
            <p className="text-sm text-slate-500">Stock leaves the shelf when you ship. The supplier's RMA number is required.</p>
            <FormField label="Supplier RMA number" htmlFor="rt-rma" required error={errors.supplier_rma}>
                <input id="rt-rma" value={data.supplier_rma} onChange={(e) => setData('supplier_rma', e.target.value)} className={input} autoFocus />
            </FormField>
            <button
                type="submit"
                disabled={processing || !data.supplier_rma}
                title={!data.supplier_rma ? 'Enter the RMA number first' : undefined}
                className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
            >
                <PackageX className="w-4 h-4" />
                {processing ? 'Shipping…' : 'Ship Return'}
            </button>
        </form>
    );
}

function CreditCard({ supplierReturn: r }) {
    const { data, setData, post, processing, errors } = useForm({ credit_note_ref: '', credit_total: String(r.value) });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <form
            onSubmit={(e) => { e.preventDefault(); post(route('purchasing.returns.credit', r.id), { preserveScroll: true }); }}
            className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
        >
            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Capture supplier credit</h2>
            <p className="text-sm text-slate-500">The credit reduces what you owe this supplier.</p>
            <FormField label="Supplier credit note ref" htmlFor="rt-credit-ref" required error={errors.credit_note_ref}>
                <input id="rt-credit-ref" value={data.credit_note_ref} onChange={(e) => setData('credit_note_ref', e.target.value)} className={`${input} font-mono`} autoFocus />
            </FormField>
            <FormField label="Credit amount" htmlFor="rt-credit-total" required error={errors.credit_total}>
                <input id="rt-credit-total" type="number" step="0.01" min="0.01" value={data.credit_total} onChange={(e) => setData('credit_total', e.target.value)} className={`${input} text-right tabular-nums`} />
            </FormField>
            <button
                type="submit"
                disabled={processing || !data.credit_note_ref || !data.credit_total}
                className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed"
            >
                <Receipt className="w-4 h-4" />
                {processing ? 'Capturing…' : 'Capture Credit'}
            </button>
        </form>
    );
}
