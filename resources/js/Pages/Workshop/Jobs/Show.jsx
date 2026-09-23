import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Search, Trash2, Wrench, PackagePlus, AlertTriangle, Receipt } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

const money = (n) => Number(n || 0).toFixed(2);
const STATUS_LABEL = (s) => s.replace(/_/g, ' ');

export default function JobShow({ job, labourCodes, technicians }) {
    const { errors } = usePage().props;
    const editable = !['invoiced', 'closed', 'cancelled'].includes(job.status);

    return (
        <ModuleLayout navConfig={navConfig} title={job.job_number}
            breadcrumbs={[
                { label: 'Workshop', href: route('modules.show', 'workshop') },
                { label: 'Job Cards', href: route('workshop.jobs.index') },
                { label: job.job_number },
            ]}>
            <Head title={job.job_number} />

            {(errors.status || errors.part || errors.invoice) && (
                <div className="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" /><span>{errors.status || errors.part || errors.invoice}</span>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    {/* Header */}
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-xl font-bold text-slate-900">{job.job_number}</h1>
                                    <StatusBadge status={job.status} />
                                </div>
                                <p className="mt-1 text-sm text-slate-500">
                                    {job.vehicle
                                        ? <Link href={route('workshop.vehicles.show', job.vehicle.id)} className="font-medium text-slate-600 hover:text-orange-600">{job.vehicle.label}</Link>
                                        : 'No vehicle'}
                                    {' · '}
                                    {job.customer
                                        ? <Link href={route('customers.show', job.customer.id)} className="font-medium text-slate-600 hover:text-orange-600">{job.customer.name}</Link>
                                        : 'No customer'}
                                </p>
                                {job.reported_fault && <p className="mt-2 text-sm text-slate-600"><span className="text-slate-400">Reported:</span> {job.reported_fault}</p>}
                            </div>
                            {job.invoice && (
                                <Link href={route('sales.invoices.show', job.invoice.id)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <Receipt className="w-4 h-4" /> {job.invoice.document_number}
                                </Link>
                            )}
                        </div>

                        {/* Status actions + technician */}
                        {editable && (
                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                {job.next_statuses.map((s) => (
                                    <button key={s} onClick={() => router.post(route('workshop.jobs.transition', job.id), { status: s }, { preserveScroll: true })}
                                        className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 capitalize">
                                        {STATUS_LABEL(s)}
                                    </button>
                                ))}
                                <select aria-label="Technician" value={job.technician?.id ?? ''}
                                    onChange={(e) => router.patch(route('workshop.jobs.update', job.id), { technician_id: e.target.value }, { preserveScroll: true })}
                                    className="ml-auto rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
                                    <option value="">Unassigned</option>
                                    {technicians.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                            </div>
                        )}
                    </div>

                    <LabourSection job={job} labourCodes={labourCodes} editable={editable} />
                    <PartsSection job={job} editable={editable} />
                </div>

                {/* Costing + invoice */}
                <div className="space-y-4">
                    <CostingPanel costing={job.costing} />
                    {job.is_invoiceable && <InvoicePanel job={job} />}
                </div>
            </div>
        </ModuleLayout>
    );
}

function LabourSection({ job, labourCodes, editable }) {
    const { data, setData, post, processing, reset } = useForm({ labour_code_id: '', description: '', hours: '', rate: '', is_warranty: false });

    function onCode(id) {
        const c = labourCodes.find((x) => String(x.id) === String(id));
        setData((d) => ({ ...d, labour_code_id: id, description: c?.description ?? d.description, hours: c?.standard_hours ?? d.hours, rate: c?.default_rate ?? d.rate }));
    }
    function submit(e) { e.preventDefault(); post(route('workshop.jobs.labour.store', job.id), { preserveScroll: true, onSuccess: () => reset() }); }

    return (
        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
            <div className="px-4 py-2.5 border-b border-slate-100 flex items-center gap-2"><Wrench className="w-4 h-4 text-slate-400" /><h2 className="text-sm font-semibold text-slate-700">Labour</h2></div>
            {job.labours.length > 0 && (
                <table className="w-full text-sm">
                    <tbody className="divide-y divide-slate-50">
                        {job.labours.map((l) => (
                            <tr key={l.id}>
                                <td className="px-4 py-2">
                                    <span className="text-slate-700">{l.description}</span>
                                    {l.code && <span className="ml-2 font-mono text-xs text-slate-400">{l.code}</span>}
                                    {l.is_warranty && <span className="ml-2 rounded bg-purple-50 px-1.5 text-[10px] font-semibold uppercase text-purple-600">warranty</span>}
                                    <span className="block text-xs text-slate-400">{l.hours}h @ {money(l.rate)}{l.technician && ` · ${l.technician}`}</span>
                                </td>
                                <td className="px-2 py-2 text-right tabular-nums font-medium text-slate-700">{money(l.line_total)}</td>
                                <td className="px-3 py-2 text-right w-9">
                                    {editable && <button onClick={() => router.delete(route('workshop.jobs.labour.destroy', [job.id, l.id]), { preserveScroll: true })} aria-label="Remove" className="p-1 text-slate-300 hover:text-red-600"><Trash2 className="w-4 h-4" /></button>}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
            {editable && (
                <form onSubmit={submit} className="flex flex-wrap items-end gap-2 p-3 bg-slate-50/60 border-t border-slate-100">
                    <select value={data.labour_code_id} onChange={(e) => onCode(e.target.value)} aria-label="Labour code" className="rounded border border-slate-300 px-2 py-1.5 text-sm">
                        <option value="">Custom…</option>
                        {labourCodes.map((c) => <option key={c.id} value={c.id}>{c.code}</option>)}
                    </select>
                    <input value={data.description} onChange={(e) => setData('description', e.target.value)} placeholder="Description" aria-label="Description" className="flex-1 min-w-[8rem] rounded border border-slate-300 px-2 py-1.5 text-sm" />
                    <input type="number" step="0.1" min="0" value={data.hours} onChange={(e) => setData('hours', e.target.value)} placeholder="Hrs" aria-label="Hours" className="w-16 rounded border border-slate-300 px-2 py-1.5 text-sm text-right" />
                    <input type="number" step="0.01" min="0" value={data.rate} onChange={(e) => setData('rate', e.target.value)} placeholder="Rate" aria-label="Rate" className="w-20 rounded border border-slate-300 px-2 py-1.5 text-sm text-right" />
                    <label className="flex items-center gap-1 text-xs text-slate-500"><input type="checkbox" checked={data.is_warranty} onChange={(e) => setData('is_warranty', e.target.checked)} className="rounded border-slate-300 text-orange-600" /> Warranty</label>
                    <button type="submit" disabled={processing || !data.hours} className="rounded-lg bg-orange-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Add</button>
                </form>
            )}
        </div>
    );
}

function PartsSection({ job, editable }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [picked, setPicked] = useState(null);
    const { data, setData, post, processing, reset } = useForm({ part_id: '', qty: 1, unit_price: '', is_warranty: false });
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try {
                const res = await fetch(route('workshop.jobs.part-lookup') + '?' + new URLSearchParams({ q: query, branch_id: job.branch_id ?? '' }), { headers: { Accept: 'application/json' } });
                setResults((await res.json()).parts ?? []);
            } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    function pick(p) {
        const suggested = p.price > 0 ? p.price : p.avco * 2;
        setPicked(p); setData((d) => ({ ...d, part_id: p.id, unit_price: Number(suggested).toFixed(2) }));
        setQuery(''); setResults([]);
    }
    function submit(e) {
        e.preventDefault();
        post(route('workshop.jobs.parts.store', job.id), { preserveScroll: true, onSuccess: () => { reset(); setPicked(null); } });
    }

    return (
        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
            <div className="px-4 py-2.5 border-b border-slate-100 flex items-center gap-2"><PackagePlus className="w-4 h-4 text-slate-400" /><h2 className="text-sm font-semibold text-slate-700">Parts</h2></div>
            {job.parts.length > 0 && (
                <table className="w-full text-sm">
                    <tbody className="divide-y divide-slate-50">
                        {job.parts.map((p) => (
                            <tr key={p.id} className={p.status === 'returned' ? 'opacity-50' : ''}>
                                <td className="px-4 py-2">
                                    <Link href={route('inventory.parts.show', p.part_id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{p.part_number}</Link>
                                    {p.is_warranty && <span className="ml-2 rounded bg-purple-50 px-1.5 text-[10px] font-semibold uppercase text-purple-600">warranty</span>}
                                    <span className="block text-xs text-slate-400">{p.qty} @ {money(p.unit_price)}</span>
                                </td>
                                <td className="px-2 py-2"><StatusBadge status={p.status === 'requested' ? 'pending' : p.status === 'issued' ? 'allocated' : 'cancelled'} label={p.status} /></td>
                                <td className="px-3 py-2 text-right">
                                    {editable && p.status === 'requested' && (
                                        <button onClick={() => router.post(route('workshop.jobs.parts.issue', [job.id, p.id]), {}, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Issue</button>
                                    )}
                                    {editable && p.status === 'issued' && (
                                        <button onClick={() => router.post(route('workshop.jobs.parts.return', [job.id, p.id]), {}, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Return</button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
            {editable && (
                <div className="p-3 bg-slate-50/60 border-t border-slate-100 space-y-2">
                    {!picked ? (
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search part to add…" aria-label="Search part" className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm" />
                            {results.length > 0 && (
                                <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                    {results.map((p) => (
                                        <li key={p.id}><button type="button" onClick={() => pick(p)} className="w-full flex gap-3 px-3 py-2 text-sm text-left hover:bg-orange-50"><span className="font-mono font-semibold text-slate-800">{p.part_number}</span><span className="flex-1 truncate text-slate-500">{p.description}</span><span className="text-xs text-slate-400 tabular-nums">{money(p.on_hand)} on hand</span></button></li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    ) : (
                        <form onSubmit={submit} className="flex flex-wrap items-end gap-2">
                            <span className="font-mono text-sm font-semibold text-slate-800">{picked.part_number}</span>
                            <input type="number" step="0.01" min="0.01" value={data.qty} onChange={(e) => setData('qty', e.target.value)} aria-label="Qty" className="w-16 rounded border border-slate-300 px-2 py-1.5 text-sm text-right" />
                            <input type="number" step="0.01" min="0" value={data.unit_price} onChange={(e) => setData('unit_price', e.target.value)} placeholder="Price" aria-label="Unit price" className="w-24 rounded border border-slate-300 px-2 py-1.5 text-sm text-right" />
                            <label className="flex items-center gap-1 text-xs text-slate-500"><input type="checkbox" checked={data.is_warranty} onChange={(e) => setData('is_warranty', e.target.checked)} className="rounded border-slate-300 text-orange-600" /> Warranty</label>
                            <button type="submit" disabled={processing || !data.unit_price} className="rounded-lg bg-orange-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Add</button>
                            <button type="button" onClick={() => setPicked(null)} className="text-xs text-slate-400 hover:text-slate-600">cancel</button>
                        </form>
                    )}
                </div>
            )}
        </div>
    );
}

function CostingPanel({ costing }) {
    const Row = ({ label, cost, billed }) => (
        <tr>
            <td className="py-1.5 text-slate-500">{label}</td>
            <td className="py-1.5 text-right tabular-nums text-slate-500">{money(cost)}</td>
            <td className="py-1.5 text-right tabular-nums text-slate-700">{money(billed)}</td>
        </tr>
    );
    return (
        <div className="rounded-xl border border-slate-100 bg-white p-5">
            <h2 className="text-sm font-semibold text-slate-700 mb-3">Job costing</h2>
            <table className="w-full text-sm">
                <thead>
                    <tr className="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                        <th className="py-1 text-left"></th><th className="py-1 text-right">Cost</th><th className="py-1 text-right">Billed</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                    <Row label="Labour" cost={costing.labour_cost} billed={costing.labour_billed} />
                    <Row label="Parts" cost={costing.parts_cost} billed={costing.parts_billed} />
                </tbody>
                <tfoot>
                    <tr className="border-t-2 border-slate-200 font-bold text-slate-900">
                        <td className="py-2">Total</td>
                        <td className="py-2 text-right tabular-nums">{money(costing.total_cost)}</td>
                        <td className="py-2 text-right tabular-nums">{money(costing.total_billed)}</td>
                    </tr>
                </tfoot>
            </table>
            <div className={`mt-3 rounded-lg px-3 py-2 text-sm flex items-center justify-between ${costing.margin_pct >= 30 ? 'bg-emerald-50 text-emerald-700' : costing.margin_pct >= 0 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'}`}>
                <span className="font-medium">Margin</span>
                <span className="font-bold tabular-nums">{costing.margin_pct}%</span>
            </div>
        </div>
    );
}

function InvoicePanel({ job }) {
    const [method, setMethod] = useState('account');
    const [processing, setProcessing] = useState(false);
    const total = Math.round(job.costing.total_billed * 1.15 * 100) / 100; // + VAT
    const isAccount = job.customer && !job.customer.is_walk_in;

    function invoice() {
        setProcessing(true);
        const payments = method === 'account'
            ? [{ method: 'account', amount: total }]
            : [{ method, amount: total, tendered: method === 'cash' ? total : null }];
        router.post(route('workshop.jobs.invoice', job.id), { payments }, { onFinish: () => setProcessing(false) });
    }

    return (
        <div className="rounded-xl border border-orange-200 bg-orange-50/40 p-5 space-y-3">
            <h2 className="text-sm font-semibold text-slate-700">Invoice job</h2>
            <div className="flex justify-between text-sm"><span className="text-slate-500">Total incl VAT</span><span className="font-bold tabular-nums text-slate-900"><MoneyDisplay amount={total} /></span></div>
            <select value={method} onChange={(e) => setMethod(e.target.value)} aria-label="Payment method" className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="account" disabled={!isAccount}>On account</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="eft">EFT</option>
            </select>
            <button onClick={invoice} disabled={processing} className="w-full rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                {processing ? 'Invoicing…' : `Invoice ${money(total)}`}
            </button>
        </div>
    );
}
