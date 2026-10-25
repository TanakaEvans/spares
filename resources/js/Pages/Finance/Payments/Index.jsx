import { Head, Link, router } from '@inertiajs/react';
import { Plus, Rows3, Play } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import EntityLink from '@/Components/EntityLink';
import ListToolbar from '@/Components/ListToolbar';
import { useListFilter } from '@/lib/listFilters';

export default function PaymentsIndex({ payments, ageing, filters }) {
    const { rows, totals } = ageing;
    const filter = useListFilter('finance.payments.index', filters);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Supplier Payments"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Supplier Payments' }]}
        >
            <Head title="Supplier Payments" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <label className="text-sm text-slate-500">Ageing as at</label>
                        <input type="date" value={filters.as_of}
                            onChange={(e) => router.get(route('finance.payments.index'), { as_of: e.target.value }, { preserveState: true, replace: true })}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('finance.payment-run.index')} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <Play className="w-4 h-4" /> Payment Run
                        </Link>
                        <Link href={route('finance.payments.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                            <Plus className="w-4 h-4" /> New Payment
                        </Link>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50"><h2 className="text-sm font-semibold text-slate-700">Aged Creditors — as at {ageing.as_of}</h2></div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2">Supplier</th>
                                <th className="px-2 py-2 text-right">Current</th>
                                <th className="px-2 py-2 text-right">30</th>
                                <th className="px-2 py-2 text-right">60</th>
                                <th className="px-2 py-2 text-right">90+</th>
                                <th className="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {rows.length === 0 ? (
                                <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-400">Nothing outstanding.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.supplier_id} className="hover:bg-slate-50">
                                    <td className="px-4 py-2"><EntityLink type="supplier" id={r.supplier_id} label={r.supplier} className="font-medium" /></td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.current.toFixed(2)}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.b30.toFixed(2)}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.b60.toFixed(2)}</td>
                                    <td className={`px-2 py-2 text-right tabular-nums ${r.b90 > 0 ? 'text-red-600 font-medium' : 'text-slate-600'}`}>{r.b90.toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right tabular-nums font-semibold text-slate-800">{r.total.toFixed(2)}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                                <td className="px-4 py-2.5 text-right">Totals</td>
                                <td className="px-2 py-2.5 text-right tabular-nums">{totals.current.toFixed(2)}</td>
                                <td className="px-2 py-2.5 text-right tabular-nums">{totals.b30.toFixed(2)}</td>
                                <td className="px-2 py-2.5 text-right tabular-nums">{totals.b60.toFixed(2)}</td>
                                <td className="px-2 py-2.5 text-right tabular-nums">{totals.b90.toFixed(2)}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums">{totals.total.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div>
                    <h2 className="text-sm font-semibold text-slate-700 mb-2">Payments</h2>
                    <ListToolbar
                        filters={filters}
                        onFilter={filter}
                        searchPlaceholder="Payment no, supplier or batch…"
                        searchClass="w-72"
                        dateRange
                        exportRoute="finance.payments.export"
                    >
                        <select aria-label="Payment method" value={filters.method ?? ''} onChange={(e) => filter({ method: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">All methods</option>
                            {['eft', 'cash', 'cheque', 'bank_transfer'].map((m) => <option key={m} value={m}>{m.replace('_', ' ')}</option>)}
                        </select>
                    </ListToolbar>
                    <div className="mt-3">
                        <DataTable
                            columns={[
                                { key: 'payment_number', label: 'Payment', sortable: 'payment_number', render: (p) => <EntityLink type="payment" id={p.id} label={p.payment_number} mono className="font-semibold" /> },
                                { key: 'supplier', label: 'Supplier', render: (p) => <EntityLink type="supplier" id={p.supplier_id} label={p.supplier} /> },
                                { key: 'payment_date', label: 'Date', sortable: 'payment_date', render: (p) => <span className="tabular-nums text-slate-500">{p.payment_date}</span> },
                                { key: 'method', label: 'Method', sortable: 'method', render: (p) => <span className="capitalize text-slate-500">{p.method?.replace('_', ' ')}</span> },
                                { key: 'batch', label: 'Batch', render: (p) => <span className="font-mono text-xs text-slate-400">{p.batch_ref ?? '—'}</span> },
                                { key: 'amount', label: 'Amount', align: 'right', sortable: 'amount', render: (p) => <MoneyDisplay amount={p.amount} /> },
                            ]}
                            rows={payments.data}
                            pagination={payments}
                            sorting={{ filters, onFilter: filter }}
                            rowHref={(p) => route('finance.payments.show', p.id)}
                            emptyTitle="No payments captured yet"
                            emptyIcon={Rows3}
                        />
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
