import { Head, router, useForm } from '@inertiajs/react';
import { Landmark, CheckCircle2, Circle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';

export default function BankIndex({ accounts, bankGlAccounts, selectedId, lines }) {
    const acct = useForm({ name: '', bank_name: '', account_number: '', gl_account_id: '', currency: 'USD' });
    const line = useForm({ txn_date: new Date().toISOString().slice(0, 10), description: '', amount: '' });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const selected = accounts.find((a) => a.id === selectedId);

    return (
        <ModuleLayout navConfig={navConfig} title="Cash & Bank" breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'Cash & Bank Management' }]}>
            <Head title="Cash & Bank" />
            <div className="space-y-6 max-w-4xl">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Bank accounts</h2></div>
                        {accounts.length === 0 ? (
                            <div className="py-8 text-center text-sm text-slate-400"><Landmark className="w-8 h-8 mx-auto mb-2 text-slate-300" />No bank accounts yet.</div>
                        ) : (
                            <table className="w-full text-sm"><tbody className="divide-y divide-slate-50">
                                {accounts.map((a) => (
                                    <tr key={a.id} className={`cursor-pointer hover:bg-slate-50 ${a.id === selectedId ? 'bg-orange-50/40' : ''}`} onClick={() => router.get(route('finance.bank.index'), { account_id: a.id }, { preserveState: true, replace: true })}>
                                        <td className="px-4 py-2"><div className="font-medium text-slate-800">{a.name}</div><div className="text-xs text-slate-400">{a.bank_name} · {a.gl_account ?? 'no GL'}</div></td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-700">{a.gl_balance !== null ? <MoneyDisplay amount={a.gl_balance} /> : '—'}</td>
                                        <td className="px-4 py-2 text-right text-xs">{a.unreconciled > 0 ? <span className="text-amber-600">{a.unreconciled} unrec.</span> : <span className="text-emerald-600">clear</span>}</td>
                                    </tr>
                                ))}
                            </tbody></table>
                        )}
                    </div>
                    <form onSubmit={(e) => { e.preventDefault(); acct.post(route('finance.bank.store'), { onSuccess: () => acct.reset() }); }} className="rounded-xl border border-slate-100 bg-white p-4 space-y-2 h-fit">
                        <h2 className="text-sm font-semibold text-slate-700">Add account</h2>
                        <input value={acct.data.name} onChange={(e) => acct.setData('name', e.target.value)} placeholder="Name" className={input} />
                        <input value={acct.data.bank_name} onChange={(e) => acct.setData('bank_name', e.target.value)} placeholder="Bank" className={input} />
                        <input value={acct.data.account_number} onChange={(e) => acct.setData('account_number', e.target.value)} placeholder="Account no." className={input} />
                        <select value={acct.data.gl_account_id} onChange={(e) => acct.setData('gl_account_id', e.target.value)} className={input}><option value="">GL account…</option>{bankGlAccounts.map((g) => <option key={g.id} value={g.id}>{g.account_code} {g.name}</option>)}</select>
                        <button type="submit" disabled={acct.processing || !acct.data.name} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Add</button>
                    </form>
                </div>

                {selected && (
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        <div className="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                            <h2 className="text-sm font-semibold text-slate-700">{selected.name} — statement</h2>
                            <form onSubmit={(e) => { e.preventDefault(); line.post(route('finance.bank.lines.store', selected.id), { preserveScroll: true, onSuccess: () => line.reset('description', 'amount') }); }} className="flex items-center gap-2">
                                <input type="date" value={line.data.txn_date} onChange={(e) => line.setData('txn_date', e.target.value)} className="rounded border border-slate-300 px-2 py-1 text-xs" />
                                <input value={line.data.description} onChange={(e) => line.setData('description', e.target.value)} placeholder="Description" className="rounded border border-slate-300 px-2 py-1 text-xs w-40" />
                                <input type="number" step="0.01" value={line.data.amount} onChange={(e) => line.setData('amount', e.target.value)} placeholder="± Amount" className="rounded border border-slate-300 px-2 py-1 text-xs w-24 text-right" />
                                <button type="submit" disabled={line.processing || !line.data.description || !line.data.amount} className="rounded bg-slate-700 px-2 py-1 text-xs text-white disabled:opacity-40">Add line</button>
                            </form>
                        </div>
                        <table className="w-full text-sm"><tbody className="divide-y divide-slate-50">
                            {lines.length === 0 ? <tr><td className="px-4 py-6 text-center text-slate-400">No statement lines. Import or add lines, then reconcile against the GL.</td></tr> : lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-4 py-2 tabular-nums text-slate-500">{l.txn_date}</td>
                                    <td className="px-2 py-2 text-slate-700">{l.description}</td>
                                    <td className={`px-2 py-2 text-right tabular-nums ${l.amount < 0 ? 'text-red-600' : 'text-emerald-600'}`}>{l.amount.toFixed(2)}</td>
                                    <td className="px-4 py-2 text-right"><button onClick={() => router.post(route('finance.bank.reconcile', l.id), {}, { preserveScroll: true })} className="inline-flex items-center gap-1 text-xs">{l.reconciled ? <CheckCircle2 className="w-4 h-4 text-emerald-500" /> : <Circle className="w-4 h-4 text-slate-300" />}</button></td>
                                </tr>
                            ))}
                        </tbody></table>
                    </div>
                )}
            </div>
        </ModuleLayout>
    );
}
