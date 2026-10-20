import { Head, Link } from '@inertiajs/react';
import { ClipboardList, Clock, HardHat, Wrench } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';
import MoneyDisplay from '@/Components/MoneyDisplay';

const SKILL_STATUS = { master: 'special_order', apprentice: 'pending', qualified: 'active' };

export default function TechnicianShow({ technician, jobs, productivity }) {
    const activeJobs = jobs.filter((j) => ['allocated', 'in_progress', 'quality_check'].includes(j.status));

    return (
        <ModuleLayout navConfig={navConfig} title={technician.name}
            breadcrumbs={[
                { label: 'Workshop', href: route('modules.show', 'workshop') },
                { label: 'Technicians', href: route('workshop.technicians.index') },
                { label: technician.name },
            ]}>
            <Head title={technician.name} />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    {/* Profile */}
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-50 text-orange-600"><HardHat className="h-5 w-5" /></div>
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-xl font-bold text-slate-900">{technician.name}</h1>
                                    <StatusBadge status={SKILL_STATUS[technician.skill_level] ?? 'active'} label={technician.skill_level} />
                                    {!technician.is_active && <StatusBadge status="inactive" label="inactive" />}
                                </div>
                                <p className="mt-1 text-sm text-slate-500">
                                    {technician.specialisations || 'No specialisations recorded'}
                                    {technician.employee_id && <span className="ml-2 font-mono text-xs text-slate-400">#{technician.employee_id}</span>}
                                </p>
                            </div>
                        </div>
                        <dl className="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                            <div><dt className="text-xs uppercase tracking-wide text-slate-400">Cost / hour</dt><dd className="mt-1 font-semibold tabular-nums text-slate-800"><MoneyDisplay amount={technician.cost_rate} /></dd></div>
                            <div><dt className="text-xs uppercase tracking-wide text-slate-400">Branch</dt><dd className="mt-1 text-slate-800"><EntityLink type="branch" id={technician.branch_id} label={technician.branch ?? 'Any branch'} /></dd></div>
                            <div><dt className="text-xs uppercase tracking-wide text-slate-400">Active jobs</dt><dd className="mt-1 font-semibold tabular-nums text-slate-800">{productivity.jobs_active}</dd></div>
                            <div><dt className="text-xs uppercase tracking-wide text-slate-400">Status</dt><dd className="mt-1 text-slate-800">{technician.is_active ? 'Active' : 'Inactive'}</dd></div>
                        </dl>
                    </div>

                    {/* Job cards */}
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100 flex items-center gap-2">
                            <ClipboardList className="w-4 h-4 text-slate-400" />
                            <h2 className="text-sm font-semibold text-slate-700">Job cards</h2>
                            <span className="ml-auto text-xs text-slate-400">most recent {jobs.length}</span>
                        </div>
                        {jobs.length === 0 ? (
                            <div className="py-10 text-center text-sm text-slate-400"><Wrench className="w-8 h-8 mx-auto mb-2 text-slate-300" />No jobs assigned yet.</div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                        <th className="px-4 py-2">Job</th><th className="px-2 py-2">Customer</th><th className="px-2 py-2">Vehicle</th>
                                        <th className="px-2 py-2">Invoice</th><th className="px-2 py-2">Opened</th><th className="px-4 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {jobs.map((j) => (
                                        <tr key={j.id} className="hover:bg-slate-50/70">
                                            <td className="px-4 py-2"><EntityLink type="job" id={j.id} label={j.job_number} mono className="font-semibold" /></td>
                                            <td className="px-2 py-2"><EntityLink type="customer" id={j.customer_id} label={j.customer} /></td>
                                            <td className="px-2 py-2"><EntityLink type="vehicle" id={j.vehicle_id} label={j.vehicle} mono /></td>
                                            <td className="px-2 py-2"><EntityLink type="invoice" id={j.invoice_id} label={j.invoice_number} mono muted /></td>
                                            <td className="px-2 py-2 tabular-nums text-slate-500">{j.opened_at}</td>
                                            <td className="px-4 py-2"><StatusBadge status={j.status} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                <div className="space-y-4">
                    {/* Productivity */}
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Clock className="w-4 h-4 text-slate-400" /> Productivity</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex justify-between"><dt className="text-slate-500">Jobs completed</dt><dd className="font-semibold tabular-nums text-slate-800">{productivity.jobs_completed}<span className="text-slate-400 font-normal"> / {productivity.jobs_total}</span></dd></div>
                            <div className="flex justify-between"><dt className="text-slate-500">Hours booked (labour)</dt><dd className="font-semibold tabular-nums text-slate-800">{productivity.hours_booked.toFixed(2)}h</dd></div>
                            <div className="flex justify-between"><dt className="text-slate-500">Hours logged (clock)</dt><dd className="font-semibold tabular-nums text-slate-800">{productivity.hours_logged.toFixed(2)}h</dd></div>
                        </dl>
                        {productivity.time_log_entries > 0 ? (
                            <p className="mt-3 text-xs text-slate-400">{productivity.time_log_entries} clock entries · last clock-on {productivity.last_clock_on}</p>
                        ) : (
                            <p className="mt-3 text-xs text-slate-400">No clock-on/off entries recorded yet. Hours above come from booked labour lines.</p>
                        )}
                    </div>

                    <RelatedLinks
                        groups={[
                            { title: 'Active jobs', items: activeJobs.map((j) => ({ type: 'job', id: j.id, label: j.job_number, mono: true })) },
                            { title: 'Branch', items: technician.branch_id ? [{ type: 'branch', id: technician.branch_id, label: technician.branch }] : [] },
                        ]}
                    />

                    <div className="rounded-xl border border-slate-100 bg-white p-5 text-sm">
                        <h2 className="text-sm font-semibold text-slate-700 mb-2">Manage</h2>
                        <p className="text-slate-500">
                            Edit skill level, cost rate or the active flag from the <Link href={route('workshop.technicians.index')} className="text-orange-600 hover:underline">technicians list</Link>;
                            see live workload on the <Link href={route('workshop.board')} className="text-orange-600 hover:underline">technician board</Link>.
                        </p>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
