import { Head, useForm } from '@inertiajs/react';
import { Plus, UsersRound } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function CustomerGroupsIndex({ groups, priceLists, filters = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', price_list_id: '' });
    const filter = useListFilter('customers.groups.index', filters);

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

            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Group name…"
                searchClass="w-56"
            />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl mt-4">
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Group', sortable: 'name', render: (g) => <span className="font-semibold text-slate-800">{g.name}</span> },
                            { key: 'price_list', label: 'Price list', render: (g) => <EntityLink type="price_list" id={g.price_list_id} label={g.price_list ?? '—'} muted /> },
                            { key: 'customers_count', label: 'Customers', align: 'right', render: (g) => <span className="tabular-nums text-slate-500">{g.customers_count}</span> },
                        ]}
                        rows={groups.data}
                        pagination={groups}
                        sorting={{ filters, onFilter: filter }}
                        emptyTitle="No groups yet"
                        emptyMessage="Groups let you attach a price list to many customers at once."
                        emptyIcon={UsersRound}
                    />
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
