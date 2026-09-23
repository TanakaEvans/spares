import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Search, Truck, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/finance';
import FormField from '@/Components/FormField';

const money = (n) => Number(n || 0).toFixed(2);

export default function PaymentCreate({ branches }) {
    const [supplier, setSupplier] = useState(null);
    const [openInvoices, setOpenInvoices] = useState([]);
    const [alloc, setAlloc] = useState({});
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [form, setForm] = useState({ branch_id: branches[0]?.id ?? '', payment_date: new Date().toISOString().slice(0, 10), method: 'eft', amount: '', reference: '' });
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try {
                const res = await fetch(route('finance.payments.supplier-lookup') + '?' + new URLSearchParams({ q: query }), { headers: { Accept: 'application/json' } });
                setResults((await res.json()).suppliers ?? []);
            } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    async function pickSupplier(s) {
        setQuery(''); setResults([]);
        const res = await fetch(route('finance.payments.supplier-lookup') + '?' + new URLSearchParams({ supplier_id: s.id }), { headers: { Accept: 'application/json' } });
        const data = await res.json();
        setSupplier({ ...data.supplier, ap_balance: data.ap_balance });
        setOpenInvoices(data.open_invoices ?? []);
        setAlloc({});
    }

    const setF = (k, v) => setForm((prev) => ({ ...prev, [k]: v }));
    const allocTotal = Object.values(alloc).reduce((n, v) => n + (Number(v) || 0), 0);
    const amount = Number(form.amount) || 0;

    function autoSettle() {
        let remaining = amount;
        const next = {};
        for (const inv of openInvoices) {
            if (remaining <= 0) break;
            const take = Math.min(remaining, inv.outstanding);
            next[inv.id] = Number(take.toFixed(2));
            remaining -= take;
        }
        setAlloc(next);
    }

    function payAll() {
        const total = openInvoices.reduce((n, inv) => n + inv.outstanding, 0);
        setF('amount', total.toFixed(2));
        const next = {};
        openInvoices.forEach((inv) => { next[inv.id] = Number(inv.outstanding.toFixed(2)); });
        setAlloc(next);
    }

    function submit() {
        setProcessing(true); setError(null);
        router.post(route('finance.payments.store'), {
            ...form,
            supplier_id: supplier.id,
            amount,
            allocations: Object.entries(alloc).filter(([, v]) => Number(v) > 0)
                .map(([supplier_invoice_id, v]) => ({ supplier_invoice_id: Number(supplier_invoice_id), amount: Number(v) })),
        }, { onError: (e) => { setError(Object.values(e)[0]); setProcessing(false); }, onFinish: () => setProcessing(false) });
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Payment"
            breadcrumbs={[
                { label: 'Finance & Accounts', href: route('modules.show', 'finance') },
                { label: 'Supplier Payments', href: route('finance.payments.index') },
                { label: 'New Payment' },
            ]}
        >
            <Head title="New Payment" />

            {error && <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

            <div className="max-w-3xl space-y-6">
                <div className="rounded-xl border border-slate-100 bg-white p-5">
                    {supplier ? (
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Truck className="w-4 h-4 text-slate-400" />
                                <div>
                                    <div className="text-sm font-semibold text-slate-800">{supplier.name}</div>
                                    <div className="text-xs text-slate-400">{supplier.supplier_number} · owed {money(supplier.ap_balance)}</div>
                                </div>
                            </div>
                            <button onClick={() => { setSupplier(null); setOpenInvoices([]); setAlloc({}); }} aria-label="Change" className="p-1 text-slate-300 hover:text-slate-600"><X className="w-4 h-4" /></button>
                        </div>
                    ) : (
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search supplier…" aria-label="Search supplier" className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm" />
                            {results.length > 0 && (
                                <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                    {results.map((s) => (
                                        <li key={s.id}>
                                            <button type="button" onClick={() => pickSupplier(s)} className="w-full px-3 py-2 text-left text-sm hover:bg-orange-50">
                                                {s.name} <span className="text-xs text-slate-400">{s.supplier_number}</span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}
                </div>

                {supplier && (
                    <>
                        <div className="rounded-xl border border-slate-100 bg-white p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <FormField label="Date" htmlFor="p-date" required><input id="p-date" type="date" value={form.payment_date} onChange={(e) => setF('payment_date', e.target.value)} className={input} /></FormField>
                            <FormField label="Method" htmlFor="p-method" required>
                                <select id="p-method" value={form.method} onChange={(e) => setF('method', e.target.value)} className={input}>
                                    <option value="eft">EFT</option><option value="bank_transfer">Bank transfer</option><option value="cheque">Cheque</option><option value="cash">Cash</option>
                                </select>
                            </FormField>
                            <FormField label="Amount" htmlFor="p-amt" required><input id="p-amt" type="number" step="0.01" min="0" value={form.amount} onChange={(e) => setF('amount', e.target.value)} className={input} /></FormField>
                            <FormField label="Reference" htmlFor="p-ref"><input id="p-ref" value={form.reference} onChange={(e) => setF('reference', e.target.value)} className={input} /></FormField>
                        </div>

                        <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                                <h2 className="text-sm font-semibold text-slate-700">Allocate to invoices</h2>
                                <div className="flex gap-3">
                                    <button type="button" onClick={payAll} disabled={openInvoices.length === 0} className="text-xs font-medium text-orange-600 hover:text-orange-700 disabled:opacity-40">Pay all</button>
                                    <button type="button" onClick={autoSettle} disabled={!amount} className="text-xs font-medium text-orange-600 hover:text-orange-700 disabled:opacity-40">Auto-settle oldest</button>
                                </div>
                            </div>
                            {openInvoices.length === 0 ? (
                                <p className="px-4 py-6 text-center text-sm text-slate-400">No open invoices for this supplier.</p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                            <th className="px-4 py-2">Invoice</th>
                                            <th className="px-2 py-2">Due</th>
                                            <th className="px-2 py-2 text-right">Outstanding</th>
                                            <th className="px-4 py-2 text-right w-32">Allocate</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-50">
                                        {openInvoices.map((inv) => (
                                            <tr key={inv.id}>
                                                <td className="px-4 py-2 font-mono text-slate-700">{inv.invoice_number}<span className="block text-xs text-slate-400">{inv.supplier_ref}</span></td>
                                                <td className="px-2 py-2 tabular-nums text-slate-400">{inv.due_date ?? '—'}</td>
                                                <td className="px-2 py-2 text-right tabular-nums text-slate-600">{money(inv.outstanding)}</td>
                                                <td className="px-4 py-2 text-right">
                                                    <input type="number" step="0.01" min="0" max={inv.outstanding}
                                                        value={alloc[inv.id] ?? ''} aria-label={`Allocate ${inv.invoice_number}`}
                                                        onChange={(e) => setAlloc((prev) => ({ ...prev, [inv.id]: Math.min(inv.outstanding, Number(e.target.value) || 0) }))}
                                                        className="w-28 rounded border border-slate-300 px-2 py-1 text-right tabular-nums" />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                            <div className="flex justify-between px-4 py-2.5 border-t border-slate-100 text-sm">
                                <span className="text-slate-500">Allocated {money(allocTotal)} of {money(amount)}</span>
                                <span className={allocTotal > amount + 0.001 ? 'text-red-600 font-medium' : 'text-slate-500'}>
                                    {amount - allocTotal > 0.001 ? `${money(amount - allocTotal)} unallocated` : 'Fully allocated'}
                                </span>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3">
                            <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                            <button type="button" onClick={submit} disabled={processing || !amount || allocTotal > amount + 0.001}
                                className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                {processing ? 'Posting…' : 'Post Payment'}
                            </button>
                        </div>
                    </>
                )}
            </div>
        </ModuleLayout>
    );
}
