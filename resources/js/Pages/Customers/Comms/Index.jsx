import { Head, useForm } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function CommsIndex({ notes, customers, filters = {} }) {
    const { data, setData, post, processing, reset } = useForm({ customer_id: '', channel: 'call', subject: '', body: '' });
    const filter = useListFilter('customers.comms.index', filters);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Communication Log" breadcrumbs={[{ label: 'Customers', href: route('modules.show', 'customers') }, { label: 'Communication Log' }]}>
            <Head title="Communication Log" />
            <ListToolbar
                filters={filters}
                onFilter={filter}
                searchPlaceholder="Subject, details or customer…"
                searchClass="w-64"
                dateRange
            >
                <select aria-label="Channel" value={filters.channel ?? ''} onChange={(e) => filter({ channel: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All channels</option>
                    {['call', 'email', 'visit', 'note'].map((c) => <option key={c} value={c}>{c}</option>)}
                </select>
            </ListToolbar>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-5xl mt-4">
                <div className="lg:col-span-2">
                    <DataTable
                        columns={[
                            { key: 'customer', label: 'Customer', render: (n) => <EntityLink type="customer" id={n.customer_id} label={n.customer} className="text-sm font-semibold" /> },
                            { key: 'subject', label: 'Communication', render: (n) => (
                                <div><div className="text-sm text-slate-700">{n.subject}</div>{n.body && <div className="text-xs text-slate-500 mt-0.5 line-clamp-2">{n.body}</div>}</div>
                            ) },
                            { key: 'channel', label: 'Channel', sortable: 'channel', render: (n) => <span className="text-xs text-slate-400 capitalize">{n.channel}</span> },
                            { key: 'at', label: 'When', sortable: 'created_at', render: (n) => (
                                <div className="text-xs text-slate-400">{n.at}{n.user && <div className="text-[11px]">by <EntityLink type="user" id={n.user_id} label={n.user} muted /></div>}</div>
                            ) },
                        ]}
                        rows={notes.data}
                        pagination={notes}
                        sorting={{ filters, onFilter: filter }}
                        emptyTitle="No communications logged"
                        emptyIcon={MessageSquare}
                    />
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('customers.comms.store'), { onSuccess: () => reset('subject', 'body') }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">Log communication</h2>
                    <select value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} className={input}><option value="">Customer…</option>{customers.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
                    <select value={data.channel} onChange={(e) => setData('channel', e.target.value)} className={input}><option value="call">Call</option><option value="email">Email</option><option value="visit">Visit</option><option value="note">Note</option></select>
                    <input value={data.subject} onChange={(e) => setData('subject', e.target.value)} placeholder="Subject" className={input} />
                    <textarea value={data.body} onChange={(e) => setData('body', e.target.value)} rows={3} placeholder="Details…" className={input} />
                    <button type="submit" disabled={processing || !data.customer_id || !data.subject} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Log it</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
