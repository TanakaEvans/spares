import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import MoneyDisplay from '@/Components/MoneyDisplay';
import PaymentPanel from '@/Components/Pos/PaymentPanel';
import { useTender, accountBlockReason } from '@/Components/Pos/paymentCore';

/**
 * Take payment for a fixed amount against any POST endpoint that accepts
 * `payments[]` in the POS shape (e.g. order fulfilment). One-key tenders,
 * note buttons, change on cash, split tender — the same UX as the till.
 */
export default function PaymentDialog({ open, onClose, title, subtitle, total, customer, submitTo, extraData = {}, submitLabel = 'Post payment', initialMode }) {
    const tender = useTender(total);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!open) return;
        tender.reset(initialMode ?? 'cash');
        setError(null); setProcessing(false);
    }, [open, initialMode]);

    useEffect(() => { if (open) setTimeout(() => tender.amountRef.current?.focus(), 30); }, [open, tender.mode]);

    const why = tender.blocker(customer);

    function submit() {
        if (why) { setError(why); return; }
        setProcessing(true); setError(null);
        router.post(submitTo, { payments: tender.payments(), ...extraData }, {
            preserveScroll: true,
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
                        <DialogTitle className="text-lg font-semibold">{title}</DialogTitle>
                        <DialogDescription className="text-sm text-slate-400">{subtitle}</DialogDescription>
                    </div>
                    <div className="text-right">
                        <div className="text-[11px] uppercase tracking-wider text-slate-400">Total due</div>
                        <div className="text-3xl font-bold tabular-nums"><MoneyDisplay amount={total} /></div>
                    </div>
                </div>

                <div className="p-6 space-y-5">
                    <PaymentPanel tender={tender} customer={customer} onEnter={onEnter} />
                    {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{error}</p>}
                </div>

                <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" onClick={onClose} disabled={processing} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200">Back <kbd className="ml-1 font-mono text-[10px] text-slate-400">Esc</kbd></button>
                    <button type="button" onClick={submit} disabled={processing || !!why} title={why ?? undefined}
                        className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-3 text-base font-bold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                        {processing ? <><Loader2 className="h-5 w-5 animate-spin" /> Posting…</> : <>{submitLabel} <MoneyDisplay amount={total} /> <kbd className="ml-1 rounded bg-orange-700 px-1.5 font-mono text-[10px]">Enter</kbd></>}
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
