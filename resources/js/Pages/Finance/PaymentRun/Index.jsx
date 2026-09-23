import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Play } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ConfirmDialog from '@/Components/ConfirmDialog';

const money = (n) => Number(n || 0).toFixed(2);

export default function PaymentRunIndex({ dueInvoices, branches }) {
    const [selected, setSelected] = useState({}); // {invoiceId: true}
    const [branchId, setBranchId] = useState(branches[0]?.id ?? '');
    const [method, setMethod] = useState('eft');
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const chosen = dueInvoices.filter((i) => selected[i.id]);
    const total = chosen.reduce((n, i) => n + i.outstanding, 0);
    const allChecked = dueInvoices.length > 0 && chosen.length === dueInvoices.length;

    function toggleAll() {
        if (allChecked) { setSelected({}); return; }
        const next = {};
        dueInvoices.forEach((i) => { next[i.id] = true; });
        setSelected(next);
    }

    function execute() {
        setProcessing(true);
        router.post(route('finance.payment-run.execute'), {
            branch_id: branchId, method, invoice_ids: chosen.map((i) => i.id),
        }, { onFinish: () => { setProcessing(false); setOpen(false); } });
    }

    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Payment Run"
            breadcrumbs={[
                { label: 'Finance & Accounts', href: route('modules.show', 'finance') },
                { label: 'Supplier Payments', href: route('finance.payments.index') },
                { label: 'Payment Run' },
            ]}
        >
            <Head title="Payment Run" />

            <div className="max-w-4xl space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <select aria-label="Pay from branch" value={branchId} onChange={(e) => setBranchId(e.target.value)} className={input}>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <select aria-label="Method" value={method} onChange={(e) => setMethod(e.target.value)} className={input}>
                        <option value="eft">EFT</option><option value="bank_transfer">Bank transfer</option><option value="cheque">Cheque</option>
                    </select>
                    <div className="ml-auto text-sm text-slate-500">
                        Selected <strong className="text-slate-800">{chosen.length}</strong> · <span className="tabular-nums font-semibold text-slate-900"><MoneyDisplay amount={total} /></span>
                    </div>
                    <button onClick={() => setOpen(true)} disabled={chosen.length === 0}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        <Play className="w-4 h-4" /> Run Batch
                    </button>
                </div>

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th className="px-4 py-2 w-10"><input type="checkbox" checked={allChecked} onChange={toggleAll} aria-label="Select all" className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" /></th>
                                <th className="px-2 py-2">Invoice</th>
                                <th className="px-2 py-2">Supplier</th>
                                <th className="px-2 py-2">Due</th>
                                <th className="px-4 py-2 text-right">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50">
                            {dueInvoices.length === 0 ? (
                                <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-400">No outstanding supplier invoices.</td></tr>
                            ) : dueInvoices.map((inv) => (
                                <tr key={inv.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-2"><input type="checkbox" checked={!!selected[inv.id]} onChange={(e) => setSelected((prev) => ({ ...prev, [inv.id]: e.target.checked }))} aria-label={`Select ${inv.invoice_number}`} className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" /></td>
                                    <td className="px-2 py-2 font-mono text-slate-700">{inv.invoice_number}</td>
                                    <td className="px-2 py-2 text-slate-600">{inv.supplier}</td>
                                    <td className="px-2 py-2 tabular-nums text-slate-400">{inv.due_date ?? '—'}</td>
                                    <td className="px-4 py-2 text-right tabular-nums text-slate-700">{money(inv.outstanding)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <ConfirmDialog
                open={open}
                title={`Run batch payment for ${chosen.length} invoice(s)?`}
                message={`One payment per supplier will post, clearing ${money(total)} of creditors. This cannot be undone.`}
                confirmLabel="Run batch"
                processing={processing}
                onConfirm={execute}
                onCancel={() => setOpen(false)}
            />
        </ModuleLayout>
    );
}
