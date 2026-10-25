import { Head } from '@inertiajs/react';
import { UsersRound, Star } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function ContactsIndex({ contacts, filters }) {
    const filter = useListFilter('suppliers.contacts.index', filters);
    return (
        <ModuleLayout navConfig={navConfig} title="Supplier Contacts"
            breadcrumbs={[{ label: 'Suppliers', href: route('modules.show', 'suppliers') }, { label: 'Supplier Contacts' }]}>
            <Head title="Supplier Contacts" />
            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Name, email or supplier…"
                    searchClass="w-72"
                />
                <DataTable
                    columns={[
                        { key: 'name', label: 'Contact', sortable: 'name', render: (c) => <span className="font-semibold text-slate-800">{c.name}{c.is_primary && <Star className="inline w-3.5 h-3.5 ml-1.5 fill-amber-400 text-amber-400" />}</span> },
                        { key: 'position', label: 'Position', render: (c) => <span className="text-slate-500">{c.position ?? '—'}</span> },
                        { key: 'supplier', label: 'Supplier', render: (c) => <EntityLink type="supplier" id={c.supplier_id} label={c.supplier} /> },
                        { key: 'phone', label: 'Phone', render: (c) => <span className="text-slate-500">{c.phone ?? '—'}</span> },
                        { key: 'email', label: 'Email', sortable: 'email', render: (c) => (c.email ? <a href={`mailto:${c.email}`} onClick={(e) => e.stopPropagation()} className="text-slate-600 hover:text-orange-600">{c.email}</a> : '—') },
                    ]}
                    rows={contacts.data}
                    pagination={contacts}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(c) => route('suppliers.show', c.supplier_id)}
                    emptyTitle="No contacts"
                    emptyMessage="Add contacts on each supplier's profile."
                    emptyIcon={UsersRound}
                />
            </div>
        </ModuleLayout>
    );
}
