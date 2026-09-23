import { Head, Link, router } from '@inertiajs/react';
import { UsersRound, Star } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';

export default function ContactsIndex({ contacts, filters }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Supplier Contacts"
            breadcrumbs={[{ label: 'Suppliers', href: route('modules.show', 'suppliers') }, { label: 'Supplier Contacts' }]}>
            <Head title="Supplier Contacts" />
            <div className="space-y-4">
                <SearchInput id="sc-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('suppliers.contacts.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Name, email or supplier…" className="w-72" />
                <DataTable
                    columns={[
                        { key: 'name', label: 'Contact', render: (c) => <span className="font-semibold text-slate-800">{c.name}{c.is_primary && <Star className="inline w-3.5 h-3.5 ml-1.5 fill-amber-400 text-amber-400" />}</span> },
                        { key: 'position', label: 'Position', render: (c) => <span className="text-slate-500">{c.position ?? '—'}</span> },
                        { key: 'supplier', label: 'Supplier', render: (c) => <Link href={route('suppliers.show', c.supplier_id)} onClick={(e) => e.stopPropagation()} className="text-slate-700 hover:text-orange-600">{c.supplier}</Link> },
                        { key: 'phone', label: 'Phone', render: (c) => <span className="text-slate-500">{c.phone ?? '—'}</span> },
                        { key: 'email', label: 'Email', render: (c) => (c.email ? <a href={`mailto:${c.email}`} onClick={(e) => e.stopPropagation()} className="text-slate-600 hover:text-orange-600">{c.email}</a> : '—') },
                    ]}
                    rows={contacts.data}
                    pagination={contacts}
                    rowHref={(c) => route('suppliers.show', c.supplier_id)}
                    emptyTitle="No contacts"
                    emptyMessage="Add contacts on each supplier's profile."
                    emptyIcon={UsersRound}
                />
            </div>
        </ModuleLayout>
    );
}
