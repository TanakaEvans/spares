import { Head, Link, useForm } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';

export default function CommsIndex({ notes, customers }) {
    const { data, setData, post, processing, reset } = useForm({ customer_id: '', channel: 'call', subject: '', body: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Communication Log" breadcrumbs={[{ label: 'Customers', href: route('modules.show', 'customers') }, { label: 'Communication Log' }]}>
            <Head title="Communication Log" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {notes.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><MessageSquare className="w-8 h-8 mx-auto mb-2 text-slate-300" />No communications logged.</div>
                    ) : (
                        <ul className="divide-y divide-slate-50">
                            {notes.data.map((n) => (
                                <li key={n.id} className="px-4 py-3">
                                    <div className="flex items-center justify-between">
                                        <Link href={route('customers.show', n.customer_id)} className="text-sm font-semibold text-slate-800 hover:text-orange-600">{n.customer}</Link>
                                        <span className="text-xs text-slate-400 capitalize">{n.channel} · {n.at}</span>
                                    </div>
                                    <div className="text-sm text-slate-700 mt-0.5">{n.subject}</div>
                                    {n.body && <div className="text-xs text-slate-500 mt-0.5">{n.body}</div>}
                                    {n.user && <div className="text-[11px] text-slate-400 mt-0.5">by {n.user}</div>}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('customers.comms.store'), { onSuccess: () => reset('subject', 'body') }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">Log communication</h2>
                    <select value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} className={input}><option value="">Customer…</option>{customers.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
                    <select value={data.channel} onChange={(e) => setData('channel', e.target.value)} className={input}><option value="call">Call</option><option value="email">Email</option><option value="visit">Visit</option><option value="note">Note</option></select>
                    <input value={data.subject} onChange={(e) => setData('subject', e.target.value)} placeholder="Subject" className={input} />
                    <textarea value={data.body} onChange={(e) => setData('body', e.target.value)} rows={3} placeholder="Details…" className={input} />
                    <button type="submit" disabled={processing || !data.customer_id || !data.subject} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Log it</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
