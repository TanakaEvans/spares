import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Upload } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

const TABS = ['Orders', 'Invoices', 'Price Lists', 'Contacts'];

export default function SupplierShow({ supplier }) {
    const [tab, setTab] = useState('Orders');

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
                                <td className="px-6 py-2.5 font-mono font-semibold text-orange-700">{po.po_number}</td>
                                <td className="px-3 py-2.5 text-slate-500">{po.order_date}</td>
                                <td className="px-3 py-2.5"><StatusBadge status={po.status} /></td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={po.total} /></td>
                            </tr>
                        )}
                        empty="No purchase orders yet."
                    />
                )}

                {tab === 'Invoices' && (
                    <SimpleTable
                        headers={['Supplier Ref', 'Date', 'Due', 'Status', 'Total']}
                        rows={supplier.invoices}
                        render={(inv) => (
                            <tr key={inv.id}>
                                <td className="px-6 py-2.5 font-mono text-slate-700">{inv.supplier_ref}</td>
                                <td className="px-3 py-2.5 text-slate-500">{inv.invoice_date}</td>
                                <td className="px-3 py-2.5 text-slate-500">{inv.due_date}</td>
                                <td className="px-3 py-2.5"><StatusBadge status={inv.status} /></td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={inv.total} /></td>
                            </tr>
                        )}
                        empty="No invoices captured yet."
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
                                <th key={i} className={`px-3 py-3 first:pl-6 last:pr-6 ${['Total', 'Items', 'Matched', 'Unmatched'].includes(h) ? 'text-right' : ''}`}>{h}</th>
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
