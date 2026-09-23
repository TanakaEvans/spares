import { Head } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

export default function NotificationsCentre({ events, recent }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Notifications Centre" breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Notifications Centre' }]}>
            <Head title="Notifications Centre" />
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">
                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Event catalogue</h2></div>
                    <table className="w-full text-sm">
                        <tbody className="divide-y divide-slate-50">
                            {events.length === 0 ? <tr><td className="px-4 py-6 text-center text-slate-400">No events registered.</td></tr> : events.map((e) => (
                                <tr key={e.key}>
                                    <td className="px-4 py-2"><span className="text-slate-700">{e.label}</span><span className="block text-xs text-slate-400">{e.group}</span></td>
                                    <td className="px-4 py-2 text-right">{(e.channels || []).map((c) => <span key={c} className="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] uppercase text-slate-500">{c}</span>)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Recent notifications</h2></div>
                    {recent.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Bell className="w-8 h-8 mx-auto mb-2 text-slate-300" />No notifications yet.</div>
                    ) : (
                        <ul className="divide-y divide-slate-50">
                            {recent.map((n) => (
                                <li key={n.id} className="px-4 py-2.5 flex items-start gap-2">
                                    <span className={`mt-1.5 w-2 h-2 rounded-full shrink-0 ${n.read ? 'bg-slate-200' : 'bg-orange-500'}`} />
                                    <div><div className="text-sm text-slate-700">{n.message}</div><div className="text-xs text-slate-400">{n.type} · {n.at}</div></div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
