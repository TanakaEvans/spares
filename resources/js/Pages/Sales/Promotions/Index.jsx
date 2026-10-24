import { Head, router, useForm } from '@inertiajs/react';
import { Percent, Tag } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import FormField from '@/Components/FormField';
import { fmt } from '@/Components/Pos/posMath';

/** Lifecycle from the dates: scheduled before start, expired after end. */
function lifecycle(p) {
    const today = new Date().toISOString().slice(0, 10);
    if (!p.is_active) return 'inactive';
    if (p.starts_at && p.starts_at > today) return 'pending';
    if (p.ends_at && p.ends_at < today) return 'expired';
    return 'active';
}

const statusLabel = { active: 'live', pending: 'scheduled', expired: 'ended', inactive: 'disabled' };

export default function PromotionsIndex({ promotions, categories }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', type: 'percent', value: '', applies_to: 'all', category_id: '', starts_at: '', ends_at: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Promotions & Discounts" breadcrumbs={[{ label: 'Sales & POS', href: route('modules.show', 'sales') }, { label: 'Promotions & Discounts' }]}>
            <Head title="Promotions & Discounts" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden h-fit">
                    {promotions.length === 0 ? (
                        <div className="py-12 text-center text-sm text-slate-400">
                            <Tag className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                            <p className="font-medium text-slate-600">No promotions yet.</p>
                            <p className="mt-1 text-xs">Create a percent or rand discount — for all parts or one category.</p>
                        </div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2.5">Promotion</th><th className="px-2 py-2.5">Discount</th><th className="px-2 py-2.5">Scope</th><th className="px-2 py-2.5">Window</th><th className="px-2 py-2.5">Status</th><th className="px-4 py-2.5 text-right"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {promotions.map((p) => {
                                    const state = lifecycle(p);
                                    return (
                                        <tr key={p.id}>
                                            <td className="px-4 py-2.5 font-medium text-slate-800">{p.name}</td>
                                            <td className="px-2 py-2.5 text-slate-600 font-semibold tabular-nums">{p.type === 'percent' ? `${p.value}%` : `R${fmt(p.value)}`}</td>
                                            <td className="px-2 py-2.5 text-slate-500">{p.applies_to === 'all' ? 'All parts' : <EntityLink type="category" id={p.category_id} label={p.category ?? 'Category'} muted />}</td>
                                            <td className="px-2 py-2.5 tabular-nums text-slate-400">{p.starts_at ?? '…'} → {p.ends_at ?? 'open'}</td>
                                            <td className="px-2 py-2.5"><StatusBadge status={state} label={statusLabel[state]} /></td>
                                            <td className="px-4 py-2.5 text-right"><button onClick={() => router.post(route('sales.promotions.toggle', p.id), {}, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">{p.is_active ? 'Disable' : 'Enable'}</button></td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('sales.promotions.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">New promotion</h2>
                    <FormField label="Name" error={errors.name}>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g. October filter special" className={input} />
                    </FormField>
                    <div className="grid grid-cols-2 gap-2">
                        <FormField label="Type">
                            <select value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}><option value="percent">Percent %</option><option value="fixed">Rand off</option></select>
                        </FormField>
                        <FormField label="Value" error={errors.value}>
                            <input type="number" step="0.01" min="0" value={data.value} onChange={(e) => setData('value', e.target.value)} placeholder={data.type === 'percent' ? '10' : '25.00'} className={`${input} text-right tabular-nums`} />
                        </FormField>
                    </div>
                    <FormField label="Applies to">
                        <select value={data.applies_to} onChange={(e) => setData('applies_to', e.target.value)} className={input}><option value="all">All parts</option><option value="category">A category</option></select>
                    </FormField>
                    {data.applies_to === 'category' && (
                        <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} aria-label="Category" className={input}>
                            <option value="">Category…</option>
                            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    )}
                    <div className="grid grid-cols-2 gap-2">
                        <FormField label="Starts"><input type="date" value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} className={input} /></FormField>
                        <FormField label="Ends"><input type="date" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} className={input} /></FormField>
                    </div>
                    <button type="submit" disabled={processing || !data.name || !data.value} className="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create promotion</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
