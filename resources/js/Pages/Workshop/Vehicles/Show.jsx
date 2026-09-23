import { Head, Link } from '@inertiajs/react';
import { Plus, Wrench } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';

export default function VehicleShow({ vehicle, history }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title={vehicle.registration}
            breadcrumbs={[
                { label: 'Workshop', href: route('modules.show', 'workshop') },
                { label: 'Vehicle Registry', href: route('workshop.vehicles.index') },
                { label: vehicle.registration },
            ]}
        >
            <Head title={vehicle.registration} />

            <div className="max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900 font-mono">{vehicle.registration}</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {[vehicle.make, vehicle.model, vehicle.variant].filter(Boolean).join(' ') || 'Unspecified vehicle'}
                            {vehicle.year && ` · ${vehicle.year}`}{vehicle.colour && ` · ${vehicle.colour}`}
                            {vehicle.engine_code && ` · ${vehicle.engine_code}`}
                        </p>
                        <p className="mt-1 text-sm text-slate-500">
                            Owner: {vehicle.customer
                                ? <Link href={route('customers.show', vehicle.customer.id)} className="font-medium text-slate-600 hover:text-orange-600">{vehicle.customer.name}</Link>
                                : '—'}
                            {vehicle.vin && ` · VIN ${vehicle.vin}`}
                        </p>
                    </div>
                    <Link href={route('workshop.jobs.create', { vehicle_id: vehicle.id })} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Job
                    </Link>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Service history</h2></div>
                    {history.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Wrench className="w-8 h-8 mx-auto mb-2 text-slate-300" />No services recorded yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Date</th>
                                    <th className="px-2 py-2 text-right">Odometer</th>
                                    <th className="px-2 py-2">Work</th>
                                    <th className="px-4 py-2">Job</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {history.map((h) => (
                                    <tr key={h.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2 tabular-nums text-slate-500">{h.service_date}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500">{h.odometer ? h.odometer.toLocaleString() : '—'}</td>
                                        <td className="px-2 py-2 text-slate-700">{h.summary}</td>
                                        <td className="px-4 py-2">
                                            {h.job_card_id
                                                ? <Link href={route('workshop.jobs.show', h.job_card_id)} className="font-mono text-slate-600 hover:text-orange-600">{h.job_number}</Link>
                                                : '—'}
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
