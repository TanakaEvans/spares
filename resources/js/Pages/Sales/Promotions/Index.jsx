import { Head, router, useForm } from '@inertiajs/react';
import { Percent } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import StatusBadge from '@/Components/StatusBadge';

export default function PromotionsIndex({ promotions, categories }) {
    const { data, setData, post, processing, reset } = useForm({ name: '', type: 'percent', value: '', applies_to: 'all', category_id: '', starts_at: '', ends_at: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Promotions & Discounts" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Promotions & Discounts' }]}>
            <Head title="Promotions & Discounts" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {promotions.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Percent className="w-8 h-8 mx-auto mb-2 text-slate-300" />No promotions yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Promotion</th><th className="px-2 py-2">Discount</th><th className="px-2 py-2">Scope</th><th className="px-2 py-2">Window</th><th className="px-4 py-2 text-right"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {promotions.map((p) => (
                                    <tr key={p.id}>
                                        <td className="px-4 py-2 font-medium text-slate-800">{p.name} {p.is_active ? <StatusBadge status="active" /> : <StatusBadge status="inactive" />}</td>
                                        <td className="px-2 py-2 text-slate-600">{p.type === 'percent' ? `${p.value}%` : `$${p.value.toFixed(2)}`}</td>
                                        <td className="px-2 py-2 text-slate-500">{p.applies_to === 'all' ? 'All parts' : (p.category ?? 'Category')}</td>
                                        <td className="px-2 py-2 tabular-nums text-slate-400">{p.starts_at ?? '—'} → {p.ends_at ?? '—'}</td>
                                        <td className="px-4 py-2 text-right"><button onClick={() => router.post(route('sales.promotions.toggle', p.id), {}, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">{p.is_active ? 'Disable' : 'Enable'}</button></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('sales.promotions.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New promotion</h2>
                    <input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Name" className={input} />
                    <div className="grid grid-cols-2 gap-2">
                        <select value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}><option value="percent">Percent %</option><option value="fixed">Fixed $</option></select>
                        <input type="number" step="0.01" value={data.value} onChange={(e) => setData('value', e.target.value)} placeholder="Value" className={input} />
                    </div>
                    <select value={data.applies_to} onChange={(e) => setData('applies_to', e.target.value)} className={input}><option value="all">All parts</option><option value="category">A category</option></select>
                    {data.applies_to === 'category' && <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} className={input}><option value="">Category…</option>{categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>}
                    <div className="grid grid-cols-2 gap-2"><input type="date" value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} className={input} /><input type="date" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} className={input} /></div>
                    <button type="submit" disabled={processing || !data.name || !data.value} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
