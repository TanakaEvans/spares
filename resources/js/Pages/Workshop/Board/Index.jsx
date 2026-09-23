import { Head, Link } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';

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
                                <Link key={j.id} href={route('workshop.jobs.show', j.id)}
                                    className="block rounded-lg bg-white border border-slate-100 p-3 shadow-sm hover:border-orange-300 hover:shadow transition">
                                    <div className="font-mono text-xs font-semibold text-slate-800">{j.job_number}</div>
                                    <div className="mt-1 text-sm text-slate-700 truncate">{j.customer ?? 'No customer'}</div>
                                    <div className="text-xs text-slate-400 truncate">{j.vehicle ?? '—'}{j.technician && ` · ${j.technician}`}</div>
                                </Link>
                            ))}
                            {board[col].length === 0 && <p className="px-2 py-3 text-center text-xs text-slate-300">—</p>}
                        </div>
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
