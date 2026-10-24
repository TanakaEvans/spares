import { Head } from '@inertiajs/react';
import { PackageCheck } from 'lucide-react';
import DocumentBuilder from '@/Components/Sales/DocumentBuilder';

export default function OrderCreate({ walkIn, vatRate }) {
    return (
        <>
            <Head title="New Sales Order" />
            <DocumentBuilder
                walkIn={walkIn}
                vatRate={vatRate}
                title="New Sales Order"
                breadcrumbs={[
                    { label: 'Sales & POS', href: route('modules.show', 'sales') },
                    { label: 'Sales Orders', href: route('sales.orders.index') },
                    { label: 'New Order' },
                ]}
                submitTo={route('sales.orders.store')}
                draftKey="sales.order.draft"
                submitLabel="Create Order"
                emptyHint='Capture a phoned-in or counter order — the lines are confirmed and stock is reserved on save.'
                sideNote={
                    <div className="flex items-center gap-2 text-xs text-slate-500">
                        <PackageCheck className="h-3.5 w-3.5" />
                        Saving <strong className="text-slate-700">confirms</strong> the order and reserves every line&apos;s stock at this branch.
                    </div>
                }
            />
        </>
    );
}
