import { Head, router } from '@inertiajs/react';
import { Scale } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import EntityLink from '@/Components/EntityLink';

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
                            <EntityLink type="part" id={r.part_id} label={r.part_number} mono className="font-semibold" />
                            <span className="text-xs text-slate-400">{r.description}</span>
                        </div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {r.offers.map((o, i) => {
                                    const worst = r.offers[r.offers.length - 1].cost;
                                    const saving = worst - o.cost;
                                    return (
                                    <tr key={i} className={o.cost === r.best ? 'bg-emerald-50/50' : ''}>
                                        <td className="px-4 py-2"><EntityLink type="supplier" id={o.supplier_id} label={o.supplier} />{o.cost === r.best && <span className="ml-2 text-[10px] font-semibold uppercase text-emerald-600">best</span>}</td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-700"><MoneyDisplay amount={o.cost} /></td>
                                        <td className="px-4 py-2 text-right w-36 text-xs">
                                            {saving > 0
                                                ? <span className="text-emerald-600">saves <MoneyDisplay amount={saving} /></span>
                                                : r.offers.length > 1 && o.cost === worst
                                                    ? <span className="text-red-500">costliest</span>
                                                    : <span className="text-slate-300">—</span>}
                                        </td>
                                    </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
