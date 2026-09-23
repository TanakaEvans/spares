import { Head, Link, router } from '@inertiajs/react';
import { Car, CheckCircle2, Search } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';

// The counter's killer feature: pick a vehicle, see everything that fits it.
export default function FitmentIndex({ makes, models, variants, selection, results }) {
    function pick(field, value) {
        const next = { ...selection, [field]: value || null };
        if (field === 'make_id') {
            next.model_id = null;
            next.variant_id = null;
        }
        if (field === 'model_id') next.variant_id = null;

        router.get(route('vehicle-ref.fitment'), Object.fromEntries(
            Object.entries(next).filter(([, v]) => v)
        ), { preserveState: true, preserveScroll: true });
    }

    const select = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-400';
    const categories = results ? Object.entries(results) : [];
    const totalParts = categories.reduce((n, [, parts]) => n + parts.length, 0);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Fitment Lookup"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Fitment Lookup' },
            ]}
        >
            <Head title="Fitment Lookup" />

            <div className="space-y-6">
                {/* Vehicle picker */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 bg-slate-600 rounded-xl flex items-center justify-center">
                            <Car className="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">What fits this vehicle?</h1>
                            <p className="text-sm text-slate-500">Pick the vehicle — every compatible part appears below, with live stock.</p>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label htmlFor="fit-make" className="block text-xs font-medium text-slate-500 mb-1">Make</label>
                            <select id="fit-make" value={selection.make_id ?? ''} onChange={(e) => pick('make_id', e.target.value)} className={select}>
                                <option value="">Select make…</option>
                                {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="fit-model" className="block text-xs font-medium text-slate-500 mb-1">Model</label>
                            <select id="fit-model" value={selection.model_id ?? ''} onChange={(e) => pick('model_id', e.target.value)} disabled={!selection.make_id} className={select}>
                                <option value="">All models</option>
                                {models.map((m) => (
                                    <option key={m.id} value={m.id}>
                                        {m.name}{m.year_from ? ` (${m.year_from}–${m.year_to ?? ''})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="fit-variant" className="block text-xs font-medium text-slate-500 mb-1">Variant</label>
                            <select id="fit-variant" value={selection.variant_id ?? ''} onChange={(e) => pick('variant_id', e.target.value)} disabled={!selection.model_id} className={select}>
                                <option value="">All variants</option>
                                {variants.map((v) => (
                                    <option key={v.id} value={v.id}>
                                        {v.name}{v.engine_code ? ` · ${v.engine_code}` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="fit-year" className="block text-xs font-medium text-slate-500 mb-1">Year</label>
                            <input
                                id="fit-year"
                                type="number"
                                min="1950"
                                max="2100"
                                placeholder="e.g. 2018"
                                defaultValue={selection.year ?? ''}
                                onBlur={(e) => pick('year', e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && pick('year', e.target.value)}
                                disabled={!selection.make_id}
                                className={select}
                            />
                        </div>
                    </div>
                </div>

                {/* Results */}
                {results === null ? (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                        <EmptyState
                            icon={Search}
                            title="Pick a vehicle to begin"
                            message="Choose at least the make. Narrow by model, variant and year for a precise fit list."
                        />
                    </div>
                ) : totalParts === 0 ? (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                        <EmptyState
                            icon={Car}
                            title="No fitment records for this vehicle yet"
                            message="Fitments are added on each part's detail page as the catalogue grows."
                        />
                    </div>
                ) : (
                    <div className="space-y-5">
                        <p className="text-sm text-slate-500">
                            <CheckCircle2 className="w-4 h-4 inline text-green-600 mr-1" />
                            {totalParts} compatible part{totalParts === 1 ? '' : 's'} found
                        </p>
                        {categories.map(([category, parts]) => (
                            <div key={category} className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                                <h2 className="px-6 pt-4 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-50">
                                    {category} ({parts.length})
                                </h2>
                                <table className="w-full text-sm">
                                    <tbody className="divide-y divide-slate-50">
                                        {parts.map((p) => (
                                            <tr key={p.id} className="hover:bg-slate-50/70 cursor-pointer"
                                                onClick={() => router.visit(route('inventory.parts.show', p.id))}>
                                                <td className="px-6 py-2.5 w-44">
                                                    <Link
                                                        href={route('inventory.parts.show', p.id)}
                                                        className="font-mono font-semibold text-orange-700 hover:underline"
                                                        onClick={(e) => e.stopPropagation()}
                                                    >
                                                        {p.part_number}
                                                    </Link>
                                                </td>
                                                <td className="px-3 py-2.5 text-slate-700">{p.description}</td>
                                                <td className="px-3 py-2.5 text-slate-400 w-32">{p.brand}</td>
                                                <td className="px-3 py-2.5 w-20">
                                                    {p.is_oem && <StatusBadge status="active" label="OEM" />}
                                                </td>
                                                <td className="px-6 py-2.5 w-32 text-right tabular-nums">
                                                    {p.qty_available > 0 ? (
                                                        <span className="text-green-700 font-semibold">{p.qty_available} in stock</span>
                                                    ) : (
                                                        <span className="text-slate-400">out of stock</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </ModuleLayout>
    );
}
