import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Upload } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const TABS = ['Orders', 'Receipts', 'Invoices', 'Payments', 'Returns', 'Price Lists', 'Contacts'];

export default function SupplierShow({ supplier }) {
    const [tab, setTab] = useState('Orders');

    const groups = [
        { title: 'Purchase orders', items: supplier.purchase_orders.map((po) => ({ type: 'purchase_order', id: po.id, label: po.po_number, mono: true })) },
        { title: 'Goods received', items: supplier.grns.map((g) => ({ type: 'grn', id: g.id, label: g.grn_number, mono: true })) },
        { title: 'Supplier invoices', items: supplier.invoices.map((i) => ({ type: 'supplier_invoice', id: i.id, label: i.supplier_ref, mono: true })) },
        { title: 'Payments', items: supplier.payments.map((p) => ({ type: 'payment', id: p.id, label: p.payment_number, mono: true })) },
        { title: 'Returns', items: supplier.returns.map((r) => ({ type: 'supplier_return', id: r.id, label: r.return_number, mono: true })) },
        { title: 'Active price list', items: supplier.active_price_list ? [{ type: 'price_list', id: null, label: supplier.active_price_list.name }] : [] },
        { title: 'Approved / preferred for', items: supplier.approved_parts.map((a) => ({ type: 'part', id: a.part_id, label: a.is_preferred ? `★ ${a.part_number}` : a.part_number, mono: true })) },
    ];

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={supplier.name}
            breadcrumbs={[
                { label: 'Suppliers', href: route('modules.show', 'suppliers') },
                { label: 'All Suppliers', href: route('suppliers.index') },
                { label: supplier.name },
            ]}
        >
            <Head title={supplier.name} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <h1 className="text-2xl font-bold text-slate-900">{supplier.name}</h1>
                                <StatusBadge status={supplier.is_active ? 'active' : 'inactive'} />
                                <span className="font-mono text-sm text-slate-400">{supplier.supplier_number}</span>
                                {supplier.currency && <span className="text-xs rounded bg-slate-100 px-1.5 py-0.5 text-slate-500">{supplier.currency}</span>}
                            </div>
                            <p className="text-sm text-slate-500 mt-1 capitalize">
                                {supplier.type}{supplier.city ? ` · ${supplier.city}` : ''}{supplier.country ? `, ${supplier.country}` : ''}
                                {supplier.phone ? ` · ${supplier.phone}` : ''}{supplier.email ? ` · ${supplier.email}` : ''}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Link
                                href={route('purchasing.orders.create', { supplier_id: supplier.id })}
                                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                            >
                                <Plus className="w-4 h-4" />
                                New PO
                            </Link>
                            <Link
                                href={route('suppliers.edit', supplier.id)}
                                className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                <Pencil className="w-4 h-4" />
                                Edit
                            </Link>
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">AP Balance</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800"><MoneyDisplay amount={supplier.ap_balance} /></div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Terms</div>
                            <div className="text-lg font-bold text-slate-800">Net {supplier.payment_terms_days}</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Lead Time</div>
                            <div className="text-lg font-bold text-slate-800">{supplier.lead_time_days} days</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">VAT No</div>
                            <div className="text-lg font-bold text-slate-800">{supplier.vat_number ?? '—'}</div>
                        </div>
                    </div>
                </div>

                <div className="flex gap-1 border-b border-slate-200">
                    {TABS.map((t) => (
                        <button
                            key={t}
                            onClick={() => setTab(t)}
                            className={`px-4 py-2 text-sm font-medium rounded-t-lg ${
                                tab === t ? 'bg-white border border-b-white border-slate-200 text-orange-700 -mb-px' : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            {t}
                        </button>
                    ))}
                </div>

                {tab === 'Orders' && (
                    <SimpleTable
                        headers={['PO No', 'Date', 'Status', 'Total']}
                        rows={supplier.purchase_orders}
                        render={(po) => (
                            <tr key={po.id} className="hover:bg-slate-50/60 cursor-pointer" onClick={() => router.visit(route('purchasing.orders.show', po.id))}>
                                <td className="px-6 py-2.5"><EntityLink type="purchase_order" id={po.id} label={po.po_number} mono className="font-semibold" /></td>
                                <td className="px-3 py-2.5 text-slate-500">{po.order_date}</td>
                                <td className="px-3 py-2.5"><StatusBadge status={po.status} /></td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={po.total} /></td>
                            </tr>
                        )}
                        empty="No purchase orders yet."
                    />
                )}

                {tab === 'Receipts' && (
                    <SimpleTable
                        headers={['GRN No', 'PO', 'Received', 'Status']}
                        rows={supplier.grns}
                        render={(g) => (
                            <tr key={g.id} className="hover:bg-slate-50/60 cursor-pointer" onClick={() => router.visit(route('purchasing.grns.show', g.id))}>
                                <td className="px-6 py-2.5"><EntityLink type="grn" id={g.id} label={g.grn_number} mono className="font-semibold" /></td>
                                <td className="px-3 py-2.5"><EntityLink type="purchase_order" id={g.po_id} label={g.po_number} mono muted /></td>
                                <td className="px-3 py-2.5 text-slate-500">{g.received_date}</td>
                                <td className="px-6 py-2.5"><StatusBadge status={g.status} /></td>
                            </tr>
                        )}
                        empty="Nothing received from this supplier yet."
                    />
                )}

                {tab === 'Invoices' && (
                    <SimpleTable
                        headers={['Supplier Ref', 'GRN', 'Date', 'Due', 'Status', 'Total']}
                        rows={supplier.invoices}
                        render={(inv) => (
                            <tr key={inv.id} className="hover:bg-slate-50/60 cursor-pointer" onClick={() => router.visit(route('purchasing.invoices.show', inv.id))}>
                                <td className="px-6 py-2.5"><EntityLink type="supplier_invoice" id={inv.id} label={inv.supplier_ref} mono className="font-semibold" /></td>
                                <td className="px-3 py-2.5"><EntityLink type="grn" id={inv.grn_id} label={inv.grn_number} mono muted /></td>
                                <td className="px-3 py-2.5 text-slate-500">{inv.invoice_date}</td>
                                <td className="px-3 py-2.5 text-slate-500">{inv.due_date}</td>
                                <td className="px-3 py-2.5"><StatusBadge status={inv.status} /></td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={inv.total} /></td>
                            </tr>
                        )}
                        empty="No invoices captured yet."
                    />
                )}

                {tab === 'Payments' && (
                    <SimpleTable
                        headers={['Payment No', 'Date', 'Amount']}
                        rows={supplier.payments}
                        render={(p) => (
                            <tr key={p.id}>
                                <td className="px-6 py-2.5"><EntityLink type="payment" id={p.id} label={p.payment_number} mono className="font-semibold" /></td>
                                <td className="px-3 py-2.5 text-slate-500">{p.payment_date}</td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={p.amount} /></td>
                            </tr>
                        )}
                        empty="No payments made to this supplier yet."
                    />
                )}

                {tab === 'Returns' && (
                    <SimpleTable
                        headers={['Return No', 'Status', 'Credited']}
                        rows={supplier.returns}
                        render={(r) => (
                            <tr key={r.id} className="hover:bg-slate-50/60 cursor-pointer" onClick={() => router.visit(route('purchasing.returns.show', r.id))}>
                                <td className="px-6 py-2.5"><EntityLink type="supplier_return" id={r.id} label={r.return_number} mono className="font-semibold" /></td>
                                <td className="px-3 py-2.5"><StatusBadge status={r.status} /></td>
                                <td className="px-6 py-2.5 text-right tabular-nums">{r.credit_total !== null ? <MoneyDisplay amount={r.credit_total} /> : <span className="text-slate-300">—</span>}</td>
                            </tr>
                        )}
                        empty="No returns to this supplier."
                    />
                )}

                {tab === 'Price Lists' && (
                    <div className="space-y-4">
                        <Link
                            href={route('suppliers.pricelists.import', supplier.id)}
                            className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                        >
                            <Upload className="w-4 h-4" />
                            Import Price List
                        </Link>
                        <SimpleTable
                            headers={['Name', 'Effective', 'Items', 'Matched', 'Unmatched', 'Status', '']}
                            rows={supplier.price_lists}
                            render={(pl) => (
                                <tr key={pl.id}>
                                    <td className="px-6 py-2.5 font-medium text-slate-700">{pl.name}</td>
                                    <td className="px-3 py-2.5 text-slate-500">{pl.effective_date}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{pl.items_count}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums text-green-700">{pl.matched_count}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums text-amber-600">{pl.unmatched_count}</td>
                                    <td className="px-3 py-2.5"><StatusBadge status={pl.status} /></td>
                                    <td className="px-6 py-2.5 text-right">
                                        {pl.status === 'pending' && (
                                            <button
                                                onClick={() => router.post(route('suppliers.pricelists.activate', [supplier.id, pl.id]), {}, { preserveScroll: true })}
                                                className="text-xs font-semibold text-orange-700 hover:underline"
                                            >
                                                Activate
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            )}
                            empty="No price lists imported yet."
                        />
                    </div>
                )}

                {tab === 'Contacts' && <ContactsTab supplier={supplier} />}

                <RelatedLinks groups={groups} />
            </div>
        </ModuleLayout>
    );
}

function SimpleTable({ headers, rows, render, empty }) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            {rows.length === 0 ? (
                <p className="px-6 py-10 text-center text-sm text-slate-400">{empty}</p>
            ) : (
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            {headers.map((h, i) => (
                                <th key={i} className={`px-3 py-3 first:pl-6 last:pr-6 ${['Total', 'Items', 'Matched', 'Unmatched', 'Amount', 'Credited'].includes(h) ? 'text-right' : ''}`}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">{rows.map(render)}</tbody>
                </table>
            )}
        </div>
    );
}

function ContactsTab({ supplier }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', position: '', email: '', phone: '', is_primary: false,
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div className="lg:col-span-2">
                <SimpleTable
                    headers={['Name', 'Position', 'Email', 'Phone', '']}
                    rows={supplier.contacts}
                    render={(c) => (
                        <tr key={c.id}>
                            <td className="px-6 py-2.5 font-medium text-slate-700">{c.name}</td>
                            <td className="px-3 py-2.5 text-slate-500">{c.position}</td>
                            <td className="px-3 py-2.5 text-slate-500">{c.email}</td>
                            <td className="px-3 py-2.5 text-slate-500">{c.phone}</td>
                            <td className="px-6 py-2.5">{c.is_primary ? <StatusBadge status="active" label="Primary" /> : null}</td>
                        </tr>
                    )}
                    empty="No contacts yet."
                />
            </div>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('suppliers.contacts.store', supplier.id), { preserveScroll: true, onSuccess: () => reset() });
                }}
                className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
            >
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Contact</h2>
                <FormField label="Name" htmlFor="ct-name" required error={errors.name}>
                    <input id="ct-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                </FormField>
                <FormField label="Position" htmlFor="ct-pos" error={errors.position}>
                    <input id="ct-pos" value={data.position} onChange={(e) => setData('position', e.target.value)} className={input} />
                </FormField>
                <FormField label="Email" htmlFor="ct-email" error={errors.email}>
                    <input id="ct-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={input} />
                </FormField>
                <FormField label="Phone" htmlFor="ct-phone" error={errors.phone}>
                    <input id="ct-phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className={input} />
                </FormField>
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={data.is_primary} onChange={(e) => setData('is_primary', e.target.checked)} className="rounded border-slate-300 text-orange-600" />
                    Primary contact
                </label>
                <button type="submit" disabled={processing || !data.name} className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                    Add Contact
                </button>
            </form>
        </div>
    );
}
