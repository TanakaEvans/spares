import { Head, router } from '@inertiajs/react';
import { ArrowRight, Ship } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

const STEPS = ['ordered', 'in_transit', 'customs', 'cleared', 'received'];
const NEXT = { ordered: 'in_transit', in_transit: 'customs', customs: 'cleared', cleared: 'received' };

// Import shipment detail (Module 3.6) — the consignment's journey and its
// landed cost (goods + freight + duty).
export default function ImportShipmentShow({ shipment: s }) {
    const stepIndex = STEPS.indexOf(s.status);
    const badge = s.status === 'received' ? 'received' : s.status === 'cleared' ? 'confirmed' : 'in_transit';

    const groups = [
        { title: 'Supplier', items: s.supplier ? [{ type: 'supplier', id: s.supplier_id, label: s.supplier }] : [] },
    ];

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={s.shipment_ref}
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Import Management', href: route('purchasing.imports.index') },
                { label: s.shipment_ref },
            ]}
        >
            <Head title={s.shipment_ref} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2.5 flex-wrap">
                                <Ship className="w-6 h-6 text-slate-400" />
                                <h1 className="font-mono text-2xl font-bold text-slate-900">{s.shipment_ref}</h1>
                                <StatusBadge status={badge} label={s.status.replace('_', ' ')} />
                            </div>
                            <p className="text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-x-1.5">
                                {s.supplier ? <EntityLink type="supplier" id={s.supplier_id} label={s.supplier} className="font-medium" /> : <span>No supplier</span>}
                                {s.origin_country && <span>· from {s.origin_country}</span>}
                                {s.eta && <span>· ETA {s.eta}</span>}
                                {s.created_at && <span>· created {s.created_at}</span>}
                            </p>
                        </div>
                        {NEXT[s.status] && (
                            <button
                                onClick={() => router.post(route('purchasing.imports.transition', s.id), { status: NEXT[s.status] }, { preserveScroll: true })}
                                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 capitalize"
                            >
                                <ArrowRight className="w-4 h-4" />
                                Mark {NEXT[s.status].replace('_', ' ')}
                            </button>
                        )}
                    </div>

                    <ol className="mt-5 flex flex-wrap items-center gap-2 text-xs">
                        {STEPS.map((step, i) => (
                            <li key={step} className="flex items-center gap-2">
                                <span className={`rounded-md px-2.5 py-1 capitalize ${
                                    i < stepIndex ? 'bg-green-50 text-green-700 border border-green-200'
                                        : i === stepIndex ? 'bg-orange-600 text-white font-semibold'
                                            : 'border border-dashed border-slate-300 text-slate-400'
                                }`}>
                                    {step.replace('_', ' ')}
                                </span>
                                {i < STEPS.length - 1 && <ArrowRight className="h-3.5 w-3.5 text-slate-300" />}
                            </li>
                        ))}
                    </ol>

                    {s.notes && <p className="mt-4 text-sm text-slate-600 whitespace-pre-line">{s.notes}</p>}
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div className="px-6 py-3 border-b border-slate-100">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Landed cost</h2>
                    </div>
                    <table className="w-full text-sm">
                        <tbody className="divide-y divide-slate-50">
                            <tr>
                                <td className="px-6 py-2.5 text-slate-600">Goods value</td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={s.goods_value} /></td>
                            </tr>
                            <tr>
                                <td className="px-6 py-2.5 text-slate-600">Freight</td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={s.freight} /></td>
                            </tr>
                            <tr>
                                <td className="px-6 py-2.5 text-slate-600">Duty</td>
                                <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={s.duty} /></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200">
                                <td className="px-6 py-3 font-semibold text-slate-700">Landed total</td>
                                <td className="px-6 py-3 text-right font-bold text-lg tabular-nums"><MoneyDisplay amount={s.landed} /></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <RelatedLinks groups={groups} />
            </div>
        </ModuleLayout>
    );
}
