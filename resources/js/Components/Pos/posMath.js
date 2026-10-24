// Till maths — mirrors PricingService::computeLine so the screen total is the
// total the server will post (prices are stored VAT-exclusive).
export const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

export const fmt = (n) => Number(n || 0).toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const qtyFmt = (n) => (Number.isInteger(Number(n)) ? String(Number(n)) : Number(n).toFixed(2));

export const inclPrice = (excl, vatRate) => round2(excl * (1 + vatRate / 100));

export function lineMaths(line, vatRate) {
    const gross = line.qty * line.unit_price;
    const discount = round2(gross * (line.discount_pct || 0) / 100);
    const excl = round2(gross - discount);
    const vat = round2(excl * vatRate / 100);
    return { gross: round2(gross), discount, excl, vat, incl: round2(excl + vat) };
}

export function cartTotals(lines, vatRate) {
    const calc = lines.map((l) => lineMaths(l, vatRate));
    const sum = (k) => round2(calc.reduce((n, c) => n + c[k], 0));
    const subtotal = sum('excl');
    const vat = sum('vat');
    return {
        calc,
        subtotal,
        discount: sum('discount'),
        vat,
        total: round2(subtotal + vat),
        items: round2(lines.reduce((n, l) => n + Number(l.qty || 0), 0)),
    };
}

/** "Exact" plus the next note amounts a customer is likely to hand over. */
export function cashSuggestions(total) {
    const t = round2(total);
    const out = [t];
    for (const step of [10, 20, 50, 100, 200, 500]) {
        const v = Math.ceil(t / step) * step;
        if (v > t && !out.includes(v)) out.push(v);
    }
    return out.slice(0, 5);
}

/** "3*AF213" → sell 3 of AF213. */
export function parseQuery(raw) {
    const m = raw.match(/^\s*(\d+(?:\.\d+)?)\s*\*\s*(.+)$/);
    return m ? { qty: Math.max(Number(m[1]), 0.01), term: m[2].trim() } : { qty: 1, term: raw.trim() };
}

/** Stock badge + whether the till may sell it at all (inventory.allow_negative_stock). */
export function stockState(part, allowNegative) {
    const avail = Number(part.available ?? part.on_hand ?? 0);
    if (avail <= 0) {
        return allowNegative
            ? { tone: 'amber', label: 'None in stock', blocked: false }
            : { tone: 'red', label: 'Out of stock', blocked: true, reason: 'Out of stock at this branch — negative stock is not allowed.' };
    }
    if (part.reorder_point && avail <= part.reorder_point) return { tone: 'amber', label: `Low · ${fmt(avail).replace('.00', '')}`, blocked: false };
    return { tone: 'green', label: `${fmt(avail).replace('.00', '')} in stock`, blocked: false };
}

export const TONES = {
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    amber: 'bg-amber-50 text-amber-700 ring-amber-200',
    red: 'bg-red-50 text-red-700 ring-red-200',
    slate: 'bg-slate-100 text-slate-600 ring-slate-200',
};

/** Debounce-friendly JSON GET against a Ziggy route. */
export async function getJson(routeName, params) {
    const res = await fetch(route(routeName) + '?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

/* Per-till browser storage: suspended carts + the live cart (crash recovery, 2.1 edge cases). */
const read = (key, fallback) => { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; } };
const write = (key, value) => { try { value === null ? localStorage.removeItem(key) : localStorage.setItem(key, JSON.stringify(value)); } catch { /* storage unavailable */ } };

export const tillStore = {
    suspended: (branchId) => read(`pos.suspended.${branchId}`, []),
    setSuspended: (branchId, list) => write(`pos.suspended.${branchId}`, list),
    cart: (branchId) => read(`pos.cart.${branchId}`, null),
    setCart: (branchId, cart) => write(`pos.cart.${branchId}`, cart),
    focus: () => read('pos.focus', false),
    setFocus: (on) => write('pos.focus', on),
};
