import { Head, router } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import RelatedLinks from '@/Components/RelatedLinks';

const docType = (t) => (t === 'order' ? 'order' : t === 'quotation' ? 'quote' : t === 'credit_note' ? 'credit_note' : 'invoice');
const statusTone = (s) => (s === 'dispatched' ? 'in_transit' : s === 'delivered' ? 'complete' : 'draft');

export default function DeliveryNoteShow({ note }) {
    const source = note.document ? { type: docType(note.document.document_type), id: note.document.id, label: note.document.document_number } : null;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={note.delivery_number}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Delivery Notes', href: route('sales.delivery-notes.index') },
                { label: note.delivery_number },
            ]}
        >
            <Head title={note.delivery_number} />

            <div className="max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold text-slate-900">{note.delivery_number}</h1>
                            <StatusBadge status={statusTone(note.status)} label={note.status} />
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            <EntityLink type="customer" id={note.customer?.id} label={note.customer?.name} className="font-medium" />
                            {note.customer?.customer_number && <span className="font-mono text-slate-400"> {note.customer.customer_number}</span>}
                            {' · '}{note.delivery_date}
                            {note.branch && <> · <EntityLink type="branch" id={note.branch.id} label={note.branch.name} muted /></>}
                        </p>
                    </div>
                    {note.status === 'dispatched' && (
                        <button
                            type="button"
                            onClick={() => router.post(route('sales.delivery-notes.transition', note.id), { status: 'delivered' }, { preserveScroll: true })}
                            className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                        >
                            <Truck className="w-4 h-4" />
                            Mark delivered
                        </button>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400 mb-2">Delivery</div>
                        <dl className="space-y-1 text-slate-600">
                            <div className="flex justify-between gap-4"><dt className="text-slate-400">Source document</dt><dd>{source ? <EntityLink type={source.type} id={source.id} label={source.label} mono /> : '—'}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-slate-400">Delivery date</dt><dd className="tabular-nums">{note.delivery_date}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-slate-400">Address</dt><dd className="text-right">{note.address ?? '—'}</dd></div>
                        </dl>
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-4">
                        <div className="text-xs uppercase tracking-wider text-slate-400 mb-2">Transport</div>
                        <dl className="space-y-1 text-slate-600">
                            <div className="flex justify-between gap-4"><dt className="text-slate-400">Driver</dt><dd>{note.driver ?? '—'}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-slate-400">Vehicle reg</dt><dd className="font-mono">{note.vehicle_reg ?? '—'}</dd></div>
                            <div className="flex justify-between gap-4"><dt className="text-slate-400">Raised</dt><dd className="tabular-nums">{note.created_at}</dd></div>
                        </dl>
                    </div>
                </div>

                <RelatedLinks
                    trace={[
                        ...(source ? [source] : []),
                        { type: 'delivery_note', current: true, label: note.delivery_number },
                    ]}
                    groups={[
                        { title: 'Customer', items: note.customer ? [{ type: 'customer', id: note.customer.id, label: note.customer.name }] : [] },
                        { title: 'Branch', items: note.branch ? [{ type: 'branch', id: note.branch.id, label: note.branch.name }] : [] },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}
