import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { History, Search, ChevronDown, ChevronRight } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import Pagination from '@/Components/Pagination';
import EmptyState from '@/Components/EmptyState';

const ACTION_TONE = (action) => {
    if (action.includes('override') || action.includes('deleted') || action.includes('locked')) return 'bg-red-50 text-red-700';
    if (action.includes('permissions') || action.includes('setting')) return 'bg-amber-50 text-amber-700';
    if (action.includes('created')) return 'bg-emerald-50 text-emerald-700';
    return 'bg-slate-100 text-slate-600';
};

function Diff({ before, after }) {
    const keys = Array.from(new Set([...Object.keys(before ?? {}), ...Object.keys(after ?? {})]));
    if (keys.length === 0) return <span className="text-slate-400">—</span>;
    const show = (v) => (v === null || v === undefined ? <span className="text-slate-300">∅</span> : typeof v === 'object' ? JSON.stringify(v) : String(v));
    return (
        <table className="text-xs w-full">
            <tbody>
                {keys.map((k) => (
                    <tr key={k} className="align-top">
                        <td className="pr-3 py-0.5 font-mono text-slate-500 whitespace-nowrap">{k}</td>
                        <td className="pr-3 py-0.5 text-red-600 line-through break-all">{before && k in before ? show(before[k]) : ''}</td>
                        <td className="py-0.5 text-emerald-700 break-all">{after && k in after ? show(after[k]) : ''}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function Row({ log }) {
    const [open, setOpen] = useState(false);
    const hasDiff = (log.before && Object.keys(log.before).length) || (log.after && Object.keys(log.after).length);
    return (
        <>
            <tr className="border-b border-slate-50 hover:bg-slate-50/60">
                <td className="px-3 py-2 text-xs tabular-nums text-slate-500 whitespace-nowrap">{log.at}</td>
                <td className="px-3 py-2 text-sm text-slate-800">{log.user}</td>
                <td className="px-3 py-2"><span className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${ACTION_TONE(log.action)}`}>{log.action}</span></td>
                <td className="px-3 py-2 text-sm text-slate-700">
                    {log.entity && <span className="text-slate-400 text-xs mr-1">{log.entity}</span>}
                    {log.label ?? (log.entity_id ? `#${log.entity_id}` : '')}
                </td>
                <td className="px-3 py-2 text-xs text-slate-500">{log.note}</td>
                <td className="px-3 py-2 text-right">
                    {hasDiff ? (
                        <button onClick={() => setOpen((o) => !o)} className="inline-flex items-center gap-1 text-xs font-medium text-orange-600 hover:text-orange-700">
                            {open ? <ChevronDown className="w-3.5 h-3.5" /> : <ChevronRight className="w-3.5 h-3.5" />} Details
                        </button>
                    ) : null}
                </td>
            </tr>
            {open && (
                <tr className="bg-slate-50/80 border-b border-slate-100">
                    <td colSpan={6} className="px-6 py-3">
                        <Diff before={log.before} after={log.after} />
                        {log.ip && <div className="mt-2 text-[11px] text-slate-400">from {log.ip}</div>}
                    </td>
                </tr>
            )}
        </>
    );
}

export default function AuditLogIndex({ logs, filters, actions, users }) {
    const [form, setForm] = useState(filters);
    const apply = (next) => router.get(route('admin.audit-log.index'), next, { preserveState: true, replace: true });
    const set = (k, v) => { const next = { ...form, [k]: v }; setForm(next); if (k !== 'search') apply(next); };

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Audit Trail"
            breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Audit Trail' }]}
        >
            <Head title="Audit Trail" />

            <div className="space-y-4">
                <p className="text-sm text-slate-500 max-w-3xl">
                    Every override, approval, permission change, setting change and master-data edit — who did it, when, and the before/after values. Append-only.
                </p>

                <form onSubmit={(e) => { e.preventDefault(); apply(form); }} className="flex flex-wrap items-end gap-2 rounded-xl border border-slate-100 bg-white p-3">
                    <label className="flex-1 min-w-[12rem]">
                        <span className="block text-xs font-medium text-slate-500 mb-1">Search</span>
                        <div className="relative">
                            <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input value={form.search} onChange={(e) => set('search', e.target.value)} placeholder="Record, note or action…" className="w-full rounded-lg border border-slate-300 pl-8 pr-3 py-2 text-sm" />
                        </div>
                    </label>
                    <label>
                        <span className="block text-xs font-medium text-slate-500 mb-1">Action</span>
                        <select value={form.action} onChange={(e) => set('action', e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">All actions</option>
                            {actions.map((a) => <option key={a} value={a}>{a}</option>)}
                        </select>
                    </label>
                    <label>
                        <span className="block text-xs font-medium text-slate-500 mb-1">User</span>
                        <select value={form.user_id ?? ''} onChange={(e) => set('user_id', e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Anyone</option>
                            {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </label>
                    <label>
                        <span className="block text-xs font-medium text-slate-500 mb-1">From</span>
                        <input type="date" value={form.date_from ?? ''} onChange={(e) => set('date_from', e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </label>
                    <label>
                        <span className="block text-xs font-medium text-slate-500 mb-1">To</span>
                        <input type="date" value={form.date_to ?? ''} onChange={(e) => set('date_to', e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </label>
                    <button type="submit" className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Filter</button>
                </form>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {logs.data.length === 0 ? (
                        <EmptyState icon={History} title="Nothing audited yet" description="Overrides, approvals and master-data changes will appear here as they happen." />
                    ) : (
                        <table className="w-full">
                            <thead>
                                <tr className="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-3 py-2">When</th>
                                    <th className="px-3 py-2">Who</th>
                                    <th className="px-3 py-2">Action</th>
                                    <th className="px-3 py-2">Record</th>
                                    <th className="px-3 py-2">Note</th>
                                    <th className="px-3 py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {logs.data.map((log) => <Row key={log.id} log={log} />)}
                            </tbody>
                        </table>
                    )}
                </div>
                <Pagination links={logs.links} meta={logs} />
            </div>
        </ModuleLayout>
    );
}
