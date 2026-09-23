import { Head, router, useForm } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import StatusBadge from '@/Components/StatusBadge';

export default function DeliveryNotesIndex({ notes, customers, branches }) {
    const { data, setData, post, processing, reset } = useForm({ customer_id: '', branch_id: branches[0]?.id ?? '', delivery_date: new Date().toISOString().slice(0, 10), driver: '', vehicle_reg: '', address: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Delivery Notes" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Delivery Notes' }]}>
            <Head title="Delivery Notes" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {notes.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Truck className="w-8 h-8 mx-auto mb-2 text-slate-300" />No delivery notes yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Delivery</th><th className="px-2 py-2">Customer</th><th className="px-2 py-2">Date</th><th className="px-2 py-2">Driver</th><th className="px-2 py-2">Status</th><th className="px-4 py-2"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {notes.data.map((d) => (
                                    <tr key={d.id}>
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{d.delivery_number}</td>
                                        <td className="px-2 py-2 text-slate-600">{d.customer}</td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{d.delivery_date}</td>
                                        <td className="px-2 py-2 text-slate-500">{d.driver ?? '—'}{d.vehicle_reg ? ` · ${d.vehicle_reg}` : ''}</td>
                                        <td className="px-2 py-2"><StatusBadge status={d.status === 'dispatched' ? 'in_transit' : d.status === 'delivered' ? 'complete' : 'draft'} label={d.status} /></td>
                                        <td className="px-4 py-2 text-right">{d.status === 'dispatched' && <button onClick={() => router.post(route('sales.delivery-notes.transition', d.id), { status: 'delivered' }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">Mark delivered</button>}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('sales.delivery-notes.store'), { onSuccess: () => reset('driver', 'vehicle_reg', 'address') }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New delivery note</h2>
                    <select value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} className={input}><option value="">Customer…</option>{customers.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
                    <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>{branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select>
                    <input type="date" value={data.delivery_date} onChange={(e) => setData('delivery_date', e.target.value)} className={input} />
                    <input value={data.driver} onChange={(e) => setData('driver', e.target.value)} placeholder="Driver" className={input} />
                    <input value={data.vehicle_reg} onChange={(e) => setData('vehicle_reg', e.target.value)} placeholder="Vehicle reg" className={input} />
                    <input value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="Delivery address" className={input} />
                    <button type="submit" disabled={processing || !data.customer_id} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
