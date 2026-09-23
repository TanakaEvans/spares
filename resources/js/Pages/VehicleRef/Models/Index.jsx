import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import SearchInput from '@/Components/SearchInput';

export default function ModelsIndex({ models, makes, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        make_id: filters.make_id ?? '', name: '', body_type: '', year_from: '', year_to: '',
    });

    function filter(params) {
        router.get(route('vehicle-ref.models.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true });
    }

    function add(e) {
        e.preventDefault();
        post(route('vehicle-ref.models.store'), {
            preserveScroll: true,
            onSuccess: () => reset('name', 'body_type', 'year_from', 'year_to'),
        });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Vehicle Models"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Models & Variants' },
            ]}
        >
            <Head title="Vehicle Models" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div className="lg:col-span-2 space-y-4">
                    <div className="flex flex-wrap gap-3">
                        <SearchInput
                            id="models-search"
                            value={filters.search ?? ''}
                            onSearch={(s) => filter({ search: s })}
                            placeholder="Search models…"
                            className="w-64"
                        />
                        <select
                            aria-label="Filter by make"
                            value={filters.make_id ?? ''}
                            onChange={(e) => filter({ make_id: e.target.value })}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">All makes</option>
                            {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </select>
                    </div>
                    <DataTable
                        columns={[
                            { key: 'make', label: 'Make', render: (m) => <span className="text-slate-500">{m.make?.name}</span> },
                            { key: 'name', label: 'Model', render: (m) => <span className="font-medium text-slate-800">{m.name}</span> },
                            { key: 'body_type', label: 'Body', render: (m) => <span className="capitalize text-slate-500">{m.body_type}</span> },
                            { key: 'years', label: 'Years', render: (m) => <span className="tabular-nums text-slate-500">{m.year_from ?? ''}–{m.year_to ?? 'now'}</span> },
                            { key: 'variants_count', label: 'Variants', align: 'right' },
                        ]}
                        rows={models.data}
                        pagination={models}
                        rowHref={(m) => route('vehicle-ref.models.show', m.id)}
                        emptyTitle="No models found"
                        emptyMessage="Adjust the filters, or add a model on the right."
                    />
                </div>

                <form onSubmit={add} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Model</h2>
                    <FormField label="Make" htmlFor="model-make" required error={errors.make_id}>
                        <select id="model-make" value={data.make_id} onChange={(e) => setData('make_id', e.target.value)} className={input}>
                            <option value="">Select make…</option>
                            {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Model name" htmlFor="model-name" required error={errors.name}>
                        <input id="model-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Body type" htmlFor="model-body" error={errors.body_type}>
                        <select id="model-body" value={data.body_type} onChange={(e) => setData('body_type', e.target.value)} className={input}>
                            <option value="">—</option>
                            {['pickup', 'sedan', 'hatchback', 'suv', 'van', 'mpv', 'wagon', 'truck', 'bus'].map((b) => (
                                <option key={b} value={b}>{b}</option>
                            ))}
                        </select>
                    </FormField>
                    <div className="grid grid-cols-2 gap-3">
                        <FormField label="Year from" htmlFor="model-yf" error={errors.year_from}>
                            <input id="model-yf" type="number" value={data.year_from} onChange={(e) => setData('year_from', e.target.value)} className={input} />
                        </FormField>
                        <FormField label="Year to" htmlFor="model-yt" error={errors.year_to} help="Blank = current">
                            <input id="model-yt" type="number" value={data.year_to} onChange={(e) => setData('year_to', e.target.value)} className={input} />
                        </FormField>
                    </div>
                    <button
                        type="submit"
                        disabled={processing || !data.make_id || !data.name}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        Add Model
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
