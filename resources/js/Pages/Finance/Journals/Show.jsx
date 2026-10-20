import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Undo2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

export default function JournalShow({ journal }) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    function reverse() {
        setProcessing(true);
        router.post(route('finance.journals.reverse', journal.id), { reason: `Reversal of ${journal.journal_number}` },
            { onFinish: () => { setProcessing(false); setOpen(false); } });
    }

    const totalDebit = journal.lines.reduce((n, l) => n + l.debit, 0);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={journal.journal_number}
            breadcrumbs={[
                { label: 'Finance & Accounts', href: route('modules.show', 'finance') },
                { label: 'Journals', href: route('finance.journals.index') },
                { label: journal.journal_number },
            ]}
        >
            <Head title={journal.journal_number} />

            <div className="max-w-3xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{journal.journal_number}</h1>
                            <StatusBadge status={journal.status} />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            <span className="capitalize">{journal.journal_type}</span> · {journal.journal_date}
                            {journal.reference && <> · <span className="font-mono">{journal.reference}</span></>}
                            {journal.branch && <> · <EntityLink type="branch" id={journal.branch_id} label={journal.branch} muted /></>}
                            {journal.posted_by && <> · posted by <EntityLink type="user" id={journal.posted_by.id} label={journal.posted_by.name} muted /></>}
                        </p>
                        <p className="mt-1 text-sm text-slate-600">{journal.description}</p>
                        {journal.source && (
                            <p className="mt-1 text-sm text-slate-500">
                                Source document: <EntityLink type={journal.source.type} id={journal.source.id} label={journal.source.label} mono className="font-semibold" />
                            </p>
                        )}
                        {journal.reversing_journal && (
                            <p className="mt-1 text-sm text-red-600">
                                Reversed by <EntityLink type="journal" id={journal.reversing_journal.id} label={journal.reversing_journal.label} mono className="font-semibold" />
                            </p>
                        )}
                        {journal.reversal_of && (
                            <p className="mt-1 text-sm text-slate-500">
                                Reversal of <EntityLink type="journal" id={journal.reversal_of.id} label={journal.reversal_of.label} mono className="font-semibold" />
                            </p>
                        )}
                    </div>
                    {journal.is_reversible && (
                        <button onClick={() => setOpen(true)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <Undo2 className="w-4 h-4" />
                            Reverse
                        </button>
                    )}
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Account</th>
                                <th className="px-2 py-2.5">Narration</th>
                                <th className="px-2 py-2.5 text-right">Debit</th>
                                <th className="px-4 py-2.5 text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {journal.lines.map((l, i) => (
                                <tr key={i}>
                                    <td className="px-4 py-2.5">
                                        <EntityLink type="gl_account" number={l.account_code} title={`GL enquiry — ${l.account_code}`}>
                                            <span className="font-mono text-slate-400">{l.account_code}</span> <span>{l.account_name}</span>
                                        </EntityLink>
                                    </td>
                                    <td className="px-2 py-2.5 text-slate-500">{l.description}</td>
                                    <td className="px-2 py-2.5 text-right tabular-nums text-slate-700">{l.debit > 0 ? l.debit.toFixed(2) : ''}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-slate-700">{l.credit > 0 ? l.credit.toFixed(2) : ''}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr className="border-t-2 border-slate-200 font-bold">
                                <td colSpan={2} className="px-4 py-2.5 text-right text-slate-600">Total</td>
                                <td className="px-2 py-2.5 text-right tabular-nums">{totalDebit.toFixed(2)}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums">{totalDebit.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <RelatedLinks
                    trace={[
                        ...(journal.source ? [{ type: journal.source.type, id: journal.source.id, label: journal.source.label }] : []),
                        { type: 'journal', id: journal.id, label: journal.journal_number, current: true },
                        ...(journal.reversing_journal ? [{ type: 'journal', id: journal.reversing_journal.id, label: journal.reversing_journal.label }] : []),
                    ]}
                    groups={[
                        { title: 'Source document', items: journal.source ? [{ ...journal.source, mono: true }] : [] },
                        { title: 'Accounts', items: journal.lines.map((l) => ({ type: 'gl_account', number: l.account_code, label: `${l.account_code} ${l.account_name}` })) },
                        { title: 'Posted by', items: journal.posted_by ? [{ type: 'user', id: journal.posted_by.id, label: journal.posted_by.name }] : [] },
                        { title: 'Branch', items: journal.branch_id ? [{ type: 'branch', id: journal.branch_id, label: journal.branch }] : [] },
                    ]}
                />
            </div>

            <ConfirmDialog
                open={open}
                title={`Reverse ${journal.journal_number}?`}
                message="A new equal-and-opposite journal is posted; the original is never edited."
                confirmLabel="Post reversal"
                processing={processing}
                onConfirm={reverse}
                onCancel={() => setOpen(false)}
            />
        </ModuleLayout>
    );
}
