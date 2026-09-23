import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2, AlertTriangle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import FormField from '@/Components/FormField';

const blankLine = () => ({ account: '', debit: '', credit: '', description: '' });

export default function JournalCreate({ accounts, branches }) {
    const [lines, setLines] = useState([blankLine(), blankLine()]);
    const { data, setData, post, processing, errors, transform } = useForm({
        journal_date: new Date().toISOString().slice(0, 10),
        description: '',
        reference: '',
        branch_id: '',
    });

    const setLine = (i, field, value) => setLines((prev) => prev.map((l, idx) => (idx === i ? { ...l, [field]: value } : l)));
    const addLine = () => setLines((prev) => [...prev, blankLine()]);
    const removeLine = (i) => setLines((prev) => prev.filter((_, idx) => idx !== i));

    const totalDebit = lines.reduce((n, l) => n + (Number(l.debit) || 0), 0);
    const totalCredit = lines.reduce((n, l) => n + (Number(l.credit) || 0), 0);
    const balanced = Math.abs(totalDebit - totalCredit) < 0.005 && totalDebit > 0;

    function submit(e) {
        e.preventDefault();
        transform((d) => ({
            ...d,
            lines: lines
                .filter((l) => l.account && ((Number(l.debit) || 0) > 0 || (Number(l.credit) || 0) > 0))
                .map((l) => ({ account: l.account, debit: Number(l.debit) || 0, credit: Number(l.credit) || 0, description: l.description })),
        }));
        post(route('finance.journals.store'));
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const cell = 'w-full rounded border border-slate-300 px-2 py-1 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Journal"
            breadcrumbs={[
                { label: 'Finance & Accounts', href: route('modules.show', 'finance') },
                { label: 'Journals', href: route('finance.journals.index') },
                { label: 'New Journal' },
            ]}
        >
            <Head title="New Journal" />

            <form onSubmit={submit} className="max-w-3xl space-y-6">
                {(errors.journal) && (
                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" /><span>{errors.journal}</span>
                    </div>
                )}

                <div className="rounded-xl border border-slate-100 bg-white p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <FormField label="Date" htmlFor="j-date" required error={errors.journal_date}>
                        <input id="j-date" type="date" value={data.journal_date} onChange={(e) => setData('journal_date', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Reference" htmlFor="j-ref" error={errors.reference} className="md:col-span-1">
                        <input id="j-ref" value={data.reference} onChange={(e) => setData('reference', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Branch" htmlFor="j-branch" error={errors.branch_id}>
                        <select id="j-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                            <option value="">Company-wide</option>
                            {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Description" htmlFor="j-desc" required error={errors.description} className="md:col-span-4">
                        <input id="j-desc" value={data.description} onChange={(e) => setData('description', e.target.value)} className={input} placeholder="e.g. Depreciation for September" />
                    </FormField>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white p-6 space-y-3">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="py-2">Account</th>
                                <th className="py-2">Narration</th>
                                <th className="py-2 text-right w-28">Debit</th>
                                <th className="py-2 text-right w-28">Credit</th>
                                <th className="w-9"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="py-2 pr-2">
                                        <select value={l.account} onChange={(e) => setLine(i, 'account', e.target.value)} aria-label="Account" className={cell}>
                                            <option value="">Select…</option>
                                            {accounts.map((a) => <option key={a.id} value={a.account_code}>{a.account_code} — {a.name}</option>)}
                                        </select>
                                    </td>
                                    <td className="py-2 pr-2"><input value={l.description} onChange={(e) => setLine(i, 'description', e.target.value)} aria-label="Narration" className={cell} /></td>
                                    <td className="py-2 pr-2"><input type="number" step="0.01" min="0" value={l.debit} onChange={(e) => setLine(i, 'debit', e.target.value)} aria-label="Debit" className={`${cell} text-right tabular-nums`} /></td>
                                    <td className="py-2 pr-2"><input type="number" step="0.01" min="0" value={l.credit} onChange={(e) => setLine(i, 'credit', e.target.value)} aria-label="Credit" className={`${cell} text-right tabular-nums`} /></td>
                                    <td className="py-2 text-right">
                                        {lines.length > 2 && <button type="button" onClick={() => removeLine(i)} aria-label="Remove" className="p-1 text-slate-300 hover:text-red-600"><Trash2 className="w-4 h-4" /></button>}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200 font-semibold">
                                <td colSpan={2} className="py-2 text-right text-slate-600">Totals</td>
                                <td className="py-2 text-right tabular-nums">{totalDebit.toFixed(2)}</td>
                                <td className="py-2 text-right tabular-nums">{totalCredit.toFixed(2)}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div className="flex items-center justify-between">
                        <button type="button" onClick={addLine} className="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 hover:text-orange-700"><Plus className="w-4 h-4" /> Add line</button>
                        <span className={`text-sm font-medium ${balanced ? 'text-emerald-600' : 'text-amber-600'}`}>
                            {balanced ? 'Balanced' : `Out by ${Math.abs(totalDebit - totalCredit).toFixed(2)}`}
                        </span>
                    </div>
                </div>

                <div className="flex justify-end gap-3">
                    <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" disabled={processing || !balanced || !data.description}
                        className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        {processing ? 'Posting…' : 'Post Journal'}
                    </button>
                </div>
            </form>
        </ModuleLayout>
    );
}
