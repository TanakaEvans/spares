import { useEffect, useRef } from 'react';
import { CheckCircle2, Printer, FileText, ArrowRight } from 'lucide-react';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import MoneyDisplay from '@/Components/MoneyDisplay';

const LABEL = { cash: 'Cash', card: 'Card', eft: 'EFT', account: 'Account' };

/** The moment after posting: change due (huge), receipt in one key, then straight on to the next customer. */
export default function SaleCompleteDialog({ sale, onNext }) {
    const printRef = useRef(null);
    useEffect(() => { if (sale) setTimeout(() => printRef.current?.focus(), 30); }, [sale?.id]);

    if (!sale) return null;
    const printReceipt = () => window.open(route('sales.invoices.receipt', sale.id), '_blank', 'noopener');

    return (
        <Dialog open={!!sale} onOpenChange={(o) => !o && onNext()}>
            <DialogContent showCloseButton={false} className="bg-white sm:max-w-md p-0 gap-0 overflow-hidden"
                onKeyDown={(e) => {
                    if (e.key.toLowerCase() === 'n') { e.preventDefault(); onNext(); }
                    if (e.key.toLowerCase() === 'a') { e.preventDefault(); window.open(route('sales.invoices.print', sale.id), '_blank', 'noopener'); }
                }}>
                <div className="bg-emerald-600 px-6 py-5 text-center text-white">
                    <CheckCircle2 className="mx-auto h-10 w-10" />
                    <DialogTitle className="mt-2 text-xl font-bold">Sale complete</DialogTitle>
                    <DialogDescription className="text-sm text-emerald-100">
                        <a href={route('sales.invoices.show', sale.id)} target="_blank" rel="noreferrer" className="font-mono font-semibold underline decoration-emerald-300 hover:text-white">{sale.number}</a>
                        {' · '}{sale.customer} · {sale.lines} line{sale.lines === 1 ? '' : 's'}
                    </DialogDescription>
                </div>

                <div className="p-6 space-y-4">
                    {sale.change > 0 && (
                        <div className="rounded-xl bg-amber-50 px-5 py-4 text-center ring-1 ring-amber-200">
                            <div className="text-xs font-semibold uppercase tracking-wider text-amber-700">Give change</div>
                            <div className="text-5xl font-extrabold tabular-nums text-slate-900"><MoneyDisplay amount={sale.change} /></div>
                        </div>
                    )}
                    <div className="space-y-1 text-sm">
                        {sale.methods.map((m, i) => (
                            <div key={i} className="flex justify-between text-slate-500"><span>{LABEL[m.method] ?? m.method}</span><MoneyDisplay amount={m.amount} /></div>
                        ))}
                        <div className="flex justify-between border-t border-slate-100 pt-1 text-base font-bold text-slate-900"><span>Total</span><MoneyDisplay amount={sale.total} /></div>
                    </div>

                    <div className="grid grid-cols-2 gap-2">
                        <button ref={printRef} type="button" onClick={printReceipt}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white hover:bg-orange-700">
                            <Printer className="h-4 w-4" /> Receipt <kbd className="rounded bg-orange-700 px-1 font-mono text-[10px]">Enter</kbd>
                        </button>
                        <button type="button" onClick={() => window.open(route('sales.invoices.print', sale.id), '_blank', 'noopener')}
                            className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <FileText className="h-4 w-4" /> A4 invoice <kbd className="font-mono text-[10px] text-slate-400">A</kbd>
                        </button>
                    </div>
                    <button type="button" onClick={onNext}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-900 px-4 py-3 text-sm font-bold text-slate-900 hover:bg-slate-900 hover:text-white">
                        Next customer <ArrowRight className="h-4 w-4" /> <kbd className="font-mono text-[10px] opacity-60">N / Esc</kbd>
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
