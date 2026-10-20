import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import { HBarList } from '@/Components/Charts';
import EntityLink from '@/Components/EntityLink';

export default function SupplierReport({ spend, ageing, openPos, filters }) {
    function apply(params) {
        router.get(route('reports.suppliers.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Supplier Reports"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Suppliers' }]}>
            <Head title="Supplier Reports" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <label className="text-sm text-slate-500">Spend from</label>
                    <input type="date" value={filters.from} onChange={(e) => apply({ from: e.target.value })} className={input} />
                    <label className="text-sm text-slate-500">to</label>
                    <input type="date" value={filters.to} onChange={(e) => apply({ to: e.target.value })} className={input} />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Spend by supplier</h2>
                        <HBarList
                            data={spend.map((s) => ({ id: s.supplier_id, label: s.supplier, value: s.spend }))}
                            labelKey="label" valueKey="value"
                            renderLabel={(s) => <EntityLink type="supplier" id={s.id} label={s.label} />}
                        />
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Aged creditors</h2></div>
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Supplier</th><th className="px-2 py-2 text-right">Current</th><th className="px-2 py-2 text-right">30</th><th className="px-2 py-2 text-right">60</th><th className="px-4 py-2 text-right">90+</th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {ageing.rows.length === 0 ? <tr><td colSpan={5} className="px-4 py-6 text-center text-slate-400">Nothing outstanding.</td></tr> : ageing.rows.map((r) => (
                                    <tr key={r.supplier_id}><td className="px-4 py-2"><EntityLink type="supplier" id={r.supplier_id} label={r.supplier} /></td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.current.toFixed(2)}</td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.b30.toFixed(2)}</td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.b60.toFixed(2)}</td><td className={`px-4 py-2 text-right tabular-nums ${r.b90 > 0 ? 'text-red-600 font-medium' : 'text-slate-500'}`}>{r.b90.toFixed(2)}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Open purchase orders</h2></div>
                    <table className="w-full text-sm">
                        <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">PO</th><th className="px-2 py-2">Supplier</th><th className="px-2 py-2">Expected</th><th className="px-2 py-2 text-right">Total</th><th className="px-4 py-2">Status</th></tr></thead>
                        <tbody className="divide-y divide-slate-50">
                            {openPos.length === 0 ? <tr><td colSpan={5} className="px-4 py-6 text-center text-slate-400">No open purchase orders.</td></tr> : openPos.map((po) => (
                                <tr key={po.id} className={`hover:bg-slate-50 ${po.overdue ? 'bg-red-50/40' : ''}`}>
                                    <td className="px-4 py-2"><EntityLink type="purchase_order" id={po.id} label={po.po_number} mono className="font-semibold" /></td>
                                    <td className="px-2 py-2"><EntityLink type="supplier" id={po.supplier_id} label={po.supplier} /></td>
                                    <td className={`px-2 py-2 tabular-nums ${po.overdue ? 'text-red-600 font-medium' : 'text-slate-500'}`}>{po.expected_date ?? '—'}{po.overdue && ' ⚠'}</td>
                                    <td className="px-2 py-2 text-right tabular-nums"><MoneyDisplay amount={po.total} /></td>
                                    <td className="px-4 py-2"><StatusBadge status={po.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </ModuleLayout>
    );
}
