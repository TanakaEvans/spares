// Shared tender logic — used by the POS TenderDialog and any screen that
// takes payment (order fulfilment). Presentation lives in PaymentPanel.jsx.
import { useRef, useState } from 'react';
import { round2, fmt } from './posMath';

export function accountBlockReason(customer) {
    if (!customer || customer.is_walk_in) return 'Choose an account customer first.';
    if (customer.on_hold) return `${customer.name} is on credit hold.`;
    if (customer.available_credit != null && customer.available_credit <= 0) return `${customer.name} has no credit available.`;
    return null;
}

/**
 * Tender state + maths for one payment capture. `reset` runs when a dialog
 * opens so stale tenders never carry across sales.
 */
export function useTender(total) {
    const [mode, setMode] = useState('cash');
    const [tendered, setTendered] = useState('');
    const [reference, setReference] = useState('');
    const [split, setSplit] = useState([]);
    const amountRef = useRef(null);

    function reset(initialMode = 'cash') {
        setMode(initialMode);
        setTendered('');
        setReference('');
        setSplit([{ method: 'cash', amount: total, tendered: '', reference: '' }]);
    }

    const cashGiven = tendered === '' ? total : Number(tendered);
    const change = mode === 'cash' ? Math.max(0, round2(cashGiven - total)) : 0;
    const splitPaid = round2(split.reduce((n, p) => n + Number(p.amount || 0), 0));
    const splitBalance = round2(total - splitPaid);
    const splitCash = split.find((p) => p.method === 'cash');
    const splitChange = splitCash && splitCash.tendered !== '' ? Math.max(0, round2(Number(splitCash.tendered) - Number(splitCash.amount || 0))) : 0;

    const setS = (i, field, value) => setSplit((prev) => prev.map((p, idx) => (idx === i ? { ...p, [field]: value } : p)));

    /** Why the submit must stay disabled — null when the tender is clean. */
    function blocker(customer, extra = []) {
        const accountBlocked = accountBlockReason(customer);
        for (const rule of extra) {
            const why = rule();
            if (why) return why;
        }
        if (mode === 'cash' && cashGiven < total - 0.001) return `Cash received is short by ${fmt(total - cashGiven)}.`;
        if (mode === 'account' && accountBlocked) return accountBlocked;
        if (mode === 'split') {
            if (split.some((p) => Number(p.amount || 0) <= 0)) return 'Every split line needs an amount — remove blank lines.';
            if (Math.abs(splitBalance) > 0.001) return splitBalance > 0 ? `${fmt(splitBalance)} still to allocate.` : `Over-allocated by ${fmt(-splitBalance)}.`;
            if (split.some((p) => p.method === 'account') && accountBlocked) return accountBlocked;
            if (splitCash && splitCash.tendered !== '' && Number(splitCash.tendered) < Number(splitCash.amount)) return 'Cash received is less than the cash portion.';
        }
        return null;
    }

    /** Payment rows in the shape SalesPostingService / fulfil expects. */
    function payments() {
        if (mode === 'cash') return [{ method: 'cash', amount: total, tendered: cashGiven, reference: null }];
        if (mode === 'split') {
            return split.map((p) => ({
                method: p.method, amount: Number(p.amount),
                tendered: p.method === 'cash' ? Number(p.tendered === '' ? p.amount : p.tendered) : null,
                reference: p.reference || null,
            }));
        }
        return [{ method: mode, amount: total, tendered: null, reference: reference || null }];
    }

    return {
        total, mode, setMode, tendered, setTendered, reference, setReference, split, setSplit, setS,
        amountRef, reset, cashGiven, change, splitPaid, splitBalance, splitCash, splitChange,
        blocker, payments,
    };
}
