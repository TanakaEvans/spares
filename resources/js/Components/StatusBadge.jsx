// THE single status → colour map (component-standards.md §1, §7).
// Add new statuses HERE, never inline on a screen.
const STATUS_STYLES = {
    // Positive
    active: 'bg-green-100 text-green-800',
    complete: 'bg-green-100 text-green-800',
    completed: 'bg-green-100 text-green-800',
    posted: 'bg-green-100 text-green-800',
    paid: 'bg-green-100 text-green-800',
    approved: 'bg-green-100 text-green-800',
    received: 'bg-green-100 text-green-800',
    open: 'bg-green-100 text-green-800',
    // Neutral / draft
    draft: 'bg-slate-100 text-slate-600',
    inactive: 'bg-slate-100 text-slate-500',
    pending: 'bg-slate-100 text-slate-600',
    never: 'bg-slate-100 text-slate-500',
    // In progress
    in_progress: 'bg-blue-100 text-blue-800',
    allocated: 'bg-blue-100 text-blue-800',
    picking: 'bg-blue-100 text-blue-800',
    submitted: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-blue-100 text-blue-800',
    dispatched: 'bg-blue-100 text-blue-800',
    in_transit: 'bg-blue-100 text-blue-800',
    partial: 'bg-blue-100 text-blue-800',
    counting: 'bg-blue-100 text-blue-800',
    review: 'bg-yellow-100 text-yellow-800',
    converted: 'bg-indigo-100 text-indigo-800',
    // Warning / attention
    awaiting_parts: 'bg-yellow-100 text-yellow-800',
    awaiting_customer: 'bg-yellow-100 text-yellow-800',
    on_hold: 'bg-yellow-100 text-yellow-800',
    variance_review: 'bg-yellow-100 text-yellow-800',
    quality_check: 'bg-yellow-100 text-yellow-800',
    disputed: 'bg-yellow-100 text-yellow-800',
    expired: 'bg-yellow-100 text-yellow-800',
    closed: 'bg-slate-100 text-slate-600',
    locked: 'bg-slate-200 text-slate-700',
    // Danger
    overdue: 'bg-red-100 text-red-800',
    rejected: 'bg-red-100 text-red-800',
    defaulted: 'bg-red-100 text-red-800',
    blacklisted: 'bg-red-100 text-red-800',
    voided: 'bg-red-100 text-red-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-red-50 text-red-500 line-through',
    // Special
    special_order: 'bg-purple-100 text-purple-800',
    warranty: 'bg-purple-100 text-purple-800',
    invoiced: 'bg-indigo-100 text-indigo-800',
};

export default function StatusBadge({ status, label, className = '' }) {
    const key = String(status ?? '').toLowerCase().replace(/[\s-]+/g, '_');
    const style = STATUS_STYLES[key] ?? 'bg-slate-100 text-slate-600';
    const text = label ?? String(status ?? '').replace(/_/g, ' ');

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${style} ${className}`}>
            {text}
        </span>
    );
}
