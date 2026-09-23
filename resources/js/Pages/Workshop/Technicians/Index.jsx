import { Head, useForm } from '@inertiajs/react';
import { Plus, HardHat } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import StatusBadge from '@/Components/StatusBadge';

export default function TechniciansIndex({ technicians, branches }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', skill_level: 'qualified', specialisations: '', cost_rate: '', branch_id: '',
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Technicians"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Technicians' }]}>
            <Head title="Technicians" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl">
                <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {technicians.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><HardHat className="w-8 h-8 mx-auto mb-2 text-slate-300" />No technicians yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Name</th><th className="px-2 py-2">Skill</th>
                                    <th className="px-2 py-2">Specialisations</th><th className="px-2 py-2 text-right">Cost/hr</th>
                                    <th className="px-4 py-2 text-right">Active jobs</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {technicians.map((t) => (
                                    <tr key={t.id} className={t.is_active ? '' : 'opacity-50'}>
                                        <td className="px-4 py-2 font-semibold text-slate-800">{t.name}</td>
                                        <td className="px-2 py-2"><StatusBadge status={t.skill_level === 'master' ? 'special_order' : t.skill_level === 'apprentice' ? 'pending' : 'active'} label={t.skill_level} /></td>
                                        <td className="px-2 py-2 text-slate-500">{t.specialisations ?? '—'}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500">{t.cost_rate.toFixed(2)}</td>
                                        <td className="px-4 py-2 text-right tabular-nums font-medium text-slate-700">{t.active_jobs}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.technicians.store'), { onSuccess: () => reset() }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">Add technician</h2>
                    <div><label className="block text-xs font-medium text-slate-600 mb-1">Name</label><input value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />{errors.name && <p className="text-xs text-red-600">{errors.name}</p>}</div>
                    <div><label className="block text-xs font-medium text-slate-600 mb-1">Skill level</label>
                        <select value={data.skill_level} onChange={(e) => setData('skill_level', e.target.value)} className={input}>
                            <option value="apprentice">Apprentice</option><option value="qualified">Qualified</option><option value="master">Master</option>
                        </select>
                    </div>
                    <div><label className="block text-xs font-medium text-slate-600 mb-1">Specialisations</label><input value={data.specialisations} onChange={(e) => setData('specialisations', e.target.value)} className={input} placeholder="Engines, Brakes" /></div>
                    <div><label className="block text-xs font-medium text-slate-600 mb-1">Cost / hour</label><input type="number" step="0.01" value={data.cost_rate} onChange={(e) => setData('cost_rate', e.target.value)} className={input} /></div>
                    <div><label className="block text-xs font-medium text-slate-600 mb-1">Branch</label>
                        <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                            <option value="">Any</option>
                            {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </div>
                    <button type="submit" disabled={processing || !data.name} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        <Plus className="w-4 h-4" /> Add
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
