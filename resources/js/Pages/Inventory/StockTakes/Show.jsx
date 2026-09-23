import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, ClipboardCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmDialog from '@/Components/ConfirmDialog';

function CountInput({ take, line }) {
    const [value, setValue] = useState(line.counted_qty ?? '');
    const [saving, setSaving] = useState(false);

    function save() {
        if (value === '' || Number(value) === Number(line.counted_qty)) return;
        setSaving(true);
        router.patch(route('inventory.stock-takes.count', [take.id, line.id]), { counted_qty: value }, {
            preserveScroll: true, preserveState: true, onFinish: () => setSaving(false),
        });
    }

    return (
        <input
            type="number" step="0.01" min="0" value={value}
            aria-label={`Counted qty for ${line.part_number}`}
            onChange={(e) => setValue(e.target.value)}
            onBlur={save}
            onKeyDown={(e) => e.key === 'Enter' && e.currentTarget.blur()}
            className={`w-24 rounded border px-2 py-1 text-right tabular-nums ${saving ? 'border-orange-300 bg-orange-50' : 'border-slate-300'}`}
        />
    );
}

export default function StockTakeShow({ take, recountValue }) {
    const [postOpen, setPostOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const counting = take.status === 'counting';
    const review = take.status === 'review';

    const countedLines = take.lines.filter((l) => l.counted_qty !== null);
    const varianceLines = countedLines.filter((l) => Math.abs(l.variance) > 0.001);
    const netValue = varianceLines.reduce((n, l) => n + l.variance_value, 0);

    function act(routeName, opts = {}) {
        setProcessing(true);
        router.post(route(routeName, take.id), {}, { ...opts, onFinish: () => { setProcessing(false); setPostOpen(false); } });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={take.take_number}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Stock Takes', href: route('inventory.stock-takes.index') },
                { label: take.take_number },
            ]}
        >
            <Head title={take.take_number} />

            <div className="max-w-4xl space-y-5">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{take.take_number}</h1>
                            <StatusBadge status={take.status} />
                        </div>
                        <p className="mt-1 text-sm text-slate-500 capitalize">{take.type} count · {take.branch} · {take.lines.length} lines · {countedLines.length} counted</p>
                    </div>
                    <div className="flex gap-2">
                        {counting && (
                            <button onClick={() => act('inventory.stock-takes.review', { preserveScroll: true })} disabled={processing}
                                className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40">
                                Lock &amp; review
                            </button>
                        )}
                        {(review || counting) && (
                            <button onClick={() => setPostOpen(true)} disabled={processing}
                                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                                <ClipboardCheck className="w-4 h-4" />
                                Post variance
                            </button>
                        )}
                        {take.status === 'posted' && take.adjustment_id && (
                            <Link href={route('inventory.adjustments.index')} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <CheckCircle2 className="w-4 h-4 text-emerald-500" />
                                View adjustment
                            </Link>
                        )}
                    </div>
                </div>

                {(review || take.status === 'posted') && varianceLines.length > 0 && (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        {varianceLines.length} line{varianceLines.length > 1 ? 's' : ''} with variance · net stock value change{' '}
                        <span className={netValue < 0 ? 'text-red-600 font-semibold' : 'text-emerald-600 font-semibold'}>
                            <MoneyDisplay amount={netValue} />
                        </span>
                    </div>
                )}

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2.5">Part</th>
                                <th className="px-2 py-2.5 text-right">System</th>
                                <th className="px-2 py-2.5 text-right">Counted</th>
                                <th className="px-2 py-2.5 text-right">Variance</th>
                                <th className="px-4 py-2.5 text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {take.lines.map((l) => {
                                const bigVariance = l.variance_value !== null && Math.abs(l.variance_value) >= recountValue;
                                return (
                                    <tr key={l.id} className={bigVariance ? 'bg-amber-50' : ''}>
                                        <td className="px-4 py-2.5">
                                            <span className="font-mono font-semibold text-slate-700">{l.part_number}</span>
                                            <span className="block text-xs text-slate-400 truncate max-w-[18rem]">{l.description}</span>
                                            {bigVariance && <span className="text-xs text-amber-600">Large variance — recount recommended.</span>}
                                        </td>
                                        <td className="px-2 py-2.5 text-right tabular-nums text-slate-400">{l.system_qty}</td>
                                        <td className="px-2 py-2.5 text-right">
                                            {counting ? <CountInput take={take} line={l} />
                                                : <span className="tabular-nums text-slate-700">{l.counted_qty ?? '—'}</span>}
                                        </td>
                                        <td className="px-2 py-2.5 text-right tabular-nums">
                                            {l.variance === null ? <span className="text-slate-300">—</span>
                                                : <span className={l.variance < 0 ? 'text-red-600' : l.variance > 0 ? 'text-emerald-600' : 'text-slate-400'}>{l.variance > 0 ? '+' : ''}{l.variance}</span>}
                                        </td>
                                        <td className="px-4 py-2.5 text-right tabular-nums text-slate-500">
                                            {l.variance_value === null ? '—' : <MoneyDisplay amount={l.variance_value} />}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>

            <ConfirmDialog
                open={postOpen}
                title={`Post ${take.take_number}?`}
                message={varianceLines.length === 0
                    ? 'No variances found — the take will close with no stock movement.'
                    : `${varianceLines.length} variance line${varianceLines.length > 1 ? 's' : ''} will post as one stock adjustment. This cannot be undone.`}
                confirmLabel="Post variance"
                processing={processing}
                onConfirm={() => act('inventory.stock-takes.post')}
                onCancel={() => setPostOpen(false)}
            />
        </ModuleLayout>
    );
}
