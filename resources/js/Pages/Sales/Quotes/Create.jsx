import { Head } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import DocumentBuilder from '@/Components/Sales/DocumentBuilder';

export default function QuoteCreate({ walkIn, vatRate, expiryDays }) {
    const validUntil = new Date(Date.now() + expiryDays * 86400000).toISOString().slice(0, 10);

    return (
        <>
            <Head title="New Quotation" />
            <DocumentBuilder
                walkIn={walkIn}
                vatRate={vatRate}
                title="New Quotation"
                breadcrumbs={[
                    { label: 'Sales & POS', href: route('modules.show', 'sales') },
                    { label: 'Quotations', href: route('sales.quotes.index') },
                    { label: 'New Quote' },
                ]}
                submitTo={route('sales.quotes.store')}
                draftKey="sales.quote.draft"
                submitLabel="Save Quotation"
                emptyHint='Search above to add parts — try the vehicle + part type ("corolla water pump") or 3*CODE for multiples.'
                sideNote={
                    <div className="flex items-center gap-2 text-xs text-slate-500">
                        <CalendarClock className="h-3.5 w-3.5" />
                        Valid {expiryDays} days — quoted prices hold until <strong className="text-slate-700">{validUntil}</strong>
                    </div>
                }
            />
        </>
    );
}
