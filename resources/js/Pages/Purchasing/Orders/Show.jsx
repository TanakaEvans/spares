import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PackageCheck, Send, XCircle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import ConfirmDialog from '@/Components/ConfirmDialog';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PrintButton from '@/Components/PrintButton';
import StatusBadge from '@/Components/StatusBadge';

export default function OrderShow({ order }) {
    const [confirm, setConfirm] = useState(null); // {action, title, message, destructive}

    function transition(action) {
        router.post(route('purchasing.orders.transition', order.id), { action }, {
            preserveScroll: true,
            onFinish: () => setConfirm(null),
        });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={order.po_number}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Purchase Orders', href: route('purchasing.orders.index') },
                { label: order.po_number },
            ]}
        >
            <Head title={order.po_number} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <h1 className="font-mono text-2xl font-bold text-slate-900">{order.po_number}</h1>
                                <StatusBadge status={order.status} />
                            </div>
                            <p className="text-sm text-slate-500 mt-1">
                                <Link href={route('suppliers.show', order.supplier.id)} className="text-orange-700 hover:underline font-medium">
                                    {order.supplier.name}
                                </Link>
                                {' '}· deliver to {order.branch} · ordered {order.order_date}
                                {order.expected_date ? ` · expected ${order.expected_date}` : ''}
                                {order.buyer ? ` · buyer ${order.buyer}` : ''}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <PrintButton href={route('purchasing.orders.print', order.id)} label="Print PO" />
                            {order.status === 'draft' && (
                                <button
                                    onClick={() => setConfirm({ action: 'submit', title: `Submit ${order.po_number}?`, message: 'The order is sent to the supplier and its lines can no longer be edited.' })}
                                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                                >
                                    <Send className="w-4 h-4" />
                                    Submit to Supplier
                                </button>
                            )}
                            {order.status === 'submitted' && (
                                <button
                                    onClick={() => transition('confirm')}
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    Mark Confirmed
                                </button>
                            )}
                            {order.is_receivable && (
                                <Link
                                    href={route('purchasing.grns.create', order.id)}
                                    className="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                >
                                    <PackageCheck className="w-4 h-4" />
                                    Receive Goods
                                </Link>
                            )}
                            {['draft', 'submitted'].includes(order.status) && (
                                <button
                                    onClick={() => setConfirm({ action: 'cancel', title: `Cancel ${order.po_number}?`, message: 'The order is cancelled and cannot be received against.', destructive: true })}
                                    className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                >
                                    <XCircle className="w-4 h-4" />
                                    Cancel
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Part</th>
                                <th className="px-3 py-3">Description</th>
                                <th className="px-3 py-3 text-right">Ordered</th>
                                <th className="px-3 py-3 text-right">Received</th>
                                <th className="px-3 py-3 text-right">Outstanding</th>
                                <th className="px-3 py-3 text-right">Unit Cost</th>
                                <th className="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {order.lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-6 py-2.5">
                                        <Link href={route('inventory.parts.show', l.part_id)} className="font-mono font-semibold text-orange-700 hover:underline">
                                            {l.part_number}
                                        </Link>
                                    </td>
                                    <td className="px-3 py-2.5 text-slate-600">{l.description}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{l.qty_ordered}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums text-green-700">{l.qty_received}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums text-slate-500">{l.qty_outstanding}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.unit_cost} /></td>
                                    <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.line_total} /></td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200">
                                <td colSpan={6} className="px-6 py-3 text-right font-semibold text-slate-700">Order total</td>
                                <td className="px-6 py-3 text-right font-bold text-lg tabular-nums"><MoneyDisplay amount={order.total} /></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {order.grns.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Goods Received</h2>
                        <ul className="divide-y divide-slate-50 text-sm">
                            {order.grns.map((g) => (
                                <li key={g.id} className="py-2 flex items-center gap-3">
                                    <span className="font-mono font-semibold text-slate-700">{g.grn_number}</span>
                                    <span className="text-slate-400">{g.received_date}</span>
                                    <StatusBadge status={g.status} />
                                    <Link
                                        href={route('purchasing.invoices.create', g.id)}
                                        className="ml-auto text-xs font-semibold text-orange-700 hover:underline"
                                    >
                                        Capture supplier invoice →
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>

            <ConfirmDialog
                open={confirm !== null}
                title={confirm?.title}
                message={confirm?.message}
                destructive={confirm?.destructive}
                confirmLabel={confirm?.action === 'cancel' ? 'Cancel Order' : 'Submit'}
                onConfirm={() => transition(confirm.action)}
                onCancel={() => setConfirm(null)}
            />
        </ModuleLayout>
    );
}
