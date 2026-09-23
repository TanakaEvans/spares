// Money renders ONE way everywhere: right-aligned tabular digits, 2dp,
// thousands separators, negatives in red parentheses (component-standards §6).
export default function MoneyDisplay({ amount, currency = '$', decimals = 2, className = '' }) {
    const value = Number(amount ?? 0);
    const negative = value < 0;
    const formatted = Math.abs(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    return (
        <span className={`tabular-nums ${negative ? 'text-red-600' : ''} ${className}`}>
            {negative ? `(${currency}${formatted})` : `${currency}${formatted}`}
        </span>
    );
}
