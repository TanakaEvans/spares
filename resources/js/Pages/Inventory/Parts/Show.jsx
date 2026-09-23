import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Pencil, Plus, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import FormField from '@/Components/FormField';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

const TABS = ['Stock', 'Fitments', 'Cross-refs', 'Supersession'];

export default function PartShow({ part, options }) {
    const [tab, setTab] = useState('Stock');

    const totalAvailable = part.stock.reduce((n, l) => n + l.qty_available, 0);
    const avgCost = part.stock.length ? part.stock[0].average_cost : 0;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={part.part_number}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Parts', href: route('inventory.parts.index') },
                { label: part.part_number },
            ]}
        >
            <Head title={part.part_number} />

            <div className="space-y-5">
                {/* Header + summary strip */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <h1 className="font-mono text-2xl font-bold text-slate-900">{part.part_number}</h1>
                                <StatusBadge status={part.is_active ? 'active' : 'inactive'} />
                                {part.is_oem && <StatusBadge status="posted" label="OEM" />}
                                {part.is_discontinued && <StatusBadge status="expired" label="Discontinued" />}
                            </div>
                            <p className="text-slate-700 mt-1">{part.description}</p>
                            <p className="text-sm text-slate-400 mt-0.5">
                                {part.brand}{part.category ? ` · ${part.category}` : ''}{part.unit ? ` · per ${part.unit}` : ''}
                                {part.oem_number ? ` · OEM ${part.oem_number}` : ''}
                            </p>
                        </div>
                        <Link
                            href={route('inventory.parts.edit', part.id)}
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            <Pencil className="w-4 h-4" />
                            Edit
                        </Link>
                    </div>

                    {part.superseded_by && (
                        <Link
                            href={route('inventory.parts.show', part.superseded_by.id)}
                            className="mt-4 flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-4 py-2.5 text-sm text-amber-800 hover:bg-amber-100"
                        >
                            This part is superseded — use{' '}
                            <span className="font-mono font-bold">{part.superseded_by.part_number}</span>
                            <ArrowRight className="w-4 h-4 ml-auto" />
                        </Link>
                    )}

                    <div className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Available</div>
                            <div className={`text-lg font-bold tabular-nums ${totalAvailable > 0 ? 'text-green-700' : 'text-slate-400'}`}>
                                {totalAvailable}
                            </div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Avg cost (AVCO)</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800">
                                <MoneyDisplay amount={avgCost} />
                            </div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Fitments</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800">{part.fitments.length}</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Cross-refs</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800">{part.cross_references.length}</div>
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="flex gap-1 border-b border-slate-200">
                    {TABS.map((t) => (
                        <button
                            key={t}
                            onClick={() => setTab(t)}
                            className={`px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ${
                                tab === t
                                    ? 'bg-white border border-b-white border-slate-200 text-orange-700 -mb-px'
                                    : 'text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            {t}
                        </button>
                    ))}
                </div>

                {tab === 'Stock' && <StockTab part={part} />}
                {tab === 'Fitments' && <FitmentsTab part={part} makes={options.makes} />}
                {tab === 'Cross-refs' && <CrossRefsTab part={part} brands={options.brands} />}
                {tab === 'Supersession' && <SupersessionTab part={part} />}
            </div>
        </ModuleLayout>
    );
}

function StockTab({ part }) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            {part.stock.length === 0 ? (
                <p className="px-6 py-10 text-center text-sm text-slate-400">
                    No stock records yet — stock appears here after the first receipt or opening balance.
                </p>
            ) : (
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th className="px-6 py-3">Branch</th>
                            <th className="px-3 py-3">Bin</th>
                            <th className="px-3 py-3 text-right">On hand</th>
                            <th className="px-3 py-3 text-right">Reserved</th>
                            <th className="px-3 py-3 text-right">Available</th>
                            <th className="px-6 py-3 text-right">Avg cost</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                        {part.stock.map((l, i) => (
                            <tr key={i}>
                                <td className="px-6 py-2.5 font-medium text-slate-700">{l.branch}</td>
                                <td className="px-3 py-2.5 font-mono text-slate-500">{l.bin ?? '—'}</td>
                                <td className="px-3 py-2.5 text-right tabular-nums">{l.qty_on_hand}</td>
                                <td className="px-3 py-2.5 text-right tabular-nums text-slate-400">{l.qty_reserved}</td>
                                <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-green-700">{l.qty_available}</td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={l.average_cost} /></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}

function FitmentsTab({ part, makes }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        make_id: '', year_from: '', year_to: '', engine_code: '', notes: '',
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                {part.fitments.length === 0 ? (
                    <p className="px-6 py-10 text-center text-sm text-slate-400">No fitments yet — add which vehicles this part fits.</p>
                ) : (
                    <table className="w-full text-sm">
                        <tbody className="divide-y divide-slate-50">
                            {part.fitments.map((f) => (
                                <tr key={f.id}>
                                    <td className="px-6 py-2.5">
                                        <span className="font-medium text-slate-800">{f.make}</span>
                                        {f.model && <span className="text-slate-600"> {f.model}</span>}
                                        {f.variant && <span className="text-slate-400"> · {f.variant}</span>}
                                    </td>
                                    <td className="px-3 py-2.5 tabular-nums text-slate-500">{f.years ?? 'all years'}</td>
                                    <td className="px-3 py-2.5 font-mono text-slate-500">{f.engine_code}</td>
                                    <td className="px-6 py-2.5 text-right">
                                        <button
                                            onClick={() => router.delete(route('inventory.parts.fitments.destroy', [part.id, f.id]), { preserveScroll: true })}
                                            aria-label="Remove fitment"
                                            className="p-1.5 rounded text-slate-300 hover:text-red-600 hover:bg-red-50"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('inventory.parts.fitments.store', part.id), { preserveScroll: true, onSuccess: () => reset() });
                }}
                className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
            >
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Fitment</h2>
                <FormField label="Make" htmlFor="fit-make" required error={errors.make_id}>
                    <select id="fit-make" value={data.make_id} onChange={(e) => setData('make_id', e.target.value)} className={input}>
                        <option value="">Select make…</option>
                        {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                    </select>
                </FormField>
                <div className="grid grid-cols-2 gap-3">
                    <FormField label="Year from" htmlFor="fit-yf" error={errors.year_from}>
                        <input id="fit-yf" type="number" value={data.year_from} onChange={(e) => setData('year_from', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Year to" htmlFor="fit-yt" error={errors.year_to}>
                        <input id="fit-yt" type="number" value={data.year_to} onChange={(e) => setData('year_to', e.target.value)} className={input} />
                    </FormField>
                </div>
                <FormField label="Engine code" htmlFor="fit-engine" error={errors.engine_code}>
                    <input id="fit-engine" value={data.engine_code} onChange={(e) => setData('engine_code', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                </FormField>
                <p className="text-xs text-slate-400">
                    Fine-grained model/variant fitments can be added after saving — make-level covers all models.
                </p>
                <button
                    type="submit"
                    disabled={processing || !data.make_id}
                    className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                >
                    <Plus className="w-4 h-4" />
                    Add Fitment
                </button>
            </form>
        </div>
    );
}

function CrossRefsTab({ part, brands }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        reference_number: '', brand_id: '', type: 'aftermarket',
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                {part.cross_references.length === 0 ? (
                    <p className="px-6 py-10 text-center text-sm text-slate-400">No cross-references — add the other numbers this part is known by.</p>
                ) : (
                    <table className="w-full text-sm">
                        <tbody className="divide-y divide-slate-50">
                            {part.cross_references.map((x) => (
                                <tr key={x.id}>
                                    <td className="px-6 py-2.5 font-mono font-semibold text-slate-800">{x.number}</td>
                                    <td className="px-3 py-2.5 text-slate-500">{x.brand ?? '—'}</td>
                                    <td className="px-3 py-2.5"><StatusBadge status={x.type === 'oem' ? 'posted' : 'draft'} label={x.type} /></td>
                                    <td className="px-6 py-2.5 text-right">
                                        <button
                                            onClick={() => router.delete(route('inventory.parts.crossrefs.destroy', [part.id, x.id]), { preserveScroll: true })}
                                            aria-label="Remove cross-reference"
                                            className="p-1.5 rounded text-slate-300 hover:text-red-600 hover:bg-red-50"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('inventory.parts.crossrefs.store', part.id), { preserveScroll: true, onSuccess: () => reset() });
                }}
                className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
            >
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Cross-Reference</h2>
                <FormField label="Reference number" htmlFor="xr-number" required error={errors.reference_number}>
                    <input id="xr-number" value={data.reference_number} onChange={(e) => setData('reference_number', e.target.value)} className={`${input} font-mono`} />
                </FormField>
                <FormField label="Brand" htmlFor="xr-brand" error={errors.brand_id}>
                    <select id="xr-brand" value={data.brand_id} onChange={(e) => setData('brand_id', e.target.value)} className={input}>
                        <option value="">—</option>
                        {brands.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                </FormField>
                <FormField label="Type" htmlFor="xr-type" required error={errors.type}>
                    <select id="xr-type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}>
                        <option value="oem">OEM number</option>
                        <option value="aftermarket">Aftermarket equivalent</option>
                        <option value="competitor">Competitor code</option>
                        <option value="ean">Barcode</option>
                    </select>
                </FormField>
                <button
                    type="submit"
                    disabled={processing || !data.reference_number}
                    className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                >
                    <Plus className="w-4 h-4" />
                    Add Reference
                </button>
            </form>
        </div>
    );
}

function SupersessionTab({ part }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        new_part_number: '', reason: '',
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-3 text-sm">
                {part.superseded_by ? (
                    <p className="text-slate-700">
                        <span className="font-mono font-semibold">{part.part_number}</span> is superseded by{' '}
                        <Link href={route('inventory.parts.show', part.superseded_by.id)} className="font-mono font-semibold text-orange-700 hover:underline">
                            {part.superseded_by.part_number}
                        </Link>
                        . Searches for the old number automatically point staff to the replacement.
                    </p>
                ) : (
                    <p className="text-slate-500">This part is not superseded.</p>
                )}
                {part.supersedes.length > 0 && (
                    <p className="text-slate-700">
                        It replaces:{' '}
                        {part.supersedes.map((s, i) => (
                            <span key={s.id}>
                                {i > 0 && ', '}
                                <Link href={route('inventory.parts.show', s.id)} className="font-mono text-orange-700 hover:underline">
                                    {s.part_number}
                                </Link>
                            </span>
                        ))}
                    </p>
                )}
            </div>

            {!part.superseded_by && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('inventory.parts.supersession.store', part.id), { preserveScroll: true, onSuccess: () => reset() });
                    }}
                    className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-3"
                >
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Mark as Superseded</h2>
                    <FormField label="Replaced by (part number)" htmlFor="ss-new" required error={errors.new_part_number} help="This part becomes discontinued; searches redirect to the new number.">
                        <input id="ss-new" value={data.new_part_number} onChange={(e) => setData('new_part_number', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                    </FormField>
                    <FormField label="Reason" htmlFor="ss-reason" error={errors.reason}>
                        <input id="ss-reason" value={data.reason} onChange={(e) => setData('reason', e.target.value)} className={input} />
                    </FormField>
                    <button
                        type="submit"
                        disabled={processing || !data.new_part_number}
                        className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                    >
                        Mark Superseded
                    </button>
                </form>
            )}
        </div>
    );
}
