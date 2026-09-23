import { Head, Link } from '@inertiajs/react';
import { Award } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import StatusBadge from '@/Components/StatusBadge';

export default function LoyaltyIndex({ balances, recent, enabled, earnRate }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Loyalty Programme" breadcrumbs={[{ label: 'Customers', href: route('modules.show', 'customers') }, { label: 'Loyalty Programme' }]}>
            <Head title="Loyalty Programme" />
            <div className="max-w-4xl space-y-4">
                <div className="flex items-center gap-3 text-sm text-slate-500">
                    <StatusBadge status={enabled ? 'active' : 'inactive'} label={enabled ? 'Enabled' : 'Disabled'} />
                    <span>Earn rate: <strong className="text-slate-700">{earnRate}</strong> point(s) per $1 spent (configurable in the Configuration Centre).</span>
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Point balances</h2></div>
                        {balances.length === 0 ? (
                            <div className="py-10 text-center text-sm text-slate-400"><Award className="w-8 h-8 mx-auto mb-2 text-slate-300" />No points earned yet.</div>
                        ) : (
                            <table className="w-full text-sm"><tbody className="divide-y divide-slate-50">
                                {balances.map((b) => (
                                    <tr key={b.customer_id}><td className="px-4 py-2"><Link href={route('customers.show', b.customer_id)} className="text-slate-700 hover:text-orange-600">{b.customer}</Link></td><td className="px-4 py-2 text-right tabular-nums font-semibold text-slate-800">{b.points.toLocaleString()} pts</td></tr>
                                ))}
                            </tbody></table>
                        )}
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Recent activity</h2></div>
                        {recent.length === 0 ? (
                            <div className="py-10 text-center text-sm text-slate-400">No activity.</div>
                        ) : (
                            <ul className="divide-y divide-slate-50">
                                {recent.map((e) => (
                                    <li key={e.id} className="px-4 py-2 flex items-center justify-between text-sm"><span className="text-slate-600">{e.customer} · {e.reason}</span><span className={`tabular-nums font-medium ${e.points >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>{e.points >= 0 ? '+' : ''}{e.points}</span></li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
