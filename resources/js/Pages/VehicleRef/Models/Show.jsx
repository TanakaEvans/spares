import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Car, PackageSearch, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';
import RowActions from '@/Components/RowActions';
import StatusBadge from '@/Components/StatusBadge';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import { BLANK_MODEL, BLANK_VARIANT, ModelDialog, VariantDialog, modelValues, variantValues } from './ModelDialogs';

export default function ModelShow({ model, fittingParts = [], fittingPartsTotal = 0, makes = [], engineCodes = [], variantUsage = {} }) {
    const modelEditor = useRecordEditor(BLANK_MODEL);
    const variantEditor = useRecordEditor(BLANK_VARIANT);
    const { confirmDelete, deleteDialog } = useDeleteRecord();

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
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold text-slate-900">
                                        <EntityLink type="make" id={model.make_id} label={model.make?.name} className="text-slate-900" title="Open this make" /> {model.name}
                                    </h1>
                                    {!model.is_active && <StatusBadge status="inactive" />}
                                </div>
                                <p className="text-sm text-slate-500 capitalize">
                                    {model.body_type ?? 'body n/a'}{model.generation ? ` · ${model.generation}` : ''} · {model.year_from ?? '?'}–{model.year_to ?? 'now'} · {model.variants.length} variant{model.variants.length === 1 ? '' : 's'}
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={route('vehicle-ref.fitment', { make_id: model.make_id, model_id: model.id })}
                                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                            >
                                <Search className="w-4 h-4" /> Parts that fit
                            </Link>
                            <button onClick={() => modelEditor.open(model, modelValues(model))} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <Pencil className="w-4 h-4" /> Edit
                            </button>
                            <button
                                onClick={() => confirmDelete({ url: route('vehicle-ref.models.destroy', model.id), label: `${model.make?.name} ${model.name}`, message: 'Its variants go with it. If parts or customer vehicles still reference it, it is deactivated instead.' })}
                                className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                            >
                                <Trash2 className="w-4 h-4" /> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Variants ({model.variants.length})</h2>
                        <button onClick={() => variantEditor.open()} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <Plus className="w-4 h-4" /> Add variant
                        </button>
                    </div>
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Variant', render: (v) => <span className="font-medium text-slate-800">{v.name}</span> },
                            { key: 'engine_code', label: 'Engine', render: (v) => <EntityLink type="engine" number={v.engine_code} label={v.engine_code} mono muted title="Engine code" /> },
                            { key: 'engine_size_cc', label: 'CC', align: 'right' },
                            { key: 'fuel_type', label: 'Fuel', render: (v) => <span className="capitalize text-slate-500">{v.fuel_type}</span> },
                            { key: 'transmission', label: 'Trans', render: (v) => <span className="capitalize text-slate-500">{v.transmission}</span> },
                            { key: 'drive', label: 'Drive', render: (v) => <span className="uppercase text-slate-500">{v.drive}</span> },
                            { key: 'years', label: 'Years', render: (v) => <span className="tabular-nums text-slate-500">{v.year_from ?? ''}–{v.year_to ?? 'now'}</span> },
                            {
                                key: 'actions', label: '', align: 'right', render: (v) => (
                                    <RowActions actions={[
                                        { label: 'Edit', icon: Pencil, onSelect: () => variantEditor.open(v, variantValues(v)) },
                                        { label: 'Parts that fit', icon: Search, href: route('vehicle-ref.fitment', { make_id: model.make_id, model_id: model.id, variant_id: v.id }) },
                                        {
                                            label: 'Delete', icon: Trash2, destructive: true,
                                            disabled: variantUsage[v.id] ? `${variantUsage[v.id]} part fitment(s) name this variant.` : false,
                                            onSelect: () => confirmDelete({ url: route('vehicle-ref.models.variants.destroy', [model.id, v.id]), label: `variant ${v.name}`, message: 'Nothing references this variant; it will be removed.' }),
                                        },
                                    ]} />
                                ),
                            },
                        ]}
                        rows={model.variants}
                        emptyIcon={Car}
                        emptyTitle="No variants yet"
                        emptyMessage="Add the engine/trim variants of this model."
                    />
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div className="flex items-center justify-between px-6 pt-4 pb-2 border-b border-slate-50">
                        <h2 className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <PackageSearch className="w-4 h-4" />
                            Parts that fit ({fittingPartsTotal})
                        </h2>
                        {fittingPartsTotal > fittingParts.length && (
                            <Link href={route('vehicle-ref.fitment', { make_id: model.make_id, model_id: model.id })} className="text-xs font-medium text-orange-700 hover:underline">
                                Full fitment lookup
                            </Link>
                        )}
                    </div>
                    {fittingParts.length === 0 ? (
                        <p className="px-6 py-8 text-center text-sm text-slate-400">No fitments recorded for this model yet — add them on each part's Fitments tab.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {fittingParts.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50/70">
                                        <td className="px-6 py-2.5 w-44"><EntityLink type="part" id={p.id} label={p.part_number} mono className="font-semibold" /></td>
                                        <td className="px-3 py-2.5 text-slate-700">{p.description}</td>
                                        <td className="px-3 py-2.5 w-32"><EntityLink type="brand" id={p.brand_id} label={p.brand} muted /></td>
                                        <td className="px-3 py-2.5 w-40"><EntityLink type="category" id={p.category_id} label={p.category} muted /></td>
                                        <td className="px-3 py-2.5 w-20">{!p.is_active && <StatusBadge status="inactive" />}</td>
                                        <td className="px-6 py-2.5 w-32 text-right tabular-nums">
                                            {p.qty_available > 0
                                                ? <span className="text-green-700 font-semibold">{p.qty_available} in stock</span>
                                                : <span className="text-slate-400">out of stock</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                <RelatedLinks
                    groups={[
                        { title: 'Make', items: [{ type: 'make', id: model.make_id, label: model.make?.name }] },
                        {
                            title: 'Engine codes',
                            items: [...new Set(model.variants.map((v) => v.engine_code).filter(Boolean))].map((code) => ({ type: 'engine', number: code, label: code, mono: true })),
                        },
                        { title: 'Fitting parts', items: fittingParts.slice(0, 30).map((p) => ({ type: 'part', id: p.id, label: p.part_number, mono: true })) },
                    ]}
                />
            </div>

            <ModelDialog editor={modelEditor} makes={makes} />
            <VariantDialog editor={variantEditor} model={model} engineCodes={engineCodes} />
            {deleteDialog}
        </ModuleLayout>
    );
}
