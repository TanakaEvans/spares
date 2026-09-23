import { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { PackageCheck, TriangleAlert } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FormField from '@/Components/FormField';

// The receiving screen (Module 3.3): count what arrived, reject damage,
// assign bins, POST — stock, AVCO and GL update in one atomic posting.
export default function GrnCreate({ order, bins }) {
    const { data, setData, post, processing, errors } = useForm({
        delivery_note_number: '',
        notes: '',
        over_receipt_approved: false,
        lines: order.lines.map((l) => ({
            po_line_id: l.po_line_id,
            qty_received: l.qty_outstanding,
            qty_rejected: 0,
            rejection_reason: '',
            bin_location_id: '',
        })),
    });
    const [confirmOpen, setConfirmOpen] = useState(false);

    const input = 'w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm';

    function setLine(i, field, value) {
        setData('lines', data.lines.map((l, idx) => (idx === i ? { ...l, [field]: value } : l)));
    }

    const totals = useMemo(() => {
        let qty = 0; let value = 0; let overReceipt = false;
        data.lines.forEach((l, i) => {
            const meta = order.lines[i];
            const q = Number(l.qty_received) || 0;
            qty += q;
            value += q * meta.unit_cost;
            if (meta.qty_already_received + q > meta.qty_ordered * 1.1) overReceipt = true;
        });
        return { qty, value: value.toFixed(2), overReceipt };
    }, [data.lines, order.lines]);

    function submit() {
        post(route('purchasing.grns.store', order.id), { onFinish: () => setConfirmOpen(false) });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Receive ${order.po_number}`}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Goods Receiving', href: route('purchasing.grns.index') },
                { label: order.po_number },
            ]}
        >
            <Head title={`Receive ${order.po_number}`} />

            <div className="max-w-5xl space-y-6">
                {Object.keys(errors).length > 0 && (
                    <div role="alert" className="rounded-xl bg-red-50 border border-red-200 px-5 py-3.5 text-sm text-red-700">
                        {Object.values(errors).map((e, i) => <div key={i}>{e}</div>)}
                    </div>
                )}
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h1 className="text-xl font-bold text-slate-900">
                        Receiving against <span className="font-mono">{order.po_number}</span> — {order.supplier}
                    </h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Count what physically arrived. Rejected items never enter stock. Posting is final —
                        errors are corrected by a supplier return, not by editing.
                    </p>
                    <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField label="Supplier delivery note no" htmlFor="grn-dn" error={errors.delivery_note_number}>
                            <input id="grn-dn" value={data.delivery_note_number} onChange={(e) => setData('delivery_note_number', e.target.value)} className={input} />
                        </FormField>
                        <FormField label="Notes" htmlFor="grn-notes" error={errors.notes}>
                            <input id="grn-notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={input} />
                        </FormField>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Part</th>
                                <th className="px-3 py-3 text-right">Ordered</th>
                                <th className="px-3 py-3 text-right">Prev. recv'd</th>
                                <th className="px-3 py-3 text-right w-28">Receiving</th>
                                <th className="px-3 py-3 text-right w-24">Rejected</th>
                                <th className="px-3 py-3 w-40">Reject reason</th>
                                <th className="px-6 py-3 w-36">Bin</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {order.lines.map((meta, i) => {
                                const line = data.lines[i];
                                const over = meta.qty_already_received + (Number(line.qty_received) || 0) > meta.qty_ordered * 1.1;
                                return (
                                    <tr key={meta.po_line_id} className={over ? 'bg-amber-50/60' : ''}>
                                        <td className="px-6 py-2.5">
                                            <span className="font-mono font-semibold text-slate-800">{meta.part_number}</span>
                                            <span className="block text-xs text-slate-400">{meta.description}</span>
                                        </td>
                                        <td className="px-3 py-2.5 text-right tabular-nums">{meta.qty_ordered}</td>
                                        <td className="px-3 py-2.5 text-right tabular-nums text-slate-400">{meta.qty_already_received}</td>
                                        <td className="px-3 py-2.5">
                                            <input
                                                type="number" step="0.01" min="0" value={line.qty_received}
                                                aria-label={`Quantity received for ${meta.part_number}`}
                                                onChange={(e) => setLine(i, 'qty_received', e.target.value)}
                                                className={`${input} text-right tabular-nums ${over ? 'border-amber-400' : ''}`}
                                            />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <input
                                                type="number" step="0.01" min="0" value={line.qty_rejected}
                                                aria-label={`Quantity rejected for ${meta.part_number}`}
                                                onChange={(e) => setLine(i, 'qty_rejected', e.target.value)}
                                                className={`${input} text-right tabular-nums`}
                                            />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <input
                                                value={line.rejection_reason}
                                                aria-label={`Rejection reason for ${meta.part_number}`}
                                                onChange={(e) => setLine(i, 'rejection_reason', e.target.value)}
                                                disabled={!Number(line.qty_rejected)}
                                                placeholder={Number(line.qty_rejected) ? 'required' : '—'}
                                                className={`${input} disabled:bg-slate-50`}
                                            />
                                        </td>
                                        <td className="px-6 py-2.5">
                                            <select
                                                value={line.bin_location_id}
                                                aria-label={`Bin for ${meta.part_number}`}
                                                onChange={(e) => setLine(i, 'bin_location_id', e.target.value)}
                                                className={input}
                                            >
                                                <option value="">— bin —</option>
                                                {bins.map((b) => <option key={b.id} value={b.id}>{b.code}</option>)}
                                            </select>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {totals.overReceipt && (
                    <label className="flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-5 py-3.5 text-sm text-amber-800">
                        <TriangleAlert className="w-5 h-5 shrink-0" />
                        <span className="flex-1">
                            One or more lines exceed the ordered quantity beyond the tolerance.
                            A supervisor must approve the over-receipt.
                        </span>
                        <span className="flex items-center gap-2 font-medium">
                            <input
                                type="checkbox"
                                checked={data.over_receipt_approved}
                                onChange={(e) => setData('over_receipt_approved', e.target.checked)}
                                className="rounded border-amber-400 text-orange-600"
                            />
                            Supervisor approved
                        </span>
                    </label>
                )}

                <div className="flex items-center justify-between">
                    <p className="text-sm text-slate-500">
                        Receiving <strong className="tabular-nums">{totals.qty}</strong> units ·
                        value <strong className="tabular-nums">${totals.value}</strong>
                    </p>
                    <button
                        onClick={() => setConfirmOpen(true)}
                        disabled={processing || totals.qty <= 0}
                        title={totals.qty <= 0 ? 'Nothing to receive' : undefined}
                        className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <PackageCheck className="w-4 h-4" />
                        Post GRN
                    </button>
                </div>
            </div>

            <ConfirmDialog
                open={confirmOpen}
                title={`Post goods receipt for ${order.po_number}?`}
                message={`${totals.qty} units ($${totals.value}) will enter stock and the GL. Posting cannot be undone — corrections go through a supplier return.`}
                confirmLabel="Post GRN"
                processing={processing}
                onConfirm={submit}
                onCancel={() => setConfirmOpen(false)}
            />
        </ModuleLayout>
    );
}
