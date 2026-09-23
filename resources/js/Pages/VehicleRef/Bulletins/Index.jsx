import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import StatusBadge from '@/Components/StatusBadge';

export default function BulletinsIndex({ bulletins, makes }) {
    const { data, setData, post, processing, reset } = useForm({ title: '', make_id: '', category: 'service_note', body: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const badge = { fitment_warning: 'variance_review', recall: 'rejected', service_note: 'submitted' };
    return (
        <ModuleLayout navConfig={navConfig} title="Technical Bulletins" breadcrumbs={[{ label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') }, { label: 'Technical Bulletins' }]}>
            <Head title="Technical Bulletins" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 space-y-3">
                    {bulletins.data.length === 0 ? (
                        <div className="rounded-xl border border-slate-100 bg-white py-10 text-center text-sm text-slate-400"><AlertTriangle className="w-8 h-8 mx-auto mb-2 text-slate-300" />No bulletins published.</div>
                    ) : bulletins.data.map((b) => (
                        <div key={b.id} className="rounded-xl border border-slate-100 bg-white p-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-semibold text-slate-800">{b.title}</h3>
                                <StatusBadge status={badge[b.category] ?? 'submitted'} label={b.category.replace('_', ' ')} />
                            </div>
                            <div className="text-xs text-slate-400 mt-0.5">{b.make ?? 'All makes'} · {b.at}</div>
                            <p className="text-sm text-slate-600 mt-2 whitespace-pre-line">{b.body}</p>
                        </div>
                    ))}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('vehicle-ref.bulletins.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New bulletin</h2>
                    <input value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Title" className={input} />
                    <select value={data.make_id} onChange={(e) => setData('make_id', e.target.value)} className={input}><option value="">All makes</option>{makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}</select>
                    <select value={data.category} onChange={(e) => setData('category', e.target.value)} className={input}><option value="service_note">Service note</option><option value="fitment_warning">Fitment warning</option><option value="recall">Recall</option></select>
                    <textarea value={data.body} onChange={(e) => setData('body', e.target.value)} rows={4} placeholder="Details…" className={input} />
                    <button type="submit" disabled={processing || !data.title || !data.body} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Publish</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
