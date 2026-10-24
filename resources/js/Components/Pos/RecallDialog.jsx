import { PlayCircle, Trash2 } from 'lucide-react';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { fmt } from './posMath';

/** Suspended carts on this till (2.1 rule 5: no stock held; re-priced on recall). */
export default function RecallDialog({ open, suspended, onRecall, onDiscard, onClose }) {
    return (
        <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
            <DialogContent className="bg-white sm:max-w-lg">
                <div>
                    <DialogTitle>Suspended sales</DialogTitle>
                    <DialogDescription className="mt-1 text-xs text-slate-500">No stock is held while suspended — prices and availability are re-checked on recall.</DialogDescription>
                </div>
                {suspended.length === 0 ? (
                    <p className="text-sm text-slate-400">Nothing suspended on this till.</p>
                ) : (
                    <ul className="divide-y divide-slate-100 -mx-2">
                        {suspended.map((s, i) => (
                            <li key={s.id} className="flex items-center gap-3 px-2 py-2.5">
                                <span className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{i + 1}</span>
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-medium text-slate-800">{s.customer?.name ?? 'Cash Customer'}{s.label ? ` — ${s.label}` : ''}</div>
                                    <div className="text-xs text-slate-400">
                                        {s.lines.length} line{s.lines.length === 1 ? '' : 's'} · {fmt(s.total ?? 0)} · {new Date(s.at).toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit' })}
                                    </div>
                                </div>
                                <button onClick={() => onDiscard(s)} aria-label="Discard suspended sale" className="rounded p-1.5 text-slate-300 hover:bg-red-50 hover:text-red-600"><Trash2 className="h-4 w-4" /></button>
                                <button autoFocus={i === 0} onClick={() => onRecall(s)} className="inline-flex items-center gap-1 rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700">
                                    <PlayCircle className="h-3.5 w-3.5" /> Recall
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </DialogContent>
        </Dialog>
    );
}
