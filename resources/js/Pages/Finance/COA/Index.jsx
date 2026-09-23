import { Head, Link } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

export default function CoaIndex({ accounts, groups }) {
    const byType = Object.keys(groups).map((type) => ({
        type,
        label: groups[type],
        rows: accounts.filter((a) => a.type === type),
    }));

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Chart of Accounts"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Chart of Accounts' }]}
        >
            <Head title="Chart of Accounts" />

            <div className="max-w-4xl space-y-6">
                {byType.map((group) => group.rows.length > 0 && (
                    <div key={group.type} className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100 bg-slate-50">
                            <h2 className="text-sm font-semibold text-slate-700">{group.label}</h2>
                        </div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-slate-50">
                                {group.rows.map((a) => (
                                    <tr key={a.id} className={`hover:bg-slate-50 ${!a.is_active ? 'opacity-50' : ''}`}>
                                        <td className="px-4 py-2 font-mono text-slate-500 w-20">{a.account_code}</td>
                                        <td className="px-2 py-2">
                                            <Link href={route('finance.gl.index', { account_id: a.id })} className="font-medium text-slate-700 hover:text-orange-600">
                                                {a.name}
                                            </Link>
                                            {a.is_control_account && <span className="ml-2 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-indigo-600">control</span>}
                                            {!a.allow_direct_posting && <span className="ml-1 text-[10px] uppercase tracking-wide text-slate-400">no direct posting</span>}
                                        </td>
                                        <td className="px-4 py-2 text-right tabular-nums text-slate-700">
                                            <MoneyDisplay amount={a.balance} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
