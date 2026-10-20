import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { StatTile, HBarList } from '@/Components/Charts';
import EntityLink from '@/Components/EntityLink';

export default function SalesReport({ summary, byCategory, byCustomer, topParts, branches, filters }) {
    function apply(params) {
        router.get(route('reports.sales.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Sales Reports"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Sales' }]}>
            <Head title="Sales Reports" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <label className="text-sm text-slate-500">From</label>
                    <input type="date" value={filters.from} onChange={(e) => apply({ from: e.target.value })} className={input} />
                    <label className="text-sm text-slate-500">to</label>
                    <input type="date" value={filters.to} onChange={(e) => apply({ to: e.target.value })} className={input} />
                    <select aria-label="Branch" value={filters.branch_id ?? ''} onChange={(e) => apply({ branch_id: e.target.value })} className={input}>
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <StatTile label="Invoices" value={summary.count} />
                    <StatTile label="Gross sales" value={summary.gross} money tone="emerald" />
                    <StatTile label="Net of VAT" value={summary.net_of_vat} money />
                    <StatTile label="VAT collected" value={summary.vat} money />
                    <StatTile label="Avg. sale" value={summary.atv} money />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">By payment method</h2>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {summary.by_method.map((m) => (
                                    <tr key={m.method}><td className="py-1.5 capitalize text-slate-600">{m.method}</td><td className="py-1.5 text-right tabular-nums text-slate-700"><MoneyDisplay amount={m.total} /></td></tr>
                                ))}
                                {summary.by_method.length === 0 && <tr><td className="py-3 text-slate-400">No sales in range.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Sales by category</h2>
                        <HBarList
                            data={byCategory.map((c) => ({ id: c.category_id, label: c.category, value: c.revenue }))}
                            labelKey="label" valueKey="value"
                            renderLabel={(c) => <EntityLink type="category" id={c.id} label={c.label} />}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Top parts</h2>
                        <HBarList
                            data={topParts.map((p) => ({ id: p.part_id, label: p.part_number, description: p.description, value: p.revenue }))}
                            labelKey="label" valueKey="value"
                            renderLabel={(p) => <EntityLink type="part" id={p.id} label={p.label} mono title={p.description} />}
                        />
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Top customers</h2>
                        <HBarList
                            data={byCustomer.map((c) => ({ id: c.customer_id, label: c.customer, value: c.spend }))}
                            labelKey="label" valueKey="value"
                            renderLabel={(c) => <EntityLink type="customer" id={c.id} label={c.label} />}
                        />
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
