import { Head, router, useForm } from '@inertiajs/react';
import { Percent } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';
import { StatTile } from '@/Components/Charts';

export default function VatIndex({ preview, returns, filters }) {
    const { data, setData, post, processing } = useForm({ from: filters.from, to: filters.to });

    function apply(params) {
        router.get(route('finance.vat.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    }
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="VAT Management"
            breadcrumbs={[{ label: 'Finance & Accounts', href: route('modules.show', 'finance') }, { label: 'VAT Returns' }]}>
            <Head title="VAT Management" />

            <div className="max-w-3xl space-y-6">
                <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-4">
                    <h2 className="text-sm font-semibold text-slate-700">Period preview — live from the ledger</h2>
                    <div className="flex flex-wrap items-center gap-3">
                        <label className="text-sm text-slate-500">From</label>
                        <input type="date" value={filters.from} onChange={(e) => { apply({ from: e.target.value }); setData('from', e.target.value); }} className={input} />
                        <label className="text-sm text-slate-500">to</label>
                        <input type="date" value={filters.to} onChange={(e) => { apply({ to: e.target.value }); setData('to', e.target.value); }} className={input} />
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                        <StatTile label="Output VAT (2210)" value={preview.output_vat} money />
                        <StatTile label="Input VAT (2220)" value={preview.input_vat} money />
                        <StatTile label="Net payable" value={preview.net_payable} money tone={preview.net_payable >= 0 ? 'red' : 'emerald'} />
                    </div>
                    <div className="flex justify-end">
                        <button onClick={() => post(route('finance.vat.generate'))} disabled={processing}
                            className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                            {processing ? 'Generating…' : 'Generate VAT return'}
                        </button>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <div className="px-4 py-2.5 border-b border-slate-100"><h2 className="text-sm font-semibold text-slate-700">Filed returns</h2></div>
                    {returns.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><Percent className="w-8 h-8 mx-auto mb-2 text-slate-300" />No VAT returns generated yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-4 py-2">Reference</th><th className="px-2 py-2">Period</th>
                                    <th className="px-2 py-2 text-right">Output</th><th className="px-2 py-2 text-right">Input</th>
                                    <th className="px-2 py-2 text-right">Net</th><th className="px-2 py-2">Status</th><th className="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {returns.map((r) => (
                                    <tr key={r.id} className="hover:bg-slate-50">
                                        <td className="px-4 py-2 font-mono font-semibold text-slate-800">{r.reference}</td>
                                        <td className="px-2 py-2 tabular-nums text-slate-500">{r.period}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.output_vat.toFixed(2)}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-600">{r.input_vat.toFixed(2)}</td>
                                        <td className="px-2 py-2 text-right tabular-nums font-medium text-slate-800"><MoneyDisplay amount={r.net_payable} /></td>
                                        <td className="px-2 py-2"><StatusBadge status={r.status} /></td>
                                        <td className="px-4 py-2 text-right">
                                            {r.status === 'draft' && <button onClick={() => router.post(route('finance.vat.transition', r.id), { action: 'submit' }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Submit</button>}
                                            {r.status === 'submitted' && <button onClick={() => router.post(route('finance.vat.transition', r.id), { action: 'pay' }, { preserveScroll: true })} className="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Mark paid</button>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
