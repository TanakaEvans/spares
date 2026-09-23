import { Printer } from 'lucide-react';

// The one print/download affordance (tasks 0.8): opens the PDF stream
// in a new tab — printing never blocks the page (ui-rules §8).
export default function PrintButton({ href, label = 'Print', className = '' }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener"
            className={`inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors ${className}`}
        >
            <Printer className="w-4 h-4" />
            {label}
        </a>
    );
}
