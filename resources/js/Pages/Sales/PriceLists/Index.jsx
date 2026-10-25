import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, ListOrdered, AlertTriangle, Star } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function PriceListsIndex({ priceLists, unpricedCount, filters = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', type: 'custom' });
    const filter = useListFilter('sales.price-lists.index', filters);

    function submit(e) {
        e.preventDefault();
        post(route('sales.price-lists.store'), { onSuccess: () => reset() });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const defaultList = priceLists.data.find((p) => p.is_default);

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

            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Price list name…"
                searchClass="w-56"
            />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl mt-4">
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Name', sortable: 'name', render: (p) => (
                                <span className="inline-flex items-center gap-2">
                                    {p.is_default && <Star className="w-3.5 h-3.5 text-amber-400 fill-amber-400" />}
                                    <EntityLink type="price_list" id={p.id} label={p.name} className="font-semibold" />
                                </span>
                            ) },
                            { key: 'type', label: 'Type', sortable: 'type', render: (p) => <span className="capitalize text-slate-500">{p.type}</span> },
                            { key: 'items_count', label: 'Priced parts', align: 'right', render: (p) => <span className="tabular-nums text-slate-500">{p.items_count}</span> },
                            { key: 'status', label: '', align: 'right', render: (p) => !p.is_active && <StatusBadge status="inactive" /> },
                        ]}
                        rows={priceLists.data}
                        pagination={priceLists}
                        sorting={{ filters, onFilter: filter }}
                        rowHref={(p) => route('sales.price-lists.show', p.id)}
                        emptyTitle="No price lists"
                        emptyIcon={ListOrdered}
                    />
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
