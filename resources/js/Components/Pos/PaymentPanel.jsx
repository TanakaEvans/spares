import { Banknote, CreditCard, Landmark, BookUser, Split, X } from 'lucide-react';
import MoneyDisplay from '@/Components/MoneyDisplay';
import { cashSuggestions, fmt } from './posMath';
import { accountBlockReason } from './paymentCore';

export const PAYMENT_MODES = [
    { key: 'cash', label: 'Cash', icon: Banknote, kbd: 'F5' },
    { key: 'card', label: 'Card', icon: CreditCard, kbd: 'F6' },
    { key: 'eft', label: 'EFT', icon: Landmark },
    { key: 'account', label: 'Account', icon: BookUser, kbd: 'F7' },
    { key: 'split', label: 'Split', icon: Split, kbd: 'F4' },
];

const field = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base';

/**
 * The payment body shared by every tender dialog: method tiles, cash +
 * change, card/EFT reference, account credit panel and split tender.
 * State lives in `useTender` so the caller decides how to submit.
 */
export default function PaymentPanel({ tender, customer, onEnter }) {
    const {
        mode, setMode, tendered, setTendered, reference, setReference, split, setS, setSplit,
        amountRef, cashGiven, change, splitBalance, splitCash, splitChange,
    } = tender;
    const total = tender.total;
    const accountBlocked = accountBlockReason(customer);

    return (
        <div className="space-y-5">
            <div className="grid grid-cols-5 gap-2" role="tablist" aria-label="Payment method">
                {PAYMENT_MODES.map((m) => {
                    const disabled = m.key === 'account' && !!accountBlocked;
                    return (
                        <button key={m.key} type="button" role="tab" aria-selected={mode === m.key} disabled={disabled}
                            title={disabled ? accountBlocked : undefined}
                            onClick={() => setMode(m.key)}
                            className={`flex flex-col items-center gap-1 rounded-xl border-2 px-2 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-40 ${mode === m.key ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'}`}>
                            <m.icon className="h-5 w-5" />
                            {m.label}
                            {m.kbd && <kbd className="font-mono text-[10px] font-normal text-slate-400">{m.kbd}</kbd>}
                        </button>
                    );
                })}
            </div>

            {mode === 'cash' && (
                <div className="space-y-3">
                    <label className="block">
                        <span className="mb-1 block text-xs font-medium text-slate-500">Cash received (leave blank for exact)</span>
                        <input ref={amountRef} type="number" step="0.01" min="0" inputMode="decimal" value={tendered} placeholder={fmt(total)}
                            onChange={(e) => setTendered(e.target.value)} onKeyDown={onEnter} aria-label="Cash received"
                            className={`${field} text-right text-2xl font-bold tabular-nums`} />
                    </label>
                    <div className="flex flex-wrap gap-2">
                        {cashSuggestions(total).map((v, i) => (
                            <button key={v} type="button" onClick={() => { setTendered(String(v)); amountRef.current?.focus(); }}
                                className={`rounded-lg border px-4 py-2 text-sm font-semibold tabular-nums ${Number(tendered) === v || (tendered === '' && i === 0) ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50'}`}>
                                {i === 0 ? `Exact ${fmt(v)}` : fmt(v)}
                            </button>
                        ))}
                    </div>
                    <div className={`flex items-center justify-between rounded-xl px-5 py-4 ${change > 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-50 text-slate-500'}`}>
                        <span className="text-sm font-semibold uppercase tracking-wide">Change due</span>
                        <span className="text-3xl font-bold tabular-nums"><MoneyDisplay amount={change} /></span>
                    </div>
                </div>
            )}

            {(mode === 'card' || mode === 'eft') && (
                <label className="block">
                    <span className="mb-1 block text-xs font-medium text-slate-500">{mode === 'card' ? 'Card approval / slip number' : 'EFT reference'} (optional)</span>
                    <input ref={amountRef} value={reference} onChange={(e) => setReference(e.target.value)} onKeyDown={onEnter}
                        placeholder={mode === 'card' ? 'From the card machine slip' : 'Bank reference'} className={field} aria-label="Payment reference" />
                    <span className="mt-1 block text-xs text-slate-400">Charge exactly <MoneyDisplay amount={total} /> on the {mode === 'card' ? 'card machine' : 'bank transfer'} — no change on {mode.toUpperCase()}.</span>
                </label>
            )}

            {mode === 'account' && (
                <div className="rounded-xl border border-slate-200 p-4 text-sm space-y-1.5">
                    <div className="flex justify-between"><span className="text-slate-500">Current balance</span><MoneyDisplay amount={customer.ar_balance ?? 0} /></div>
                    <div className="flex justify-between"><span className="text-slate-500">Available credit now</span><MoneyDisplay amount={customer.available_credit ?? 0} /></div>
                    <div className={`flex justify-between font-semibold ${(customer.available_credit ?? 0) - total < 0 ? 'text-red-600' : 'text-emerald-700'}`}>
                        <span>Available after this sale</span><MoneyDisplay amount={(customer.available_credit ?? 0) - total} />
                    </div>
                    <input ref={amountRef} value={reference} onChange={(e) => setReference(e.target.value)} onKeyDown={onEnter}
                        placeholder="Customer order / job number (optional)" className={`${field} mt-2 text-sm`} aria-label="Order reference" />
                    {(customer.available_credit ?? 0) - total < 0 && <p className="text-xs text-red-600">This exceeds the credit limit — the system will refuse it unless the limit is raised.</p>}
                </div>
            )}

            {mode === 'split' && (
                <div className="space-y-3">
                    {split.map((p, i) => (
                        <div key={i} className="flex items-end gap-2">
                            <select value={p.method} onChange={(e) => setS(i, 'method', e.target.value)} aria-label="Method" className={`${field} w-32`}>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="eft">EFT</option>
                                <option value="account" disabled={!!accountBlocked}>Account</option>
                            </select>
                            <input ref={i === 0 ? amountRef : undefined} type="number" step="0.01" min="0" value={p.amount} onChange={(e) => setS(i, 'amount', e.target.value)}
                                aria-label="Amount" placeholder="0.00" className={`${field} flex-1 text-right font-semibold tabular-nums`} />
                            {p.method === 'cash'
                                ? <input type="number" step="0.01" min="0" value={p.tendered} onChange={(e) => setS(i, 'tendered', e.target.value)} aria-label="Cash received" placeholder="Received" className={`${field} w-36 text-right tabular-nums`} />
                                : <input value={p.reference} onChange={(e) => setS(i, 'reference', e.target.value)} aria-label="Reference" placeholder="Reference" className={`${field} w-36`} />}
                            {split.length > 1 && <button type="button" onClick={() => setSplit((prev) => prev.filter((_, idx) => idx !== i))} aria-label="Remove payment line" className="mb-2.5 p-1 text-slate-300 hover:text-red-600"><X className="h-5 w-5" /></button>}
                        </div>
                    ))}
                    <div className="flex items-center justify-between">
                        <button type="button" disabled={splitBalance <= 0}
                            onClick={() => setSplit((prev) => [...prev, { method: prev.some((x) => x.method === 'card') ? 'eft' : 'card', amount: splitBalance, tendered: '', reference: '' }])}
                            className="text-sm font-medium text-orange-600 hover:text-orange-700 disabled:opacity-40">+ Add a payment for the balance</button>
                        <span className={`text-sm font-semibold tabular-nums ${Math.abs(splitBalance) > 0.001 ? 'text-amber-600' : 'text-emerald-600'}`}>
                            {splitBalance > 0.001 ? `Balance ${fmt(splitBalance)}` : splitBalance < -0.001 ? `Over ${fmt(-splitBalance)}` : 'Fully allocated'}
                            {splitChange > 0 && ` · change ${fmt(splitChange)}`}
                        </span>
                    </div>
                </div>
            )}
        </div>
    );
}
