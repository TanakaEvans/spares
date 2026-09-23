import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { StatTile, LabelledBars } from '@/Components/Charts';

export default function InventoryReport({ stockValue, onHand, ageing, branches, filters }) {
    function apply(params) {
        router.get(route('reports.inventory.index'), Object.fromEntries(Object.entries({ ...filters, ...params }).filter(([, v]) => v)), { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Inventory Reports"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Inventory' }]}>
            <Head title="Inventory Reports" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center gap-3">
                    <select aria-label="Branch" value={filters.branch_id ?? ''} onChange={(e) => apply({ branch_id: e.target.value })} className={input}>
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <select aria-label="Filter" value={filters.filter ?? ''} onChange={(e) => apply({ filter: e.target.value })} className={input}>
                        <option value="">All stock</option>
                        <option value="below_reorder">Below reorder</option>
                        <option value="out">Out of stock</option>
                        <option value="negative">Negative</option>
                    </select>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <StatTile label="Stock value (cost)" value={stockValue.total} money />
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400">GL Inventory (1310)</div>
                        <div className="mt-1 text-2xl font-bold tabular-nums text-slate-900">{stockValue.gl_1310 !== null ? <MoneyDisplay amount={stockValue.gl_1310} /> : '—'}</div>
                    </div>
                    <div className={`rounded-xl border p-4 flex items-center gap-2 ${stockValue.reconciled ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
                        {stockValue.reconciled ? <CheckCircle2 className="w-5 h-5 text-emerald-600" /> : <AlertTriangle className="w-5 h-5 text-amber-600" />}
                        <span className={`text-sm font-medium ${stockValue.reconciled ? 'text-emerald-700' : 'text-amber-700'}`}>
                            {stockValue.reconciled ? 'Reconciled to GL' : 'Variance vs GL — investigate'}
                        </span>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white p-5">
                    <h2 className="text-sm font-semibold text-slate-700 mb-3">Stock ageing (by value)</h2>
                    <LabelledBars data={ageing.map((a) => ({ label: a.bracket, value: a.value }))} />
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Stock on hand</h2></div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2">Part</th><th className="px-2 py-2">Branch</th>
                                <th className="px-2 py-2 text-right">On hand</th><th className="px-2 py-2 text-right">Available</th>
                                <th className="px-2 py-2 text-right">AVCO</th><th className="px-4 py-2 text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {onHand.length === 0 ? (
                                <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-400">No stock matches.</td></tr>
                            ) : onHand.map((s, i) => (
                                <tr key={i} className={`hover:bg-slate-50 ${s.below_reorder ? 'bg-amber-50/50' : ''}`}>
                                    <td className="px-4 py-2"><span className="font-mono font-semibold text-slate-700">{s.part_number}</span><span className="block text-xs text-slate-400 truncate max-w-[18rem]">{s.description}</span></td>
                                    <td className="px-2 py-2 text-slate-500">{s.branch}</td>
                                    <td className={`px-2 py-2 text-right tabular-nums ${s.on_hand < 0 ? 'text-red-600' : 'text-slate-600'}`}>{s.on_hand}{s.below_reorder && <span className="ml-1 text-amber-600" title="Below reorder">▼</span>}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-500">{s.available}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-500">{s.avco.toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-700"><MoneyDisplay amount={s.value} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </ModuleLayout>
    );
}
