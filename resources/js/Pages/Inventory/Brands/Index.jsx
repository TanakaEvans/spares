import { Head } from '@inertiajs/react';
import { Eye, PackageSearch, Pencil, Plus, Tags, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import RowActions from '@/Components/RowActions';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import BrandDialog, { BLANK_BRAND, brandValues } from './BrandDialog';

function Pills({ value, options, onChange }) {
    return (
        <div className="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
            {options.map(([v, label]) => (
                <button key={v} onClick={() => onChange(v)}
                    className={`rounded-md px-3 py-1.5 text-xs font-semibold ${(value ?? '') === v ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'}`}>
                    {label}
                </button>
            ))}
        </div>
    );
}

export default function BrandsIndex({ brands, filters }) {
    const filter = useListFilter('inventory.brands.index', filters);
    const editor = useRecordEditor(BLANK_BRAND);
    const { confirmDelete, deleteDialog } = useDeleteRecord();

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Part Brands"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Brands' },
            ]}
        >
            <Head title="Part Brands" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Part Brands</h1>
                        <p className="text-sm text-slate-500">Click a brand to see every part it makes, with stock.</p>
                    </div>
                    <button onClick={() => editor.open()} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Brand
                    </button>
                </div>

                <ListToolbar filters={filters} onFilter={filter} searchPlaceholder="Brand, code or country…" searchClass="w-72">
                    <Pills value={filters.kind} onChange={(v) => filter({ kind: v })} options={[['', 'All types'], ['oem', 'OEM'], ['aftermarket', 'Aftermarket']]} />
                    <Pills value={filters.status} onChange={(v) => filter({ status: v })} options={[['', 'All'], ['active', 'Active'], ['inactive', 'Inactive']]} />
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'name', label: 'Brand', sortable: 'name', render: (b) => <span className="font-semibold text-slate-800">{b.name}</span> },
                        { key: 'code', label: 'Code', render: (b) => <span className="font-mono text-slate-500">{b.code}</span> },
                        { key: 'country_of_origin', label: 'Origin', render: (b) => <span className="text-slate-500">{b.country_of_origin ?? '—'}</span> },
                        { key: 'is_oem_brand', label: 'Type', render: (b) => <StatusBadge status={b.is_oem_brand ? 'posted' : 'draft'} label={b.is_oem_brand ? 'OEM' : 'Aftermarket'} /> },
                        { key: 'parts_count', label: 'Parts', align: 'right', sortable: 'parts_count', render: (b) => <span className={`tabular-nums ${b.parts_count ? 'font-semibold' : 'text-slate-300'}`}>{b.parts_count.toLocaleString()}</span> },
                        { key: 'is_active', label: '', render: (b) => !b.is_active && <StatusBadge status="inactive" /> },
                        {
                            key: 'actions', label: '', align: 'right', render: (b) => (
                                <RowActions actions={[
                                    { label: 'Open', icon: Eye, href: route('inventory.brands.show', b.id) },
                                    { label: 'Edit', icon: Pencil, onSelect: () => editor.open(b, brandValues(b)) },
                                    { label: 'Parts list', icon: PackageSearch, href: route('inventory.parts.index', { brand_id: b.id }) },
                                    { label: 'Delete', icon: Trash2, destructive: true, onSelect: () => confirmDelete({ url: route('inventory.brands.destroy', b.id), label: `brand ${b.name}` }) },
                                ]} />
                            ),
                        },
                    ]}
                    rows={brands.data}
                    pagination={brands}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(b) => route('inventory.brands.show', b.id)}
                    emptyIcon={Tags}
                    emptyTitle="No brands found"
                    emptyMessage={filters.search || filters.kind || filters.status ? 'Clear a filter to see more.' : 'Add the first brand.'}
                />
            </div>

            <BrandDialog editor={editor} />
            {deleteDialog}
        </ModuleLayout>
    );
}
