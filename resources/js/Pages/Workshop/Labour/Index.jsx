import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Timer } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';

function RateForm({ code, makes }) {
    const { data, setData, post, processing, reset } = useForm({ make_id: '', flat_rate: '' });
    return (
        <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.labour.rates.store', code.id), { preserveScroll: true, onSuccess: () => reset() }); }} className="flex items-center gap-2 mt-2">
            <select value={data.make_id} onChange={(e) => setData('make_id', e.target.value)} aria-label="Make" className="rounded border border-slate-300 px-2 py-1 text-xs">
                <option value="">Make…</option>
                {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
            </select>
            <input type="number" step="0.01" min="0" value={data.flat_rate} onChange={(e) => setData('flat_rate', e.target.value)} placeholder="Rate/hr" aria-label="Flat rate" className="w-24 rounded border border-slate-300 px-2 py-1 text-xs text-right tabular-nums" />
            <button type="submit" disabled={processing || !data.make_id || !data.flat_rate} className="rounded bg-slate-700 px-2 py-1 text-xs font-medium text-white hover:bg-slate-800 disabled:opacity-40">Add rate</button>
        </form>
    );
}

export default function LabourIndex({ labourCodes, makes }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '', description: '', category: '', rate_type: 'flat_rate', standard_hours: '', default_rate: '',
    });
    const [open, setOpen] = useState(false);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Labour Codes"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Labour Codes' }]}>
            <Head title="Labour Codes" />

            <div className="max-w-4xl space-y-4">
                <div className="flex justify-end">
                    <button onClick={() => setOpen((o) => !o)} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Labour Code
                    </button>
                </div>

                {open && (
                    <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.labour.store'), { onSuccess: () => { reset(); setOpen(false); } }); }} className="rounded-xl border border-slate-100 bg-white p-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div><label className="block text-xs font-medium text-slate-600 mb-1">Code</label><input value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} className={input} placeholder="ENG-OIL-01" />{errors.code && <p className="text-xs text-red-600">{errors.code}</p>}</div>
                        <div className="md:col-span-2"><label className="block text-xs font-medium text-slate-600 mb-1">Description</label><input value={data.description} onChange={(e) => setData('description', e.target.value)} className={input} /></div>
                        <div><label className="block text-xs font-medium text-slate-600 mb-1">Category</label><input value={data.category} onChange={(e) => setData('category', e.target.value)} className={input} /></div>
                        <div><label className="block text-xs font-medium text-slate-600 mb-1">Rate type</label>
                            <select value={data.rate_type} onChange={(e) => setData('rate_type', e.target.value)} className={input}>
                                <option value="flat_rate">Flat rate</option><option value="actual_time">Actual time</option><option value="fixed_price">Fixed price</option>
                            </select>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div><label className="block text-xs font-medium text-slate-600 mb-1">Std hrs</label><input type="number" step="0.1" value={data.standard_hours} onChange={(e) => setData('standard_hours', e.target.value)} className={input} /></div>
                            <div><label className="block text-xs font-medium text-slate-600 mb-1">Rate</label><input type="number" step="0.01" value={data.default_rate} onChange={(e) => setData('default_rate', e.target.value)} className={input} /></div>
                        </div>
                        <div className="md:col-span-3 flex justify-end"><button type="submit" disabled={processing || !data.code || !data.description} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Create</button></div>
                    </form>
                )}

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {labourCodes.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Timer className="w-8 h-8 mx-auto mb-2 text-slate-300" />No labour codes yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Code</th><th className="px-2 py-2">Description</th>
                                    <th className="px-2 py-2">Type</th><th className="px-2 py-2 text-right">Std hrs</th>
                                    <th className="px-4 py-2 text-right">Rate / overrides</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {labourCodes.map((c) => (
                                    <tr key={c.id}>
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800 align-top">{c.code}</td>
                                        <td className="px-2 py-2 text-slate-700 align-top">{c.description}<span className="block text-xs text-slate-400">{c.category}</span></td>
                                        <td className="px-2 py-2 text-slate-500 align-top capitalize">{c.rate_type.replace('_', ' ')}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500 align-top">{c.standard_hours}</td>
                                        <td className="px-4 py-2 text-right align-top">
                                            <span className="tabular-nums font-medium text-slate-700">{c.default_rate.toFixed(2)}</span>
                                            {c.overrides.length > 0 && (
                                                <ul className="mt-1 text-xs text-slate-500">
                                                    {c.overrides.map((o) => <li key={o.id}>{o.make}: <span className="tabular-nums">{o.flat_rate.toFixed(2)}</span></li>)}
                                                </ul>
                                            )}
                                            <RateForm code={c} makes={makes} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
