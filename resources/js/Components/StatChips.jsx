/**
 * KPI strip for list screens: a row of compact stat chips above the table.
 * items: [{ label, value, hint?, tone?: 'slate'|'amber'|'emerald'|'red', icon? }]
 */
const TONES = {
    slate: 'border-slate-200 bg-white text-slate-700',
    amber: 'border-amber-200 bg-amber-50 text-amber-800',
    emerald: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    red: 'border-red-200 bg-red-50 text-red-700',
    orange: 'border-orange-200 bg-orange-50 text-orange-800',
};

export default function StatChips({ items }) {
    return (
        <div className="flex flex-wrap gap-3">
            {items.map((item, i) => (
                <div key={i} className={`flex items-center gap-3 rounded-xl border px-4 py-2.5 ${TONES[item.tone ?? 'slate']}`}>
                    {item.icon && <item.icon className="h-4 w-4 opacity-60" />}
                    <div>
                        <div className="text-[11px] font-medium uppercase tracking-wider opacity-70">{item.label}</div>
                        <div className="text-base font-bold tabular-nums leading-tight">{item.value}</div>
                    </div>
                    {item.hint && <div className="text-xs opacity-70 max-w-[10rem]">{item.hint}</div>}
                </div>
            ))}
        </div>
    );
}
