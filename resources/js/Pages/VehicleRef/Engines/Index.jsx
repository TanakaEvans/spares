import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import FormField from '@/Components/FormField';
import SearchInput from '@/Components/SearchInput';

export default function EnginesIndex({ engines, makes, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '', make_id: '', description: '', capacity_cc: '', fuel_type: '', aspiration: '', cylinders: '',
    });

    function add(e) {
        e.preventDefault();
        post(route('vehicle-ref.engines.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Engine Codes"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Engine Codes' },
            ]}
        >
            <Head title="Engine Codes" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div className="lg:col-span-2 space-y-4">
                    <SearchInput
                        id="engines-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => router.get(route('vehicle-ref.engines.index'), s ? { search: s } : {}, { preserveState: true })}
                        placeholder="Search code or description… e.g. 1GD-FTV"
                        className="max-w-sm"
                    />
                    <DataTable
                        columns={[
                            { key: 'code', label: 'Code', render: (e) => <EntityLink type="engine" number={e.code} label={e.code} mono className="font-semibold" title="Filter engine codes" /> },
                            { key: 'make', label: 'Make', render: (e) => <EntityLink type="make" id={e.make_id} label={e.make?.name ?? '—'} params={e.make_id ? { make_id: e.make_id } : undefined} muted /> },
                            { key: 'description', label: 'Description', render: (e) => <span className="text-slate-600">{e.description}</span> },
                            { key: 'capacity_cc', label: 'CC', align: 'right' },
                            { key: 'fuel_type', label: 'Fuel', render: (e) => <span className="capitalize text-slate-500">{e.fuel_type}</span> },
                            {
                                key: 'usage', label: 'Used by', align: 'right',
                                render: (e) => (
                                    <span className="text-xs text-slate-500 whitespace-nowrap">
                                        {e.models_count > 0 ? (
                                            <Link href={route('vehicle-ref.models.index', { search: e.code })} onClick={(ev) => ev.stopPropagation()} className="hover:text-orange-600 hover:underline">
                                                {e.models_count} model{e.models_count === 1 ? '' : 's'}
                                            </Link>
                                        ) : <span className="text-slate-300">no models</span>}
                                        {' · '}
                                        {e.parts_count > 0 && e.make_id ? (
                                            <Link href={route('vehicle-ref.fitment', { make_id: e.make_id })} onClick={(ev) => ev.stopPropagation()} className="hover:text-orange-600 hover:underline">
                                                {e.parts_count} part{e.parts_count === 1 ? '' : 's'}
                                            </Link>
                                        ) : <span className={e.parts_count > 0 ? '' : 'text-slate-300'}>{e.parts_count > 0 ? `${e.parts_count} parts` : 'no parts'}</span>}
                                    </span>
                                ),
                            },
                        ]}
                        rows={engines.data}
                        pagination={engines}
                        emptyTitle="No engine codes found"
                    />
                </div>

                <form onSubmit={add} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Engine Code</h2>
                    <FormField label="Code" htmlFor="eng-code" required error={errors.code} help="e.g. 1GD-FTV">
                        <input id="eng-code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                    </FormField>
                    <FormField label="Make" htmlFor="eng-make" error={errors.make_id}>
                        <select id="eng-make" value={data.make_id} onChange={(e) => setData('make_id', e.target.value)} className={input}>
                            <option value="">—</option>
                            {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Description" htmlFor="eng-desc" error={errors.description}>
                        <input id="eng-desc" value={data.description} onChange={(e) => setData('description', e.target.value)} className={input} />
                    </FormField>
                    <div className="grid grid-cols-2 gap-3">
                        <FormField label="Capacity (cc)" htmlFor="eng-cc" error={errors.capacity_cc}>
                            <input id="eng-cc" type="number" value={data.capacity_cc} onChange={(e) => setData('capacity_cc', e.target.value)} className={input} />
                        </FormField>
                        <FormField label="Fuel" htmlFor="eng-fuel" error={errors.fuel_type}>
                            <select id="eng-fuel" value={data.fuel_type} onChange={(e) => setData('fuel_type', e.target.value)} className={input}>
                                <option value="">—</option>
                                {['petrol', 'diesel', 'hybrid', 'electric', 'lpg'].map((f) => <option key={f} value={f}>{f}</option>)}
                            </select>
                        </FormField>
                    </div>
                    <button
                        type="submit"
                        disabled={processing || !data.code}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        Add Engine Code
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
