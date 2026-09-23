import { Head, Link } from '@inertiajs/react';
import { TrendingUp } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import MoneyDisplay from '@/Components/MoneyDisplay';

export default function PerformanceIndex({ rows }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Supplier Performance"
            breadcrumbs={[{ label: 'Suppliers', href: route('modules.show', 'suppliers') }, { label: 'Supplier Performance' }]}>
            <Head title="Supplier Performance" />
            <div className="rounded-xl border border-slate-100 bg-white overflow-hidden max-w-4xl">
                <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Delivery, spend & lead time</h2></div>
                {rows.length === 0 ? (
                    <div className="py-10 text-center text-sm text-slate-400"><TrendingUp className="w-8 h-8 mx-auto mb-2 text-slate-300" />No purchasing activity yet.</div>
                ) : (
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2">Supplier</th>
                                <th className="px-2 py-2 text-right">POs</th>
                                <th className="px-2 py-2 text-right">GRNs</th>
                                <th className="px-2 py-2 text-right">On-time</th>
                                <th className="px-2 py-2 text-right">Avg lead</th>
                                <th className="px-4 py-2 text-right">Spend</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {rows.map((r) => (
                                <tr key={r.supplier_id} className="hover:bg-slate-50">
                                    <td className="px-4 py-2"><Link href={route('suppliers.show', r.supplier_id)} className="font-medium text-slate-700 hover:text-orange-600">{r.supplier}</Link></td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.po_count}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.grn_count}</td>
                                    <td className={`px-2 py-2 text-right tabular-nums font-medium ${r.on_time_pct === null ? 'text-slate-400' : r.on_time_pct >= 90 ? 'text-emerald-600' : r.on_time_pct >= 70 ? 'text-amber-600' : 'text-red-600'}`}>{r.on_time_pct === null ? '—' : `${r.on_time_pct}%`}</td>
                                    <td className="px-2 py-2 text-right tabular-nums text-slate-500">{r.avg_lead_days === null ? '—' : `${r.avg_lead_days}d`}</td>
                                    <td className="px-4 py-2 text-right tabular-nums text-slate-700"><MoneyDisplay amount={r.spend} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </ModuleLayout>
    );
}
