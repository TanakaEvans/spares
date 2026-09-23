import { Head, router, useForm } from '@inertiajs/react';
import { Ship } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

const NEXT = { ordered: 'in_transit', in_transit: 'customs', customs: 'cleared', cleared: 'received' };

export default function ImportsIndex({ shipments, suppliers }) {
    const { data, setData, post, processing, reset } = useForm({ shipment_ref: '', supplier_id: '', origin_country: '', eta: '', goods_value: '', freight: '', duty: '', notes: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Import Management" breadcrumbs={[{ label: 'Purchasing', href: route('modules.show', 'purchasing') }, { label: 'Import Management' }]}>
            <Head title="Import Management" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {shipments.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Ship className="w-8 h-8 mx-auto mb-2 text-slate-300" />No import shipments yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Shipment</th><th className="px-2 py-2">Supplier</th><th className="px-2 py-2">ETA</th><th className="px-2 py-2 text-right">Landed</th><th className="px-2 py-2">Status</th><th className="px-4 py-2"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {shipments.data.map((s) => (
                                    <tr key={s.id}>
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{s.shipment_ref}<span className="block text-xs text-slate-400">{s.origin_country}</span></td>
                                        <td className="px-2 py-2 text-slate-600">{s.supplier ?? '—'}</td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{s.eta ?? '—'}</td>
                                        <td className="px-2 py-2 text-right tabular-nums"><MoneyDisplay amount={s.landed} /></td>
                                        <td className="px-2 py-2"><StatusBadge status={s.status === 'received' ? 'received' : s.status === 'cleared' ? 'confirmed' : 'in_transit'} label={s.status.replace('_', ' ')} /></td>
                                        <td className="px-4 py-2 text-right">{NEXT[s.status] && <button onClick={() => router.post(route('purchasing.imports.transition', s.id), { status: NEXT[s.status] }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50 capitalize">→ {NEXT[s.status].replace('_', ' ')}</button>}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('purchasing.imports.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New shipment</h2>
                    <input value={data.shipment_ref} onChange={(e) => setData('shipment_ref', e.target.value)} placeholder="Shipment ref" className={input} />
                    <select value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} className={input}><option value="">Supplier…</option>{suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
                    <input value={data.origin_country} onChange={(e) => setData('origin_country', e.target.value)} placeholder="Origin country" className={input} />
                    <input type="date" value={data.eta} onChange={(e) => setData('eta', e.target.value)} className={input} />
                    <div className="grid grid-cols-3 gap-2">
                        <input type="number" step="0.01" value={data.goods_value} onChange={(e) => setData('goods_value', e.target.value)} placeholder="Goods" className={input} />
                        <input type="number" step="0.01" value={data.freight} onChange={(e) => setData('freight', e.target.value)} placeholder="Freight" className={input} />
                        <input type="number" step="0.01" value={data.duty} onChange={(e) => setData('duty', e.target.value)} placeholder="Duty" className={input} />
                    </div>
                    <button type="submit" disabled={processing || !data.shipment_ref} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
