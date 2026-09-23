import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function PartsIndex({ parts, categories, filters }) {
    function filter(params) {
        router.get(route('inventory.parts.index'), Object.fromEntries(
            Object.entries({ ...filters, ...params }).filter(([, v]) => v)
        ), { preserveState: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Parts Catalogue"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Parts' },
            ]}
        >
            <Head title="Parts Catalogue" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput
                        id="parts-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => filter({ search: s })}
                        placeholder="Part no, OEM no, barcode, description, cross-ref…"
                        autoFocus
                        className="w-80"
                    />
                    <select
                        aria-label="Filter by category"
                        value={filters.category_id ?? ''}
                        onChange={(e) => filter({ category_id: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All categories</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>{c.parent_id ? '  ' : ''}{c.name}</option>
                        ))}
                    </select>
                    <select
                        aria-label="Filter by status"
                        value={filters.status ?? ''}
                        onChange={(e) => filter({ status: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <Link
                        href={route('inventory.parts.create')}
                        className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        New Part
                    </Link>
                </div>

                <DataTable
                    columns={[
                        {
                            key: 'part_number', label: 'Part No',
                            render: (p) => (
                                <div>
                                    <span className="font-mono font-semibold text-slate-800">{p.part_number}</span>
                                    {p.superseded_by && (
                                        <span className="block text-[11px] text-amber-600">
                                            → {p.superseded_by}
                                        </span>
                                    )}
                                </div>
                            ),
                        },
                        { key: 'description', label: 'Description', render: (p) => <span className="text-slate-700">{p.description}</span> },
                        { key: 'brand', label: 'Brand', render: (p) => <span className="text-slate-500">{p.brand}</span> },
                        { key: 'category', label: 'Category', render: (p) => <span className="text-slate-500">{p.category}</span> },
                        {
                            key: 'qty_available', label: 'Available', align: 'right',
                            render: (p) => p.qty_available > 0
                                ? <span className="text-green-700 font-semibold">{p.qty_available}</span>
                                : <span className="text-slate-400">0</span>,
                        },
                        { key: 'is_active', label: 'Status', render: (p) => <StatusBadge status={p.is_active ? 'active' : 'inactive'} /> },
                    ]}
                    rows={parts.data}
                    pagination={parts}
                    rowHref={(p) => route('inventory.parts.show', p.id)}
                    emptyTitle="No parts match"
                    emptyMessage="Try another number — the search also covers OEM numbers, barcodes and cross-references."
                    emptyAction={
                        <Link
                            href={route('inventory.parts.create')}
                            className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                        >
                            <Plus className="w-4 h-4" />
                            Add this part
                        </Link>
                    }
                />
            </div>
        </ModuleLayout>
    );
}
