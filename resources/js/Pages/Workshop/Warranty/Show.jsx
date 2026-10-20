import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

// Same lifecycle as the index: draft → submitted → acknowledged → approved → credited (or rejected).
const NEXT = { draft: 'submitted', submitted: 'acknowledged', acknowledged: 'approved', approved: 'credited' };
const BADGE = (s) => (s === 'credited' ? 'complete' : s === 'rejected' ? 'rejected' : s === 'approved' ? 'approved' : 'submitted');

export default function WarrantyShow({ claim }) {
    const [processing, setProcessing] = useState(false);
    const [supplierRef, setSupplierRef] = useState(claim.supplier_ref ?? '');
    const [creditAmount, setCreditAmount] = useState(claim.claim_amount);
    const next = NEXT[claim.status];
    const closed = claim.status === 'credited' || claim.status === 'rejected';

    function transition(status) {
        setProcessing(true);
        router.post(route('workshop.warranty.transition', claim.id), {
            status,
            credit_amount: status === 'credited' ? creditAmount : null,
            supplier_ref: supplierRef || null,
        }, { preserveScroll: true, onFinish: () => setProcessing(false) });
    }

    return (
        <ModuleLayout navConfig={navConfig} title={claim.claim_number}
            breadcrumbs={[
                { label: 'Workshop', href: route('modules.show', 'workshop') },
                { label: 'Warranty Claims', href: route('workshop.warranty.index') },
                { label: claim.claim_number },
            ]}>
            <Head title={claim.claim_number} />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-50 text-orange-600"><ShieldCheck className="h-5 w-5" /></div>
                            <div className="flex-1">
                                <div className="flex flex-wrap items-center gap-3">
                                    <h1 className="text-xl font-bold text-slate-900 font-mono">{claim.claim_number}</h1>
                                    <StatusBadge status={BADGE(claim.status)} label={claim.status} />
                                </div>
                                <p className="mt-1 text-sm text-slate-500">
                                    Raised {claim.created_at}
                                    {claim.supplier && <> · against <EntityLink type="supplier" id={claim.supplier_id} label={claim.supplier} /></>}
                                </p>
                            </div>
                        </div>

                        <dl className="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Supplier</dt>
                                <dd className="mt-1"><EntityLink type="supplier" id={claim.supplier_id} label={claim.supplier ?? '—'} /></dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Part</dt>
                                <dd className="mt-1">
                                    <EntityLink type="part" id={claim.part_id} label={claim.part_number ?? '—'} mono className="font-semibold" />
                                    {claim.part_description && <span className="ml-2 text-slate-500">{claim.part_description}</span>}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Job card</dt>
                                <dd className="mt-1 flex items-center gap-2">
                                    <EntityLink type="job" id={claim.job_card_id} label={claim.job_number ?? '—'} mono className="font-semibold" />
                                    {claim.job_status && <StatusBadge status={claim.job_status} />}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Customer · Vehicle</dt>
                                <dd className="mt-1">
                                    <EntityLink type="customer" id={claim.customer_id} label={claim.customer ?? '—'} />
                                    {claim.vehicle && <> · <EntityLink type="vehicle" id={claim.vehicle_id} label={claim.vehicle} mono /></>}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Claim amount</dt>
                                <dd className="mt-1 font-semibold tabular-nums text-slate-800"><MoneyDisplay amount={claim.claim_amount} /></dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Credited</dt>
                                <dd className={`mt-1 font-semibold tabular-nums ${claim.credit_amount > 0 ? 'text-emerald-600' : 'text-slate-400'}`}>
                                    {claim.credit_amount > 0 ? <MoneyDisplay amount={claim.credit_amount} /> : '—'}
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-xs uppercase tracking-wide text-slate-400">Fault</dt>
                                <dd className="mt-1 whitespace-pre-line text-slate-700">{claim.fault || '—'}</dd>
                            </div>
                            {claim.supplier_ref && (
                                <div>
                                    <dt className="text-xs uppercase tracking-wide text-slate-400">Supplier reference</dt>
                                    <dd className="mt-1 font-mono text-slate-700">{claim.supplier_ref}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <RelatedLinks
                        trace={[
                            { type: 'job', id: claim.job_card_id, label: claim.job_number ?? 'No job', pending: !claim.job_card_id },
                            { type: 'warranty_claim', current: true, label: claim.claim_number },
                            { type: 'supplier_credit', label: claim.status === 'credited' ? 'Supplier credit' : 'Awaiting credit', pending: true },
                        ]}
                        groups={[
                            { title: 'Supplier', items: claim.supplier_id ? [{ type: 'supplier', id: claim.supplier_id, label: claim.supplier }] : [] },
                            { title: 'Part', items: claim.part_id ? [{ type: 'part', id: claim.part_id, label: claim.part_number, mono: true }] : [] },
                            { title: 'Customer', items: claim.customer_id ? [{ type: 'customer', id: claim.customer_id, label: claim.customer }] : [] },
                            { title: 'Vehicle', items: claim.vehicle_id ? [{ type: 'vehicle', id: claim.vehicle_id, label: claim.vehicle, mono: true }] : [] },
                            { title: 'Invoice', items: claim.invoice_id ? [{ type: 'invoice', id: claim.invoice_id, label: claim.invoice_number, mono: true }] : [] },
                        ]}
                    />
                </div>

                <div className="space-y-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-3">
                        <h2 className="text-sm font-semibold text-slate-700">Progress claim</h2>
                        {closed ? (
                            <p className="text-sm text-slate-500">This claim is {claim.status}. No further transitions are available.</p>
                        ) : (
                            <>
                                <div>
                                    <label htmlFor="supplier_ref" className="block text-xs font-medium text-slate-600 mb-1">Supplier reference</label>
                                    <input id="supplier_ref" value={supplierRef} onChange={(e) => setSupplierRef(e.target.value)} placeholder="Supplier claim / RMA no." className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                                </div>
                                {next === 'credited' && (
                                    <div>
                                        <label htmlFor="credit_amount" className="block text-xs font-medium text-slate-600 mb-1">Credit amount</label>
                                        <input id="credit_amount" type="number" step="0.01" min="0" value={creditAmount} onChange={(e) => setCreditAmount(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-right" />
                                    </div>
                                )}
                                {next && (
                                    <button onClick={() => transition(next)} disabled={processing} className="w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 capitalize">
                                        {processing ? 'Saving…' : `Mark ${next}`}
                                    </button>
                                )}
                                <button onClick={() => transition('rejected')} disabled={processing} className="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-40">
                                    Reject claim
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
