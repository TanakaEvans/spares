import { Head, Link, router } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';

export default function PriceComparisonIndex({ rows, filters }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Price Comparison" breadcrumbs={[{ label: 'Purchasing', href: route('modules.show', 'purchasing') }, { label: 'Price Comparison' }]}>
            <Head title="Price Comparison" />
            <div className="max-w-3xl space-y-4">
                <p className="text-sm text-slate-500">Compare what each supplier charges for the same part (from their imported price lists). The cheapest is highlighted.</p>
                <SearchInput id="pc-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('purchasing.price-comparison.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Search a part number or description…" className="w-96" />
                {rows.length === 0 ? (
                    <div className="rounded-xl border border-slate-100 bg-white py-12 text-center text-sm text-slate-400"><Scale className="w-8 h-8 mx-auto mb-2 text-slate-300" />{filters.search ? 'No supplier prices found for that part.' : 'Search a part to compare supplier prices.'}</div>
                ) : rows.map((r) => (
                    <div key={r.part_id} className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between">
                            <Link href={route('inventory.parts.show', r.part_id)} className="font-mono font-semibold text-slate-800 hover:text-orange-600">{r.part_number}</Link>
                            <span className="text-xs text-slate-400">{r.description}</span>
                        </div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {r.offers.map((o, i) => (
                                    <tr key={i} className={o.cost === r.best ? 'bg-emerald-50/50' : ''}>
                                        <td className="px-4 py-2">{o.supplier_id ? <Link href={route('suppliers.show', o.supplier_id)} className="text-slate-700 hover:text-orange-600">{o.supplier}</Link> : o.supplier}{o.cost === r.best && <span className="ml-2 text-[10px] font-semibold uppercase text-emerald-600">best</span>}</td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-700"><MoneyDisplay amount={o.cost} /></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
