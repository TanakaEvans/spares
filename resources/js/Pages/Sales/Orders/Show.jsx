import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Banknote, MapPin, PackageCheck, Check } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';
import PaymentDialog from '@/Components/Sales/PaymentDialog';

const money = (n) => Number(n || 0).toFixed(2);

function Step({ done, current, label }) {
    return (
        <div className="flex items-center gap-2">
            <span className={`flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${done || current ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500'}`}>
                {done ? <Check className="h-3.5 w-3.5" /> : <span className="h-2 w-2 rounded-full bg-current" />}
            </span>
            <span className={`text-xs font-semibold ${current ? 'text-slate-900' : done ? 'text-emerald-700' : 'text-slate-400'}`}>{label}</span>
        </div>
    );
}

export default function OrderShow({ order }) {
    const [tenderOpen, setTenderOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);
    const [cancelling, setCancelling] = useState(false);
    const trace = [
        order.from_quote ? { type: 'quote', id: order.from_quote.id, label: order.from_quote.document_number } : null,
        { type: 'order', current: true, label: order.document_number },
        order.invoice
            ? { type: 'invoice', id: order.invoice.id, label: order.invoice.document_number }
            : order.status === 'cancelled' ? null : { type: 'invoice', pending: true, label: 'Invoice' },
    ].filter(Boolean);

    function cancel() {
        setCancelling(true);
        router.post(route('sales.orders.cancel', order.id), {}, { onFinish: () => { setCancelling(false); setCancelOpen(false); } });
    }

    const accountDefault = order.customer && !order.customer.is_walk_in && !order.customer.on_hold;

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
                            <StatusBadge status={order.status} label={order.status === 'confirmed' ? 'awaiting fulfilment' : undefined} />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            <EntityLink type="customer" id={order.customer?.id} label={order.customer?.name} className="font-medium" />
                            {' · '}{order.document_date}
                            {order.branch && <> · <EntityLink type="branch" id={order.branch.id} label={order.branch.name} muted /></>}
                            {order.from_quote && <> · from <EntityLink type="quote" id={order.from_quote.id} label={order.from_quote.document_number} mono /></>}
                        </p>
                        <div className="mt-3 flex items-center gap-4">
                            <Step done={order.status !== 'cancelled'} current={order.status === 'confirmed'} label="Confirmed · stock reserved" />
                            <span className="h-px w-6 bg-slate-300" />
                            <Step done={!!order.invoice} current={!!order.invoice} label={order.invoice ? `Invoiced ${order.invoice.document_number}` : 'Invoice'} />
                            {order.status === 'cancelled' && <StatusBadge status="cancelled" />}
                        </div>
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
                                <th className="px-2 py-2.5">Bin</th>
                                <th className="px-2 py-2.5 text-right">Qty</th>
                                <th className="px-2 py-2.5 text-right">Price</th>
                                <th className="px-4 py-2.5 text-right">Total (incl)</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {order.lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="px-4 py-2.5"><EntityLink type="part" id={l.part_id} label={l.part_number ?? l.description} mono className="font-semibold" /><span className="block text-xs text-slate-400">{l.description}</span></td>
                                    <td className="px-2 py-2.5 text-xs text-slate-500">
                                        {l.bin
                                            ? <span className="inline-flex items-center gap-1"><MapPin className="h-3 w-3" />{l.bin}</span>
                                            : <span className="text-slate-300">—</span>}
                                    </td>
                                    <td className="px-2 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-500">{money(l.unit_price)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums font-medium"><MoneyDisplay amount={l.line_total_incl} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex justify-end">
                    <dl className="w-56 space-y-1.5 text-sm">
                        <div className="flex justify-between text-slate-500"><dt>Subtotal (excl)</dt><dd className="tabular-nums">{money(order.subtotal_excl)}</dd></div>
                        <div className="flex justify-between text-slate-500"><dt>VAT</dt><dd className="tabular-nums">{money(order.vat_amount)}</dd></div>
                        <div className="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-slate-900"><dt>Total</dt><dd className="tabular-nums"><MoneyDisplay amount={order.total_incl} /></dd></div>
                    </dl>
                </div>

                <RelatedLinks
                    trace={trace}
                    groups={[
                        { title: 'Customer', items: order.customer ? [{ type: 'customer', id: order.customer.id, label: order.customer.name }] : [] },
                        { title: 'Parts', items: order.lines.filter((l) => l.part_id).map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
                        { title: 'Branch', items: order.branch ? [{ type: 'branch', id: order.branch.id, label: order.branch.name }] : [] },
                    ]}
                />
            </div>

            <PaymentDialog
                open={tenderOpen}
                onClose={() => setTenderOpen(false)}
                title={`Fulfil ${order.document_number}`}
                subtitle={`${order.customer?.name ?? 'Customer'} · posts the tax invoice and releases the reservation`}
                total={order.total_incl}
                customer={order.customer ?? {}}
                submitTo={route('sales.orders.fulfil', order.id)}
                submitLabel="Post invoice"
                initialMode={accountDefault ? 'account' : 'cash'}
            />

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
