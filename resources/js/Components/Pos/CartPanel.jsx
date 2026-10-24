import { useEffect, useRef } from 'react';
import { Minus, Plus, Trash2, MapPin, ShoppingCart, PlayCircle } from 'lucide-react';
import { fmt, inclPrice } from './posMath';

function LineRow({ line, calc, index, selected, onSelect, onQty, onDiscount, onRemove, maxDiscount, allowNegative, vatRate }) {
    const ref = useRef(null);
    const short = !allowNegative && line.qty > Number(line.available ?? line.on_hand ?? 0);
    const overDiscount = (line.discount_pct || 0) > maxDiscount;

    useEffect(() => { if (selected) ref.current?.scrollIntoView({ block: 'nearest' }); }, [selected]);

    return (
        <li ref={ref} onClick={() => onSelect(index)}
            className={`px-3 py-2.5 border-l-4 ${selected ? 'border-orange-500 bg-orange-50/60' : 'border-transparent'} ${short || !line.priced ? 'bg-red-50/70' : ''}`}>
            <div className="flex items-start gap-2">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <a href={route('inventory.parts.show', line.part_id)} target="_blank" rel="noreferrer"
                            className="font-mono text-sm font-bold text-slate-900 hover:text-orange-600">{line.part_number}</a>
                        {line.bin && <span className="inline-flex items-center gap-0.5 text-[11px] text-slate-500"><MapPin className="h-3 w-3" />{line.bin}</span>}
                    </div>
                    <div className="text-xs text-slate-500 line-clamp-1">{line.description}</div>
                    {!line.priced && <div className="text-xs font-medium text-red-600">No selling price — remove this line.</div>}
                    {short && <div className="text-xs font-medium text-red-600">Only {fmt(line.available ?? line.on_hand).replace('.00', '')} available here.</div>}
                    {line.priceChanged && <div className="text-xs font-medium text-amber-600">Price updated for this customer.</div>}
                </div>
                <div className="text-right">
                    <div className="text-sm font-bold tabular-nums text-slate-900">{fmt(calc.incl)}</div>
                    {calc.discount > 0 && <div className="text-[11px] tabular-nums text-emerald-700">−{fmt(inclPrice(calc.discount, vatRate))}</div>}
                </div>
            </div>
            <div className="mt-2 flex items-center gap-2">
                <div className="inline-flex items-center rounded-lg border border-slate-300 bg-white">
                    <button type="button" aria-label={`One less ${line.part_number}`} onClick={(e) => { e.stopPropagation(); onQty(index, Math.max(0.01, line.qty - 1)); }}
                        disabled={line.qty <= 1} className="px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30"><Minus className="h-3.5 w-3.5" /></button>
                    <input type="number" min="0.01" step="1" value={line.qty} aria-label={`Qty ${line.part_number}`}
                        onClick={(e) => e.stopPropagation()}
                        onChange={(e) => onQty(index, Number(e.target.value) || 0)}
                        className="w-14 border-0 px-1 py-1 text-center text-sm font-semibold tabular-nums focus:ring-0" />
                    <button type="button" aria-label={`One more ${line.part_number}`} onClick={(e) => { e.stopPropagation(); onQty(index, line.qty + 1); }}
                        className="px-2 py-1 text-slate-500 hover:text-slate-900"><Plus className="h-3.5 w-3.5" /></button>
                </div>
                <span className="text-xs tabular-nums text-slate-400">× {fmt(inclPrice(line.unit_price, vatRate))}</span>
                <label className="ml-auto flex items-center gap-1 text-xs text-slate-400" onClick={(e) => e.stopPropagation()}>
                    Disc
                    <input type="number" min="0" max="100" step="0.5" value={line.discount_pct} aria-label={`Discount % ${line.part_number}`}
                        title={overDiscount ? `Above ${maxDiscount}% — a supervisor will approve at payment` : `Up to ${maxDiscount}% without approval`}
                        onChange={(e) => onDiscount(index, Math.min(100, Math.max(0, Number(e.target.value) || 0)))}
                        className={`w-14 rounded border px-1.5 py-0.5 text-right text-xs tabular-nums ${overDiscount ? 'border-amber-400 bg-amber-50' : 'border-slate-300'}`} />%
                </label>
                <button type="button" onClick={(e) => { e.stopPropagation(); onRemove(index); }} aria-label={`Remove ${line.part_number}`}
                    className="rounded p-1 text-slate-300 hover:bg-red-50 hover:text-red-600"><Trash2 className="h-4 w-4" /></button>
            </div>
        </li>
    );
}

export default function CartPanel({ lines, calc, selected, onSelect, onQty, onDiscount, onRemove, maxDiscount, allowNegative, vatRate, suspendedCount, onRecall }) {
    if (lines.length === 0) {
        return (
            <div className="flex h-full flex-col items-center justify-center p-6 text-center text-slate-400">
                <ShoppingCart className="mb-3 h-10 w-10" />
                <p className="text-sm font-medium text-slate-600">No items yet</p>
                <p className="mt-1 text-xs">Scan a barcode, type a part number, or pick from the left.</p>
                {suspendedCount > 0 && (
                    <button onClick={onRecall} className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 hover:text-orange-700">
                        <PlayCircle className="h-4 w-4" /> Recall a suspended sale ({suspendedCount}) <kbd className="ml-1 rounded border border-orange-200 px-1 font-mono text-[10px]">F9</kbd>
                    </button>
                )}
            </div>
        );
    }

    return (
        <ul className="h-full overflow-y-auto divide-y divide-slate-100" aria-label="Sale lines">
            {lines.map((l, i) => (
                <LineRow key={l.part_id} line={l} calc={calc[i]} index={i} selected={selected === i} onSelect={onSelect}
                    onQty={onQty} onDiscount={onDiscount} onRemove={onRemove} maxDiscount={maxDiscount} allowNegative={allowNegative} vatRate={vatRate} />
            ))}
        </ul>
    );
}
