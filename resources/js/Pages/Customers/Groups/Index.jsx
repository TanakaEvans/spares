import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, UsersRound } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import EmptyState from '@/Components/EmptyState';

export default function CustomerGroupsIndex({ groups, priceLists }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', price_list_id: '' });

    function submit(e) {
        e.preventDefault();
        post(route('customers.groups.store'), { onSuccess: () => reset() });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Customer Groups"
            breadcrumbs={[
                { label: 'Customers', href: route('modules.show', 'customers') },
                { label: 'Customer Groups' },
            ]}
        >
            <Head title="Customer Groups" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2">
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        {groups.length === 0 ? (
                            <EmptyState icon={UsersRound} title="No groups yet" message="Groups let you attach a price list to many customers at once." />
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                        <th className="px-4 py-2.5">Group</th>
                                        <th className="px-4 py-2.5">Price list</th>
                                        <th className="px-4 py-2.5 text-right">Customers</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {groups.map((g) => (
                                        <tr key={g.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-2.5 font-semibold text-slate-800">{g.name}</td>
                                            <td className="px-4 py-2.5 text-slate-500">
                                                {g.price_list
                                                    ? <Link href={route('sales.price-lists.index')} className="text-slate-600 hover:text-orange-600">{g.price_list}</Link>
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums text-slate-500">{g.customers_count}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                <form onSubmit={submit} className="rounded-xl border border-slate-100 bg-white p-5 space-y-4 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New group</h2>
                    <div className="space-y-1">
                        <label htmlFor="g-name" className="block text-sm font-medium text-slate-700">Name</label>
                        <input id="g-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} placeholder="e.g. Trade Accounts" />
                        {errors.name && <p className="text-xs text-red-600">{errors.name}</p>}
                    </div>
                    <div className="space-y-1">
                        <label htmlFor="g-pl" className="block text-sm font-medium text-slate-700">Price list</label>
                        <select id="g-pl" value={data.price_list_id} onChange={(e) => setData('price_list_id', e.target.value)} className={input}>
                            <option value="">None (default retail)</option>
                            {priceLists.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select>
                    </div>
                    <button type="submit" disabled={processing || !data.name}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        <Plus className="w-4 h-4" />
                        Create group
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
