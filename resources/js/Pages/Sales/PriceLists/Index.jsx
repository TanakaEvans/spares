import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, ListOrdered, AlertTriangle, Star } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import StatusBadge from '@/Components/StatusBadge';

export default function PriceListsIndex({ priceLists, unpricedCount }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', type: 'custom' });

    function submit(e) {
        e.preventDefault();
        post(route('sales.price-lists.store'), { onSuccess: () => reset() });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const defaultList = priceLists.find((p) => p.is_default);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Price Lists"
            breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Price Lists' }]}
        >
            <Head title="Price Lists" />

            {unpricedCount > 0 && defaultList && (
                <Link href={route('sales.price-lists.show', defaultList.id)}
                    className="mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 hover:bg-amber-100">
                    <AlertTriangle className="w-4 h-4 shrink-0" />
                    {unpricedCount} active part{unpricedCount > 1 ? 's have' : ' has'} no retail price and cannot be sold. Set prices on the default list →
                </Link>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Name</th>
                                <th className="px-4 py-2.5">Type</th>
                                <th className="px-4 py-2.5 text-right">Priced parts</th>
                                <th className="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {priceLists.map((p) => (
                                <tr key={p.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-2.5">
                                        <Link href={route('sales.price-lists.show', p.id)} className="inline-flex items-center gap-2 font-semibold text-slate-800 hover:text-orange-600">
                                            {p.is_default && <Star className="w-3.5 h-3.5 text-amber-400 fill-amber-400" />}
                                            {p.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2.5 capitalize text-slate-500">{p.type}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-slate-500">{p.items_count}</td>
                                    <td className="px-4 py-2.5 text-right">{!p.is_active && <StatusBadge status="inactive" />}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <form onSubmit={submit} className="rounded-xl border border-slate-100 bg-white p-5 space-y-4 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New price list</h2>
                    <div className="space-y-1">
                        <label htmlFor="pl-name" className="block text-sm font-medium text-slate-700">Name</label>
                        <input id="pl-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} placeholder="e.g. Wholesale" />
                        {errors.name && <p className="text-xs text-red-600">{errors.name}</p>}
                    </div>
                    <div className="space-y-1">
                        <label htmlFor="pl-type" className="block text-sm font-medium text-slate-700">Type</label>
                        <select id="pl-type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}>
                            {['retail', 'trade', 'wholesale', 'custom'].map((t) => <option key={t} value={t}>{t}</option>)}
                        </select>
                    </div>
                    <button type="submit" disabled={processing || !data.name} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        <Plus className="w-4 h-4" />
                        Create
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
