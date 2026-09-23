import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Hash, Pencil, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

const TYPE_LABELS = {
    invoice: 'Tax Invoice',
    quotation: 'Quotation',
    sales_order: 'Sales Order',
    credit_note: 'Credit Note',
    delivery_note: 'Delivery Note',
    receipt: 'Payment Receipt',
    purchase_order: 'Purchase Order',
    purchase_requisition: 'Purchase Requisition',
    grn: 'Goods Received Note',
    supplier_return: 'Supplier Return',
    job_card: 'Job Card',
    stock_take: 'Stock Take',
    stock_adjustment: 'Stock Adjustment',
    stock_transfer: 'Stock Transfer',
    payment: 'Supplier Payment',
    journal: 'GL Journal',
    warranty_claim: 'Warranty Claim',
    layby: 'Lay-by',
    customer: 'Customer Number',
    supplier: 'Supplier Number',
};

function EditRow({ sequence, onClose }) {
    const [form, setForm] = useState({
        prefix: sequence.prefix,
        include_date: sequence.include_date,
        date_format: sequence.date_format,
        padding: sequence.padding,
        reset_frequency: sequence.reset_frequency,
    });

    function save() {
        router.patch(route('admin.sequences.update', sequence.id), form, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    const input = 'rounded-lg border border-slate-300 px-2 py-1.5 text-sm';

    return (
        <tr className="bg-orange-50/50">
            <td className="px-6 py-3 text-sm font-medium text-slate-700">
                {TYPE_LABELS[sequence.type] ?? sequence.type}
            </td>
            <td className="px-3 py-3">
                <input
                    aria-label="Prefix"
                    value={form.prefix}
                    onChange={(e) => setForm({ ...form, prefix: e.target.value.toUpperCase() })}
                    className={`${input} w-20 font-mono`}
                />
            </td>
            <td className="px-3 py-3">
                <select
                    aria-label="Date segment"
                    value={form.include_date ? form.date_format : 'none'}
                    onChange={(e) =>
                        e.target.value === 'none'
                            ? setForm({ ...form, include_date: false })
                            : setForm({ ...form, include_date: true, date_format: e.target.value })
                    }
                    className={input}
                >
                    <option value="none">No date</option>
                    <option value="Ymd">YYYYMMDD</option>
                    <option value="Ym">YYYYMM</option>
                    <option value="Y">YYYY</option>
                </select>
            </td>
            <td className="px-3 py-3">
                <select
                    aria-label="Padding"
                    value={form.padding}
                    onChange={(e) => setForm({ ...form, padding: Number(e.target.value) })}
                    className={input}
                >
                    {[3, 4, 5, 6].map((p) => (
                        <option key={p} value={p}>
                            {p} digits
                        </option>
                    ))}
                </select>
            </td>
            <td className="px-3 py-3">
                <select
                    aria-label="Reset"
                    value={form.reset_frequency}
                    onChange={(e) => setForm({ ...form, reset_frequency: e.target.value })}
                    className={input}
                >
                    <option value="never">Never</option>
                    <option value="yearly">Yearly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </td>
            <td className="px-3 py-3 font-mono text-sm text-slate-400">—</td>
            <td className="px-6 py-3 text-right whitespace-nowrap">
                <button
                    onClick={save}
                    className="rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700 mr-2"
                >
                    Save
                </button>
                <button onClick={onClose} className="p-1.5 text-slate-400 hover:text-slate-600" aria-label="Cancel">
                    <X className="w-4 h-4" />
                </button>
            </td>
        </tr>
    );
}

export default function SequencesIndex({ auth, sequences }) {
    const [editingId, setEditingId] = useState(null);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Number Sequences"
            breadcrumbs={[
                { label: 'System Administration', href: route('modules.show', 'system-admin') },
                { label: 'Number Sequences' },
            ]}
        >
            <Head title="Number Sequences" />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-gray-700 rounded-xl flex items-center justify-center">
                            <Hash className="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Number Sequences</h1>
                            <p className="text-slate-500 text-sm mt-0.5">
                                Document numbering formats. Numbers are gapless — a failed document releases its
                                number.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Document Type</th>
                                <th className="px-3 py-3">Prefix</th>
                                <th className="px-3 py-3">Date Segment</th>
                                <th className="px-3 py-3">Digits</th>
                                <th className="px-3 py-3">Resets</th>
                                <th className="px-3 py-3">Next Number Preview</th>
                                <th className="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {sequences.map((s) =>
                                editingId === s.id ? (
                                    <EditRow key={s.id} sequence={s} onClose={() => setEditingId(null)} />
                                ) : (
                                    <tr key={s.id} className="hover:bg-slate-50/60">
                                        <td className="px-6 py-3 font-medium text-slate-700">
                                            {TYPE_LABELS[s.type] ?? s.type}
                                            {s.branch && (
                                                <span className="ml-2 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                                    {s.branch}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3 font-mono">{s.prefix}</td>
                                        <td className="px-3 py-3 text-slate-500">
                                            {s.include_date
                                                ? { Ymd: 'YYYYMMDD', Ym: 'YYYYMM', Y: 'YYYY' }[s.date_format]
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-3 text-slate-500">{s.padding}</td>
                                        <td className="px-3 py-3 text-slate-500 capitalize">{s.reset_frequency}</td>
                                        <td className="px-3 py-3 font-mono text-orange-700">{s.preview}</td>
                                        <td className="px-6 py-3 text-right">
                                            <button
                                                onClick={() => setEditingId(s.id)}
                                                className="p-1.5 rounded-lg text-slate-400 hover:text-orange-600 hover:bg-orange-50"
                                                aria-label={`Edit ${s.type} sequence`}
                                            >
                                                <Pencil className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                )
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </ModuleLayout>
    );
}
