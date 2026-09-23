import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Car, Plus, Search } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';

export default function ModelShow({ model }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', engine_code: '', engine_size_cc: '', fuel_type: '',
        transmission: '', drive: '', year_from: '', year_to: '',
    });

    function addVariant(e) {
        e.preventDefault();
        post(route('vehicle-ref.models.variants.store', model.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`${model.make?.name} ${model.name}`}
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Models & Variants', href: route('vehicle-ref.models.index') },
                { label: `${model.make?.name} ${model.name}` },
            ]}
        >
            <Head title={`${model.make?.name} ${model.name}`} />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <Link
                        href={route('vehicle-ref.models.index')}
                        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600 mb-3"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        All models
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center">
                                <Car className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900">{model.make?.name} {model.name}</h1>
                                <p className="text-sm text-slate-500 capitalize">
                                    {model.body_type} · {model.year_from ?? '?'}–{model.year_to ?? 'now'} · {model.variants.length} variant{model.variants.length === 1 ? '' : 's'}
                                </p>
                            </div>
                        </div>
                        <Link
                            href={route('vehicle-ref.fitment', { make_id: model.make_id, model_id: model.id })}
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            <Search className="w-4 h-4" />
                            Parts that fit this model
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div className="lg:col-span-2">
                        <DataTable
                            columns={[
                                { key: 'name', label: 'Variant', render: (v) => <span className="font-medium text-slate-800">{v.name}</span> },
                                { key: 'engine_code', label: 'Engine', render: (v) => <span className="font-mono text-slate-500">{v.engine_code}</span> },
                                { key: 'engine_size_cc', label: 'CC', align: 'right' },
                                { key: 'fuel_type', label: 'Fuel', render: (v) => <span className="capitalize text-slate-500">{v.fuel_type}</span> },
                                { key: 'transmission', label: 'Trans', render: (v) => <span className="capitalize text-slate-500">{v.transmission}</span> },
                                { key: 'drive', label: 'Drive', render: (v) => <span className="uppercase text-slate-500">{v.drive}</span> },
                                { key: 'years', label: 'Years', render: (v) => <span className="tabular-nums text-slate-500">{v.year_from ?? ''}–{v.year_to ?? 'now'}</span> },
                            ]}
                            rows={model.variants}
                            emptyTitle="No variants yet"
                            emptyMessage="Add the engine/trim variants of this model on the right."
                        />
                    </div>

                    <form onSubmit={addVariant} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Variant</h2>
                        <FormField label="Variant name" htmlFor="var-name" required error={errors.name} help="e.g. 2.8 GD-6 4x4 AT">
                            <input id="var-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                        </FormField>
                        <div className="grid grid-cols-2 gap-3">
                            <FormField label="Engine code" htmlFor="var-engine" error={errors.engine_code}>
                                <input id="var-engine" value={data.engine_code} onChange={(e) => setData('engine_code', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                            </FormField>
                            <FormField label="Size (cc)" htmlFor="var-cc" error={errors.engine_size_cc}>
                                <input id="var-cc" type="number" value={data.engine_size_cc} onChange={(e) => setData('engine_size_cc', e.target.value)} className={input} />
                            </FormField>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <FormField label="Fuel" htmlFor="var-fuel" error={errors.fuel_type}>
                                <select id="var-fuel" value={data.fuel_type} onChange={(e) => setData('fuel_type', e.target.value)} className={input}>
                                    <option value="">—</option>
                                    {['petrol', 'diesel', 'hybrid', 'electric', 'lpg'].map((f) => <option key={f} value={f}>{f}</option>)}
                                </select>
                            </FormField>
                            <FormField label="Transmission" htmlFor="var-trans" error={errors.transmission}>
                                <select id="var-trans" value={data.transmission} onChange={(e) => setData('transmission', e.target.value)} className={input}>
                                    <option value="">—</option>
                                    {['manual', 'automatic', 'cvt', 'amt'].map((t) => <option key={t} value={t}>{t}</option>)}
                                </select>
                            </FormField>
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <FormField label="Drive" htmlFor="var-drive" error={errors.drive}>
                                <select id="var-drive" value={data.drive} onChange={(e) => setData('drive', e.target.value)} className={input}>
                                    <option value="">—</option>
                                    {['4x2', '4x4', 'awd', 'fwd', 'rwd'].map((d) => <option key={d} value={d}>{d}</option>)}
                                </select>
                            </FormField>
                            <FormField label="From" htmlFor="var-yf" error={errors.year_from}>
                                <input id="var-yf" type="number" value={data.year_from} onChange={(e) => setData('year_from', e.target.value)} className={input} />
                            </FormField>
                            <FormField label="To" htmlFor="var-yt" error={errors.year_to}>
                                <input id="var-yt" type="number" value={data.year_to} onChange={(e) => setData('year_to', e.target.value)} className={input} />
                            </FormField>
                        </div>
                        <button
                            type="submit"
                            disabled={processing || !data.name}
                            className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 transition-colors"
                        >
                            <Plus className="w-4 h-4" />
                            Add Variant
                        </button>
                    </form>
                </div>
            </div>
        </ModuleLayout>
    );
}
