import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { ShieldCheck, Loader2 } from 'lucide-react';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PaymentPanel from './PaymentPanel';
import { useTender, accountBlockReason } from './paymentCore';

export { accountBlockReason };

/**
 * Take payment (2.1 rules 2, 3, 9): one-key tenders, note buttons for cash,
 * change computed on cash only, split tender, and the supervisor approval
 * for over-limit discounts — all re-verified by the server on post.
 */
export default function TenderDialog({ open, initialMode, total, customer, lines, maxDiscount, onClose, onPosted }) {
    const tender = useTender(total);
    const [supervisor, setSupervisor] = useState({ username: '', password: '' });
    const [approver, setApprover] = useState(null);
    const [verifying, setVerifying] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    const needsApproval = lines.some((l) => (l.discount_pct || 0) > maxDiscount);

    useEffect(() => {
        if (!open) return;
        tender.reset(initialMode ?? 'cash');
        setError(null); setProcessing(false); setApprover(null); setSupervisor({ username: '', password: '' });
    }, [open, initialMode]);

    useEffect(() => { if (open) setTimeout(() => tender.amountRef.current?.focus(), 30); }, [open, tender.mode]);

    async function verifySupervisor() {
        setVerifying(true); setError(null);
        try {
            const res = await window.axios.post(route('sales.pos.verify-supervisor'), supervisor);
            setApprover(res.data.approver);
        } catch (e) {
            setApprover(null);
            setError(e.response?.data?.message ?? 'Could not verify the supervisor.');
        } finally { setVerifying(false); }
    }

    const why = tender.blocker(customer, [() => (needsApproval && !approver ? 'A supervisor must approve the over-limit discount first.' : null)]);

    function submit() {
        const block = why;
        if (block) { setError(block); return; }
        setProcessing(true); setError(null);
        router.post(route('sales.pos.store'), {
            customer_id: customer.id,
            lines: lines.map((l) => ({ part_id: l.part_id, qty: l.qty, discount_pct: l.discount_pct })),
            payments: tender.payments(),
            supervisor_username: needsApproval ? supervisor.username : null,
            supervisor_password: needsApproval ? supervisor.password : null,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => onPosted(),
            onError: (errs) => setError(Object.values(errs)[0]),
            onFinish: () => setProcessing(false),
        });
    }

    const onEnter = (e) => { if (e.key === 'Enter') { e.preventDefault(); submit(); } };

    return (
        <Dialog open={open} onOpenChange={(o) => !o && !processing && onClose()}>
            <DialogContent showCloseButton={false} className="bg-white sm:max-w-3xl p-0 gap-0 overflow-hidden" onKeyDown={(e) => {
                const hot = { F5: 'cash', F6: 'card', F7: 'account', F4: 'split' }[e.key];
                if (hot) { e.preventDefault(); if (hot !== 'account' || !accountBlockReason(customer)) tender.setMode(hot); }
            }}>
                <div className="flex items-center justify-between bg-slate-900 px-6 py-4 text-white">
                    <div>
                        <DialogTitle className="text-lg font-semibold">Take payment</DialogTitle>
                        <DialogDescription className="text-sm text-slate-400">{customer.name} · {lines.length} line{lines.length === 1 ? '' : 's'}</DialogDescription>
                    </div>
                    <div className="text-right">
                        <div className="text-[11px] uppercase tracking-wider text-slate-400">Total due</div>
                        <div className="text-3xl font-bold tabular-nums"><MoneyDisplay amount={total} /></div>
                    </div>
                </div>

                <div className="p-6 space-y-5">
                    <PaymentPanel tender={tender} customer={customer} onEnter={onEnter} />

                    {needsApproval && (
                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-3">
                            <div className="flex items-start gap-2 text-sm text-amber-800">
                                <ShieldCheck className="h-5 w-5 shrink-0" />
                                <span>A discount is above {maxDiscount}%. A supervisor must enter <strong>their own</strong> login to approve it.</span>
                            </div>
                            {approver ? (
                                <p className="text-sm font-medium text-emerald-700">Approved by {approver}.</p>
                            ) : (
                                <div className="flex flex-wrap items-end gap-2">
                                    <input value={supervisor.username} onChange={(e) => setSupervisor((s) => ({ ...s, username: e.target.value }))} placeholder="Supervisor username" aria-label="Supervisor username" className="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                                    <input type="password" value={supervisor.password} onChange={(e) => setSupervisor((s) => ({ ...s, password: e.target.value }))} placeholder="Password" aria-label="Supervisor password" className="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                                    <button type="button" onClick={verifySupervisor} disabled={verifying || !supervisor.username || !supervisor.password}
                                        className="rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:opacity-40">{verifying ? 'Checking…' : 'Approve'}</button>
                                </div>
                            )}
                        </div>
                    )}

                    {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{error}</p>}
                </div>

                <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" onClick={onClose} disabled={processing} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200">Back to sale <kbd className="ml-1 font-mono text-[10px] text-slate-400">Esc</kbd></button>
                    <button type="button" onClick={submit} disabled={processing || !!why} title={why ?? undefined}
                        className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-3 text-base font-bold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                        {processing ? <><Loader2 className="h-5 w-5 animate-spin" /> Posting…</> : <>Complete sale <MoneyDisplay amount={total} /> <kbd className="ml-1 rounded bg-orange-700 px-1.5 font-mono text-[10px]">Enter</kbd></>}
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
