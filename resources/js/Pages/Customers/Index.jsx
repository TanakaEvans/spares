import { Head, Link, router } from '@inertiajs/react';
import { Plus, Users } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function CustomersIndex({ customers, filters }) {
    function filter(params) {
        router.get(route('customers.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true, replace: true });
    }

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
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput
                        id="cust-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => filter({ search: s })}
                        placeholder="Name, number or phone…"
                        className="w-72"
                    />
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
                        href={route('customers.create')}
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                    >
                        <Plus className="w-4 h-4" />
                        Add Customer
                    </Link>
                </div>

                <DataTable
                    columns={[
                        { key: 'customer_number', label: 'Number', render: (c) => <span className="font-mono text-slate-600">{c.customer_number}</span> },
                        {
                            key: 'name', label: 'Name', render: (c) => (
                                <span className="font-semibold text-slate-800">
                                    {c.name}
                                    {c.is_walk_in && <span className="ml-2 text-[10px] uppercase tracking-wide text-slate-400">walk-in</span>}
                                </span>
                            ),
                        },
                        { key: 'type', label: 'Type', render: (c) => <span className="capitalize text-slate-500">{c.type}</span> },
                        { key: 'group', label: 'Group', render: (c) => <span className="text-slate-500">{c.group ?? '—'}</span> },
                        { key: 'phone', label: 'Phone', render: (c) => <span className="text-slate-500">{c.phone ?? '—'}</span> },
                        { key: 'credit_limit', label: 'Credit Limit', align: 'right', render: (c) => <MoneyDisplay amount={c.credit_limit} /> },
                        { key: 'on_hold', label: '', render: (c) => (c.on_hold ? <StatusBadge status="on_hold" /> : null) },
                    ]}
                    rows={customers.data}
                    pagination={customers}
                    rowHref={(c) => route('customers.show', c.id)}
                    emptyTitle="No customers"
                    emptyMessage="Add your first trade or cash customer."
                    emptyIcon={Users}
                />
            </div>
        </ModuleLayout>
    );
}
