import { Head, useForm } from '@inertiajs/react';
import { Plus, HardHat } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function TechniciansIndex({ technicians, branches, filters = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', skill_level: 'qualified', specialisations: '', cost_rate: '', branch_id: '',
    });
    const filter = useListFilter('workshop.technicians.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="Technicians"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Technicians' }]}>
            <Head title="Technicians" />

            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Name, skill or branch…"
                searchClass="w-64"
            />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl mt-4">
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Name', sortable: 'name', render: (t) => <EntityLink type="technician" id={t.id} label={t.name} className="font-semibold" /> },
                            { key: 'skill_level', label: 'Skill', sortable: 'skill_level', render: (t) => <StatusBadge status={t.skill_level === 'master' ? 'special_order' : t.skill_level === 'apprentice' ? 'pending' : 'active'} label={t.skill_level} /> },
                            { key: 'specialisations', label: 'Specialisations', render: (t) => <span className="text-slate-500">{t.specialisations ?? '—'}</span> },
                            { key: 'cost_rate', label: 'Cost/hr', align: 'right', render: (t) => <span className="tabular-nums text-slate-500">{t.cost_rate.toFixed(2)}</span> },
                            { key: 'active_jobs', label: 'Active jobs', align: 'right', render: (t) => <span className="tabular-nums font-medium text-slate-700">{t.active_jobs}</span> },
                        ]}
                        rows={technicians.data}
                        pagination={technicians}
                        sorting={{ filters, onFilter: filter }}
                        rowClassName={(t) => (t.is_active ? '' : 'opacity-50')}
                        emptyTitle="No technicians yet"
                        emptyIcon={HardHat}
                    />
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
