import { Head, Link } from '@inertiajs/react';
import { Plus, Users, Upload } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function CustomersIndex({ customers, filters }) {
    const filter = useListFilter('customers.index', filters);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Customers"
            breadcrumbs={[
                { label: 'Customers', href: route('modules.show', 'customers') },
                { label: 'Customer Profiles' },
            ]}
        >
            <Head title="Customers" />

            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Name, number or phone…"
                    searchClass="w-72"
                    exportRoute="customers.export"
                >
                    <select
                        aria-label="Filter by type"
                        value={filters.type ?? ''}
                        onChange={(e) => filter({ type: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All types</option>
                        {['cash', 'individual', 'business', 'fleet', 'dealer'].map((t) => (
                            <option key={t} value={t}>{t}</option>
                        ))}
                    </select>
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            checked={!!filters.on_hold}
                            onChange={(e) => filter({ on_hold: e.target.checked ? 1 : '' })}
                            className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                        />
                        On hold only
                    </label>
                    <Link
                        href={route('imports.show', 'customers')}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        <Upload className="w-4 h-4" />
                        Import CSV
                    </Link>
                    <Link
                        href={route('customers.create')}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        Add Customer
                    </Link>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'customer_number', label: 'Number', sortable: 'customer_number', render: (c) => <span className="font-mono text-slate-600">{c.customer_number}</span> },
                        {
                            key: 'name', label: 'Name', sortable: 'name', render: (c) => (
                                <span className="font-semibold">
                                    <EntityLink type="customer" id={c.id} label={c.name} />
                                    {c.is_walk_in && <span className="ml-2 text-[10px] uppercase tracking-wide text-slate-400">walk-in</span>}
                                </span>
                            ),
                        },
                        { key: 'type', label: 'Type', sortable: 'type', render: (c) => <span className="capitalize text-slate-500">{c.type}</span> },
                        { key: 'group', label: 'Group', render: (c) => <span className="text-slate-500">{c.group ?? '—'}</span> },
                        { key: 'phone', label: 'Phone', render: (c) => <span className="text-slate-500">{c.phone ?? '—'}</span> },
                        { key: 'credit_limit', label: 'Credit Limit', align: 'right', sortable: 'credit_limit', render: (c) => <MoneyDisplay amount={c.credit_limit} /> },
                        { key: 'on_hold', label: '', render: (c) => (c.on_hold ? <StatusBadge status="on_hold" /> : null) },
                    ]}
                    rows={customers.data}
                    pagination={customers}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(c) => route('customers.show', c.id)}
                    emptyTitle="No customers"
                    emptyMessage="Add your first trade or cash customer."
                    emptyIcon={Users}
                />
            </div>
        </ModuleLayout>
    );
}
