import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import EntityLink from '@/Components/EntityLink';

const LABELS = {
    open: 'Open', allocated: 'Allocated', in_progress: 'In Progress',
    awaiting_parts: 'Awaiting Parts', quality_check: 'Quality Check', completed: 'Completed',
};

export default function BoardIndex({ columns, board }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Technician Board"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Technician Board' }]}
        >
            <Head title="Technician Board" />

            <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
                {columns.map((col) => (
                    <div key={col} className="rounded-xl bg-slate-100/70 p-2">
                        <div className="flex items-center justify-between px-2 py-1.5">
                            <h2 className="text-xs font-semibold uppercase tracking-wider text-slate-500">{LABELS[col]}</h2>
                            <span className="rounded-full bg-white px-1.5 text-xs font-semibold text-slate-500">{board[col].length}</span>
                        </div>
                        <div className="space-y-2 mt-1">
                            {board[col].map((j) => (
                                // The card opens the job; the entity links inside stop propagation (UI-13).
                                <div key={j.id} role="link" tabIndex={0}
                                    onClick={() => router.visit(route('workshop.jobs.show', j.id))}
                                    onKeyDown={(e) => { if (e.key === 'Enter') router.visit(route('workshop.jobs.show', j.id)); }}
                                    className="block cursor-pointer rounded-lg bg-white border border-slate-100 p-3 shadow-sm hover:border-orange-300 hover:shadow transition">
                                    <div className="text-xs"><EntityLink type="job" id={j.id} label={j.job_number} mono className="font-semibold" /></div>
                                    <div className="mt-1 text-sm truncate">
                                        {j.customer ? <EntityLink type="customer" id={j.customer_id} label={j.customer} /> : <span className="text-slate-400">No customer</span>}
                                    </div>
                                    <div className="text-xs truncate">
                                        <EntityLink type="vehicle" id={j.vehicle_id} label={j.vehicle} mono muted />
                                        {j.technician && <> <span className="text-slate-400">·</span> <EntityLink type="technician" id={j.technician_id} label={j.technician} muted /></>}
                                    </div>
                                </div>
                            ))}
                            {board[col].length === 0 && <p className="px-2 py-3 text-center text-xs text-slate-300">—</p>}
                        </div>
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
