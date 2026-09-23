import MoneyDisplay from '@/Components/MoneyDisplay';

// Lightweight inline-SVG charts — no external dependency, theme-consistent.
const money = (n) => Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });

export function StatTile({ label, value, sub, tone = 'slate', money: isMoney = false }) {
    const tones = {
        slate: 'text-slate-900', emerald: 'text-emerald-600', orange: 'text-orange-600',
        red: 'text-red-600', blue: 'text-blue-600',
    };
    return (
        <div className="rounded-xl border border-slate-100 bg-white p-4">
            <div className="text-xs uppercase tracking-wider text-slate-400">{label}</div>
            <div className={`mt-1 text-2xl font-bold tabular-nums ${tones[tone] ?? tones.slate}`}>
                {isMoney ? <MoneyDisplay amount={value} /> : value}
            </div>
            {sub && <div className="mt-0.5 text-xs text-slate-400">{sub}</div>}
        </div>
    );
}

/** Vertical bar chart. data: [{label, value}]. */
export function Bars({ data, height = 180, color = '#ea580c', format = money }) {
    const max = Math.max(1, ...data.map((d) => Math.abs(d.value)));
    const w = 100 / Math.max(1, data.length);
    return (
        <svg viewBox={`0 0 100 ${height / 3}`} className="w-full" style={{ height }} preserveAspectRatio="none" role="img">
            {data.map((d, i) => {
                const h = (Math.abs(d.value) / max) * (height / 3 - 12);
                return (
                    <g key={i}>
                        <rect x={i * w + w * 0.15} y={height / 3 - 8 - h} width={w * 0.7} height={Math.max(0.5, h)} rx="0.6" fill={color} opacity={0.85} />
                    </g>
                );
            })}
        </svg>
    );
}

/** Bars with axis labels below (for small category counts). */
export function LabelledBars({ data, color = '#ea580c' }) {
    const max = Math.max(1, ...data.map((d) => Math.abs(d.value)));
    return (
        <div className="flex items-end gap-2" style={{ height: 160 }}>
            {data.map((d, i) => (
                <div key={i} className="flex-1 flex flex-col items-center justify-end gap-1 h-full">
                    <span className="text-xs tabular-nums text-slate-500">{money(d.value)}</span>
                    <div className="w-full rounded-t" style={{ height: `${(Math.abs(d.value) / max) * 100}%`, background: color, opacity: 0.85, minHeight: 2 }} />
                    <span className="text-[10px] text-slate-400 text-center truncate w-full">{d.label}</span>
                </div>
            ))}
        </div>
    );
}

/** Two-series line chart (e.g. this year vs last year). */
export function LineChart({ data, series, height = 200 }) {
    const W = 320;
    const H = 100;
    const pad = 6;
    const all = data.flatMap((d) => series.map((s) => d[s.key] ?? 0));
    const max = Math.max(1, ...all);
    const stepX = (W - pad * 2) / Math.max(1, data.length - 1);
    const y = (v) => H - pad - (v / max) * (H - pad * 2);

    return (
        <div>
            <svg viewBox={`0 0 ${W} ${H}`} className="w-full" style={{ height }} role="img">
                {[0.25, 0.5, 0.75].map((f) => (
                    <line key={f} x1={pad} x2={W - pad} y1={pad + f * (H - pad * 2)} y2={pad + f * (H - pad * 2)} stroke="#f1f5f9" strokeWidth="0.5" />
                ))}
                {series.map((s) => (
                    <polyline key={s.key} fill="none" stroke={s.color} strokeWidth="1.5" strokeLinejoin="round"
                        points={data.map((d, i) => `${pad + i * stepX},${y(d[s.key] ?? 0)}`).join(' ')} />
                ))}
            </svg>
            <div className="mt-2 flex items-center justify-between text-[10px] text-slate-400">
                {data.map((d, i) => <span key={i}>{d.month ?? d.label}</span>)}
            </div>
            <div className="mt-1 flex gap-4 text-xs">
                {series.map((s) => (
                    <span key={s.key} className="inline-flex items-center gap-1.5 text-slate-500">
                        <span className="inline-block w-3 h-0.5 rounded" style={{ background: s.color }} /> {s.label}
                    </span>
                ))}
            </div>
        </div>
    );
}

/** Horizontal ranking bars (top parts, top customers). */
export function HBarList({ data, labelKey, valueKey, isMoney = true }) {
    const max = Math.max(1, ...data.map((d) => Math.abs(d[valueKey])));
    return (
        <div className="space-y-1.5">
            {data.map((d, i) => (
                <div key={i} className="flex items-center gap-2 text-sm">
                    <span className="w-40 truncate text-slate-600">{d[labelKey]}</span>
                    <div className="flex-1 bg-slate-100 rounded h-3 overflow-hidden">
                        <div className="h-full bg-orange-500/80 rounded" style={{ width: `${(Math.abs(d[valueKey]) / max) * 100}%` }} />
                    </div>
                    <span className="w-20 text-right tabular-nums text-slate-700">
                        {isMoney ? <MoneyDisplay amount={d[valueKey]} /> : d[valueKey]}
                    </span>
                </div>
            ))}
            {data.length === 0 && <p className="text-sm text-slate-400 py-4 text-center">No data for this range.</p>}
        </div>
    );
}
