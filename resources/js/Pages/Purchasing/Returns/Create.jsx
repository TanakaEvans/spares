import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import FormField from '@/Components/FormField';
import PartLinePicker from '@/Components/PartLinePicker';

export default function ReturnCreate({ suppliers, branches }) {
    const { data, setData, post, processing, errors } = useForm({
        supplier_id: '', branch_id: branches[0]?.id ?? '', reason: 'damaged', notes: '', lines: [],
    });
    const [lines, setLines] = useState([]);

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function sync(next) {
        setLines(next);
        setData('lines', next.map(({ part_id, qty, condition }) => ({ part_id, qty, condition })));
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Supplier Return"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Returns', href: route('purchasing.returns.index') },
                { label: 'New Return' },
            ]}
        >
            <Head title="New Supplier Return" />

            <form
                onSubmit={(e) => { e.preventDefault(); post(route('purchasing.returns.store')); }}
                className="max-w-3xl space-y-6"
            >
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <FormField label="Supplier" htmlFor="rt-supplier" required error={errors.supplier_id}>
                            <select id="rt-supplier" value={data.supplier_id} onChange={(e) => setData('supplier_id', e.target.value)} className={input}>
                                <option value="">Select…</option>
                                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </FormField>
                        <FormField label="From branch" htmlFor="rt-branch" required error={errors.branch_id}>
                            <select id="rt-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                        <FormField label="Reason" htmlFor="rt-reason" required error={errors.reason}>
                            <select id="rt-reason" value={data.reason} onChange={(e) => setData('reason', e.target.value)} className={input}>
                                <option value="damaged">Damaged</option>
                                <option value="incorrect_part">Incorrect part</option>
                                <option value="excess_stock">Excess stock</option>
                                <option value="warranty">Warranty</option>
                            </select>
                        </FormField>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Parts to return</h2>
                    <PartLinePicker onPick={(p) => {
                        if (lines.some((l) => l.part_id === p.id)) return;
                        sync([...lines, { part_id: p.id, part_number: p.part_number, description: p.description, qty: 1, condition: 'damaged' }]);
                    }} />
                    {errors.lines && <p className="text-xs text-red-600">{errors.lines}</p>}

                    {lines.map((l, i) => (
                        <div key={l.part_id} className="flex items-center gap-3 text-sm">
                            <span className="font-mono font-semibold text-slate-800 w-36 truncate">{l.part_number}</span>
                            <span className="flex-1 text-slate-500 truncate">{l.description}</span>
                            <input
                                type="number" step="0.01" min="0.01" value={l.qty}
                                aria-label={`Quantity for ${l.part_number}`}
                                onChange={(e) => sync(lines.map((x, idx) => idx === i ? { ...x, qty: Number(e.target.value) || 0 } : x))}
                                className="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-right tabular-nums"
                            />
                            <select
                                value={l.condition}
                                aria-label={`Condition for ${l.part_number}`}
                                onChange={(e) => sync(lines.map((x, idx) => idx === i ? { ...x, condition: e.target.value } : x))}
                                className="w-28 rounded-lg border border-slate-300 px-2 py-1.5"
                            >
                                <option value="new">New</option>
                                <option value="damaged">Damaged</option>
                                <option value="used">Used</option>
                            </select>
                            <button type="button" onClick={() => sync(lines.filter((_, idx) => idx !== i))} aria-label="Remove line" className="p-1.5 rounded text-slate-300 hover:text-red-600 hover:bg-red-50">
                                <Trash2 className="w-4 h-4" />
                            </button>
                        </div>
                    ))}
                </div>

                <div className="flex justify-end gap-3">
                    <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={processing || !data.supplier_id || lines.length === 0}
                        className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                    >
                        {processing ? 'Creating…' : 'Create Return'}
                    </button>
                </div>
            </form>
        </ModuleLayout>
    );
}
