import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ArrowLeftRight, ArrowRight, PackageCheck, Truck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import ConfirmDialog from '@/Components/ConfirmDialog';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

// Transfer hub: from → to, who moved it and when, the lines, and the ledger
// trail. Dispatch/receive are the only actions — stock moves via the service.
export default function TransferShow({ transfer: t }) {
    const [confirm, setConfirm] = useState(null); // 'dispatch' | 'receive'
    const [processing, setProcessing] = useState(false);

    function act(action) {
        setProcessing(true);
        router.post(route(`inventory.transfers.${action}`, t.id), {}, {
            preserveScroll: true,
            onFinish: () => { setProcessing(false); setConfirm(null); },
        });
    }

    const steps = [
        { label: 'Created', at: t.created_at, byId: t.created_by_id, by: t.created_by, done: true },
        { label: 'Dispatched', at: t.dispatched_at, byId: t.dispatched_by_id, by: t.dispatched_by, done: !!t.dispatched_at },
        { label: 'Received', at: t.received_at, byId: t.received_by_id, by: t.received_by, done: !!t.received_at },
    ];

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={t.transfer_number}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Transfers', href: route('inventory.transfers.index') },
                { label: t.transfer_number },
            ]}
        >
            <Head title={t.transfer_number} />

            <div className="max-w-5xl space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center">
                                <ArrowLeftRight className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2.5 flex-wrap">
                                    <h1 className="font-mono text-2xl font-bold text-slate-900">{t.transfer_number}</h1>
                                    <StatusBadge status={t.status} />
                                </div>
                                <p className="mt-0.5 flex items-center gap-2 text-sm text-slate-600">
                                    <EntityLink type="branch" id={t.from_branch_id} label={t.from} className="font-medium" />
                                    <ArrowRight className="w-4 h-4 text-slate-300" />
                                    <EntityLink type="branch" id={t.to_branch_id} label={t.to} className="font-medium" />
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            {t.status === 'draft' && (
                                <button
                                    onClick={() => setConfirm('dispatch')}
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                                >
                                    <Truck className="w-4 h-4" />
                                    Dispatch
                                </button>
                            )}
                            {t.status === 'dispatched' && (
                                <button
                                    onClick={() => setConfirm('receive')}
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                                >
                                    <PackageCheck className="w-4 h-4" />
                                    Receive
                                </button>
                            )}
                        </div>
                    </div>

                    <ol className="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-100 pt-4 text-sm">
                        {steps.map((s) => (
                            <li key={s.label} className={s.done ? '' : 'opacity-50'}>
                                <div className="text-xs text-slate-400 uppercase tracking-wide">{s.label}</div>
                                <div className="tabular-nums text-slate-800">{s.at ?? 'pending'}</div>
                                {s.by && <div className="text-xs text-slate-500">by <EntityLink type="user" id={s.byId} label={s.by} /></div>}
                            </li>
                        ))}
                    </ol>

                    {t.notes && <p className="mt-4 rounded-lg bg-slate-50 px-4 py-2.5 text-sm text-slate-600 whitespace-pre-line">{t.notes}</p>}
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-6 py-3">Part</th>
                                <th className="px-3 py-3 text-right">Qty</th>
                                <th className="px-3 py-3 text-right">Unit cost (source AVCO)</th>
                                <th className="px-6 py-3 text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {t.lines.map((l) => (
                                <tr key={l.id}>
                                    <td className="px-6 py-2.5">
                                        <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" />
                                        <span className="block text-xs text-slate-400 truncate max-w-[22rem]">{l.description}</span>
                                    </td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">{l.qty}</td>
                                    <td className="px-3 py-2.5 text-right tabular-nums">
                                        {t.status === 'draft' ? <span className="text-slate-400">set at dispatch</span> : <MoneyDisplay amount={l.unit_cost} />}
                                    </td>
                                    <td className="px-6 py-2.5 text-right tabular-nums font-medium">
                                        {t.status === 'draft' ? <span className="text-slate-300">—</span> : <MoneyDisplay amount={l.line_value} />}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        {t.status !== 'draft' && (
                            <tfoot>
                                <tr className="border-t border-slate-100 text-sm font-semibold">
                                    <td className="px-6 py-2.5 text-slate-500" colSpan={3}>Total moved at source AVCO</td>
                                    <td className="px-6 py-2.5 text-right tabular-nums"><MoneyDisplay amount={t.total_value} /></td>
                                </tr>
                            </tfoot>
                        )}
                    </table>
                </div>

                <RelatedLinks
                    trace={[
                        { type: 'transfer', id: t.id, label: t.transfer_number, current: true },
                        t.journal_id
                            ? { type: 'journal', id: t.journal_id, label: t.journal_number ?? `Journal #${t.journal_id}` }
                            : { label: 'Intra-company — no GL journal', pending: true },
                    ]}
                    groups={[
                        { title: 'From branch', items: [{ type: 'branch', id: t.from_branch_id, label: t.from }] },
                        { title: 'To branch', items: [{ type: 'branch', id: t.to_branch_id, label: t.to }] },
                        { title: 'Parts', items: t.lines.map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
                        {
                            title: 'People',
                            items: [
                                t.created_by ? { type: 'user', id: t.created_by_id, label: `${t.created_by} (created)` } : null,
                                t.dispatched_by ? { type: 'user', id: t.dispatched_by_id, label: `${t.dispatched_by} (dispatched)` } : null,
                                t.received_by ? { type: 'user', id: t.received_by_id, label: `${t.received_by} (received)` } : null,
                            ].filter(Boolean),
                        },
                    ]}
                />
            </div>

            <ConfirmDialog
                open={confirm === 'dispatch'}
                title={`Dispatch ${t.transfer_number}?`}
                message={`Stock leaves ${t.from} now at its current average cost. ${t.to} sees it on hand only once received.`}
                confirmLabel="Dispatch"
                processing={processing}
                onConfirm={() => act('dispatch')}
                onCancel={() => setConfirm(null)}
            />
            <ConfirmDialog
                open={confirm === 'receive'}
                title={`Receive ${t.transfer_number}?`}
                message={`Stock lands at ${t.to} at the source cost. This cannot be undone.`}
                confirmLabel="Receive"
                processing={processing}
                onConfirm={() => act('receive')}
                onCancel={() => setConfirm(null)}
            />
        </ModuleLayout>
    );
}
