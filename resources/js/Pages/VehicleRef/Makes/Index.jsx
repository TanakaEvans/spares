import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function MakesIndex({ makes, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', code: '', country_of_origin: '',
    });

    function add(e) {
        e.preventDefault();
        post(route('vehicle-ref.makes.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Vehicle Makes"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Makes' },
            ]}
        >
            <Head title="Vehicle Makes" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div className="lg:col-span-2 space-y-4">
                    <SearchInput
                        id="makes-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => router.get(route('vehicle-ref.makes.index'), s ? { search: s } : {}, { preserveState: true })}
                        placeholder="Search makes…"
                        className="max-w-sm"
                    />
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Make', render: (m) => <span className="font-medium text-slate-800">{m.name}</span> },
                            { key: 'code', label: 'Code', render: (m) => <span className="font-mono text-slate-500">{m.code}</span> },
                            { key: 'country_of_origin', label: 'Origin' },
                            { key: 'models_count', label: 'Models', align: 'right' },
                            { key: 'is_active', label: 'Status', render: (m) => <StatusBadge status={m.is_active ? 'active' : 'inactive'} /> },
                        ]}
                        rows={makes.data}
                        pagination={makes}
                        rowHref={(m) => route('vehicle-ref.models.index', { make_id: m.id })}
                        emptyTitle="No makes yet"
                        emptyMessage="Add the first vehicle make, or run the ReferenceDataSeeder."
                    />
                </div>

                <form onSubmit={add} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Make</h2>
                    <FormField label="Name" htmlFor="make-name" required error={errors.name}>
                        <input id="make-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Code" htmlFor="make-code" required error={errors.code} help="Short unique code, e.g. TOY">
                        <input id="make-code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} className={`${input} font-mono uppercase`} maxLength={10} />
                    </FormField>
                    <FormField label="Country of origin" htmlFor="make-country" error={errors.country_of_origin}>
                        <input id="make-country" value={data.country_of_origin} onChange={(e) => setData('country_of_origin', e.target.value)} className={input} />
                    </FormField>
                    <button
                        type="submit"
                        disabled={processing || !data.name || !data.code}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        Add Make
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
