import { usePage } from '@inertiajs/react';

// Money renders ONE way everywhere: right-aligned tabular digits, 2dp,
// thousands separators, negatives in red parentheses (component-standards §6).
// The symbol comes from the base currency shared by the server (10.12) —
// never hard-coded — unless a caller passes an explicit `currency`.
export default function MoneyDisplay({ amount, currency, decimals, className = '' }) {
    const { currency: base } = usePage().props;
    const symbol = currency ?? base?.symbol ?? '';
    const dp = decimals ?? base?.decimals ?? 2;
    const value = Number(amount ?? 0);
    const negative = value < 0;
    const formatted = Math.abs(value).toLocaleString('en-ZA', {
        minimumFractionDigits: dp,
        maximumFractionDigits: dp,
    });

    return (
        <span className={`tabular-nums ${negative ? 'text-red-600' : ''} ${className}`}>
            {negative ? `(${symbol}${formatted})` : `${symbol}${formatted}`}
        </span>
    );
}
