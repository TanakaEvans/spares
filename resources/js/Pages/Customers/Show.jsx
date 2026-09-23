import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Pencil, PauseCircle, PlayCircle, Receipt, Undo2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import PrintButton from '@/Components/PrintButton';

function Stat({ label, children }) {
    return (
        <div className="rounded-xl border border-slate-100 bg-white p-4">
            <div className="text-xs uppercase tracking-wider text-slate-400">{label}</div>
            <div className="mt-1 text-lg font-semibold text-slate-800">{children}</div>
        </div>
    );
}

export default function CustomerShow({ customer, documents }) {
    const [holdOpen, setHoldOpen] = useState(false);
    const holdForm = useForm({ on_hold: !customer.on_hold, hold_reason: '' });

    function submitHold(e) {
        e.preventDefault();
        holdForm.post(route('customers.hold', customer.id), { onSuccess: () => setHoldOpen(false) });
    }

    const overLimit = customer.available_credit < 0;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={customer.name}
            breadcrumbs={[
                { label: 'Customers', href: route('modules.show', 'customers') },
                { label: 'Customer Profiles', href: route('customers.index') },
                { label: customer.name },
            ]}
        >
            <Head title={customer.name} />

            <div className="space-y-6 max-w-5xl">
                <div className="flex flex-wrap items-start gap-4">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{customer.name}</h1>
                            <span className="font-mono text-sm text-slate-400">{customer.customer_number}</span>
                            {customer.on_hold && <StatusBadge status="on_hold" />}
                            {customer.is_walk_in && <span className="text-[10px] uppercase tracking-wide text-slate-400">walk-in</span>}
                        </div>
                        <p className="mt-1 text-sm text-slate-500 capitalize">
                            {customer.type}
                            {customer.trading_name && ` · trading as ${customer.trading_name}`}
                            {customer.phone && ` · ${customer.phone}`}
                            {customer.city && ` · ${customer.city}`}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {!customer.is_walk_in && (
                            <PrintButton href={route('customers.statement', customer.id)} label="Statement" />
                        )}
                        {!customer.is_walk_in && (
                            <button
                                onClick={() => { holdForm.setData('on_hold', !customer.on_hold); setHoldOpen(true); }}
                                className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                {customer.on_hold ? <PlayCircle className="w-4 h-4" /> : <PauseCircle className="w-4 h-4" />}
                                {customer.on_hold ? 'Release hold' : 'Place on hold'}
                            </button>
                        )}
                        <Link
                            href={route('customers.edit', customer.id)}
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            <Pencil className="w-4 h-4" />
                            Edit
                        </Link>
                    </div>
                </div>

                {customer.on_hold && customer.hold_reason && (
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        On credit hold — {customer.hold_reason}. Account sales are blocked; cash and card still work.
                    </div>
                )}

                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <Stat label="Account balance"><MoneyDisplay amount={customer.ar_balance} /></Stat>
                    <Stat label="Credit limit"><MoneyDisplay amount={customer.credit_limit} /></Stat>
                    <Stat label="Available credit">
                        <span className={overLimit ? 'text-red-600' : 'text-emerald-600'}>
                            <MoneyDisplay amount={customer.available_credit} />
                        </span>
                    </Stat>
                    <Stat label="Terms">{customer.payment_terms_days} days</Stat>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400 mb-2">Pricing</div>
                        <dl className="space-y-1 text-slate-600">
                            <div className="flex justify-between"><dt className="text-slate-400">Group</dt><dd>{customer.group ?? '—'}</dd></div>
                            <div className="flex justify-between"><dt className="text-slate-400">Effective price list</dt><dd>{customer.effective_price_list ?? '—'}</dd></div>
                            <div className="flex justify-between"><dt className="text-slate-400">VAT number</dt><dd>{customer.vat_number ?? '—'}</dd></div>
                        </dl>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400 mb-2">Contact</div>
                        <dl className="space-y-1 text-slate-600">
                            <div className="flex justify-between"><dt className="text-slate-400">Email</dt><dd>{customer.email ?? '—'}</dd></div>
                            <div className="flex justify-between"><dt className="text-slate-400">Address</dt><dd className="text-right">{customer.address ?? '—'}</dd></div>
                        </dl>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                        <h2 className="text-sm font-semibold text-slate-700">Account activity</h2>
                        <Link href={route('sales.pos')} className="text-sm font-medium text-orange-600 hover:text-orange-700">New sale →</Link>
                    </div>
                    {documents.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-slate-400">No invoices or credit notes yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-50">
                                    <th className="px-4 py-2">Document</th>
                                    <th className="px-4 py-2">Date</th>
                                    <th className="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {documents.map((d) => {
                                    const isCredit = d.document_type === 'credit_note';
                                    const href = isCredit ? route('sales.credit-notes.show', d.id) : route('sales.invoices.show', d.id);
                                    return (
                                        <tr key={`${d.document_type}-${d.id}`} className="hover:bg-slate-50">
                                            <td className="px-4 py-2.5">
                                                <Link href={href} className="inline-flex items-center gap-2 font-mono font-semibold text-slate-700 hover:text-orange-600">
                                                    {isCredit ? <Undo2 className="w-3.5 h-3.5 text-slate-400" /> : <Receipt className="w-3.5 h-3.5 text-slate-400" />}
                                                    {d.document_number}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-2.5 tabular-nums text-slate-500">{d.document_date}</td>
                                            <td className="px-4 py-2.5 text-right tabular-nums">
                                                <span className={isCredit ? 'text-red-600' : 'text-slate-700'}>
                                                    {isCredit ? '−' : ''}<MoneyDisplay amount={d.total_incl} />
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>

            {holdOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                    <div className="absolute inset-0 bg-slate-900/50" onClick={() => setHoldOpen(false)} />
                    <form onSubmit={submitHold} className="relative w-full max-w-md rounded-xl bg-white shadow-xl p-6">
                        <h2 className="text-lg font-semibold text-slate-900">
                            {customer.on_hold ? 'Release credit hold' : 'Place on credit hold'}
                        </h2>
                        {!customer.on_hold && (
                            <div className="mt-4">
                                <label htmlFor="hold-reason" className="block text-sm font-medium text-slate-700">Reason</label>
                                <input
                                    id="hold-reason"
                                    autoFocus
                                    value={holdForm.data.hold_reason}
                                    onChange={(e) => holdForm.setData('hold_reason', e.target.value)}
                                    placeholder="e.g. Overdue beyond 60 days"
                                    className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                />
                                {holdForm.errors.on_hold && <p className="mt-1 text-xs text-red-600">{holdForm.errors.on_hold}</p>}
                            </div>
                        )}
                        <div className="mt-6 flex justify-end gap-3">
                            <button type="button" onClick={() => setHoldOpen(false)} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                            <button type="submit" disabled={holdForm.processing}
                                className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                                {customer.on_hold ? 'Release' : 'Place on hold'}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </ModuleLayout>
    );
}
