import { Head, Link, router, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

const NEXT = { draft: 'submitted', submitted: 'acknowledged', acknowledged: 'approved', approved: 'credited' };

export default function WarrantyIndex({ claims, suppliers, jobs }) {
    const { data, setData, post, processing, reset } = useForm({ supplier_id: '', job_card_id: '', fault: '', claim_amount: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Warranty Claims" breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Warranty Claims' }]}>
            <Head title="Warranty Claims" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {claims.data.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><ShieldCheck className="w-8 h-8 mx-auto mb-2 text-slate-300" />No warranty claims yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Claim</th><th className="px-2 py-2">Supplier</th><th className="px-2 py-2">Job</th><th className="px-2 py-2 text-right">Claim</th><th className="px-2 py-2">Status</th><th className="px-4 py-2"></th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {claims.data.map((c) => (
                                    <tr key={c.id}>
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{c.claim_number}<span className="block text-xs text-slate-400 truncate max-w-[14rem]">{c.fault}</span></td>
                                        <td className="px-2 py-2 text-slate-600">{c.supplier ?? '—'}</td>
                                        <td className="px-2 py-2 text-slate-500">{c.job_number ?? '—'}</td>
                                        <td className="px-2 py-2 text-right tabular-nums"><MoneyDisplay amount={c.claim_amount} />{c.credit_amount > 0 && <span className="block text-xs text-emerald-600">+{c.credit_amount.toFixed(2)} credited</span>}</td>
                                        <td className="px-2 py-2"><StatusBadge status={c.status === 'credited' ? 'complete' : c.status === 'rejected' ? 'rejected' : c.status === 'approved' ? 'approved' : 'submitted'} label={c.status} /></td>
                                        <td className="px-4 py-2 text-right">
                                            {NEXT[c.status] && <button onClick={() => router.post(route('workshop.warranty.transition', c.id), { status: NEXT[c.status], credit_amount: c.claim_amount }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50 capitalize">→ {NEXT[c.status]}</button>}
                                            {c.status !== 'credited' && c.status !== 'rejected' && <button onClick={() => router.post(route('workshop.warranty.transition', c.id), { status: 'rejected' }, { preserveScroll: true })} className="ml-1 rounded border border-slate-300 px-2 py-1 text-xs text-red-500 hover:bg-red-50">Reject</button>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.warranty.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">Raise a claim</h2>
                    <select value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} className={input}><option value="">Supplier…</option>{suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
                    <select value={data.job_card_id} onChange={(e) => setData('job_card_id', e.target.value)} className={input}><option value="">Job (optional)…</option>{jobs.map((j) => <option key={j.id} value={j.id}>{j.job_number}</option>)}</select>
                    <textarea value={data.fault} onChange={(e) => setData('fault', e.target.value)} rows={3} placeholder="Fault / reason" className={input} />
                    <input type="number" step="0.01" value={data.claim_amount} onChange={(e) => setData('claim_amount', e.target.value)} placeholder="Claim amount" className={input} />
                    <button type="submit" disabled={processing || !data.fault || !data.claim_amount} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Raise claim</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
