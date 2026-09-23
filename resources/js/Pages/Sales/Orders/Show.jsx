import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Banknote, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmDialog from '@/Components/ConfirmDialog';

const money = (n) => Number(n || 0).toFixed(2);

export default function OrderShow({ order }) {
    const [tenderOpen, setTenderOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);
    const [cancelling, setCancelling] = useState(false);

    function cancel() {
        setCancelling(true);
        router.post(route('sales.orders.cancel', order.id), {}, { onFinish: () => { setCancelling(false); setCancelOpen(false); } });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={order.document_number}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Sales Orders', href: route('sales.orders.index') },
                { label: order.document_number },
            ]}
        >
            <Head title={order.document_number} />

            <div className="max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{order.document_number}</h1>
                            <StatusBadge status={order.status} />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            {order.customer && <Link href={route('customers.show', order.customer.id)} className="font-medium text-slate-600 hover:text-orange-600">{order.customer.name}</Link>}
                            {' · '}{order.document_date}
                            {order.from_quote && <> · from <Link href={route('sales.quotes.show', order.from_quote.id)} className="font-mono text-slate-600 hover:text-orange-600">{order.from_quote.document_number}</Link></>}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {order.invoice && (
                            <Link href={route('sales.invoices.show', order.invoice.id)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                Invoice {order.invoice.document_number}
                            </Link>
                        )}
                        {order.is_fulfillable && (
                            <>
                                <button onClick={() => setCancelOpen(true)} className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                                <button onClick={() => setTenderOpen(true)} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                    <Banknote className="w-4 h-4" />
                                    Fulfil &amp; Invoice
                                </button>
                            </>
                        )}
                    </div>
                </div>

                {order.status === 'confirmed' && (
                    <div className="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Stock for this order is reserved. Fulfilling posts the tax invoice and releases the reservation.
                    </div>
                )}

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
                            {order.lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="px-4 py-2.5"><Link href={route('inventory.parts.show', l.part_id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{l.part_number}</Link><span className="block text-xs text-slate-400">{l.description}</span></td>
                                    <td className="px-2 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{l.unit_price.toFixed(2)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-medium"><MoneyDisplay amount={l.line_total_incl} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex justify-end">
                    <dl className="w-56 space-y-1.5 text-sm">
                        <div className="flex justify-between text-slate-500"><dt>Subtotal (excl)</dt><dd className="tabular-nums">{order.subtotal_excl.toFixed(2)}</dd></div>
                        <div className="flex justify-between text-slate-500"><dt>VAT</dt><dd className="tabular-nums">{order.vat_amount.toFixed(2)}</dd></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900"><dt>Total</dt><dd className="tabular-nums"><MoneyDisplay amount={order.total_incl} /></dd></div>
                    </dl>
                </div>
            </div>

            {tenderOpen && <FulfilDialog order={order} onClose={() => setTenderOpen(false)} />}

            <ConfirmDialog
                open={cancelOpen}
                title={`Cancel ${order.document_number}?`}
                message="The order is voided and its reserved stock is released back to available."
                confirmLabel="Cancel order"
                destructive
                processing={cancelling}
                onConfirm={cancel}
                onCancel={() => setCancelOpen(false)}
            />
        </ModuleLayout>
    );
}

function FulfilDialog({ order, onClose }) {
    const total = order.total_incl;
    const isAccountEligible = order.customer && !order.customer.is_walk_in;
    const [payments, setPayments] = useState([
        isAccountEligible
            ? { method: 'account', amount: total, reference: '' }
            : { method: 'cash', amount: total, tendered: total, reference: '' },
    ]);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    const paid = Math.round(payments.reduce((n, p) => n + Number(p.amount || 0), 0) * 100) / 100;
    const balance = Math.round((total - paid) * 100) / 100;
    const setP = (i, f, v) => setPayments((prev) => prev.map((p, idx) => (idx === i ? { ...p, [f]: v } : p)));

    function submit() {
        if (Math.abs(balance) > 0.001) { setError('Payments must equal the total.'); return; }
        setProcessing(true);
        router.post(route('sales.orders.fulfil', order.id), {
            payments: payments.map((p) => ({
                method: p.method, amount: Number(p.amount),
                tendered: p.method === 'cash' ? Number(p.tendered || p.amount) : null,
                reference: p.reference || null,
            })),
        }, { onError: (e) => { setError(Object.values(e)[0]); setProcessing(false); }, onFinish: () => setProcessing(false) });
    }

    const cell = 'rounded-lg border border-slate-300 px-2 py-1.5 text-sm';

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Fulfil and take payment">
            <div className="absolute inset-0 bg-slate-900/50" onClick={onClose} />
            <div className="relative w-full max-w-lg rounded-xl bg-white shadow-xl p-6 space-y-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-slate-900">Fulfil &amp; invoice</h2>
                    <span className="text-2xl font-bold tabular-nums text-slate-900">{money(total)}</span>
                </div>

                <div className="space-y-2">
                    {payments.map((p, i) => (
                        <div key={i} className="flex items-center gap-2">
                            <select value={p.method} onChange={(e) => setP(i, 'method', e.target.value)} className={`${cell} w-28`}>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="eft">EFT</option>
                                <option value="account" disabled={!isAccountEligible}>Account</option>
                            </select>
                            <input type="number" step="0.01" min="0" value={p.amount} aria-label="Amount" onChange={(e) => setP(i, 'amount', e.target.value)} className={`${cell} flex-1 text-right tabular-nums`} />
                            {p.method === 'cash'
                                ? <input type="number" step="0.01" min="0" value={p.tendered ?? ''} placeholder="Tendered" aria-label="Tendered" onChange={(e) => setP(i, 'tendered', e.target.value)} className={`${cell} w-28 text-right tabular-nums`} />
                                : <input value={p.reference} placeholder="Ref" aria-label="Reference" onChange={(e) => setP(i, 'reference', e.target.value)} className={`${cell} w-28`} />}
                            {payments.length > 1 && <button onClick={() => setPayments((prev) => prev.filter((_, idx) => idx !== i))} aria-label="Remove" className="p-1 text-slate-300 hover:text-red-600"><X className="w-4 h-4" /></button>}
                        </div>
                    ))}
                    <button onClick={() => setPayments((prev) => [...prev, { method: 'card', amount: Math.max(0, balance), reference: '' }])} className="text-xs font-medium text-orange-600 hover:text-orange-700">+ Split payment</button>
                </div>

                <div className={`text-sm text-right ${Math.abs(balance) > 0.001 ? 'text-amber-600' : 'text-emerald-600'}`}>
                    {balance > 0 ? `Balance due ${money(balance)}` : balance < 0 ? `Over ${money(-balance)}` : 'Settled'}
                </div>
                {error && <p className="text-sm text-red-600">{error}</p>}

                <div className="flex justify-end gap-3">
                    <button onClick={onClose} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button onClick={submit} disabled={processing || Math.abs(balance) > 0.001}
                        className="rounded-lg bg-orange-600 px-6 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        {processing ? 'Posting…' : 'Post Invoice'}
                    </button>
                </div>
            </div>
        </div>
    );
}
