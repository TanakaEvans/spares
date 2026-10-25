import { Head } from '@inertiajs/react';
import { Eye, Factory, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import ListToolbar from '@/Components/ListToolbar';
import RowActions from '@/Components/RowActions';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import MakeDialog, { BLANK_MAKE, makeValues } from './MakeDialog';

const STATUS = [['', 'All'], ['active', 'Active'], ['inactive', 'Inactive']];

export default function MakesIndex({ makes, filters }) {
    const filter = useListFilter('vehicle-ref.makes.index', filters);
    const editor = useRecordEditor(BLANK_MAKE);
    const { confirmDelete, deleteDialog } = useDeleteRecord();

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Vehicle Makes"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Makes' },
            ]}
        >
            <Head title="Vehicle Makes" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Vehicle Makes</h1>
                        <p className="text-sm text-slate-500">Click a make to see its models, engines and every part that fits it.</p>
                    </div>
                    <button onClick={() => editor.open()} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Make
                    </button>
                </div>

                <ListToolbar filters={filters} onFilter={filter} searchPlaceholder="Make, code or country…" searchClass="w-72">
                    <div className="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
                        {STATUS.map(([value, label]) => (
                            <button
                                key={value}
                                onClick={() => filter({ status: value })}
                                className={`rounded-md px-3 py-1.5 text-xs font-semibold ${(filters.status ?? '') === value ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'}`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'name', label: 'Make', sortable: 'name', render: (m) => <EntityLink type="make" id={m.id} label={m.name} className="font-semibold" /> },
                        { key: 'code', label: 'Code', render: (m) => <span className="font-mono text-slate-500">{m.code}</span> },
                        { key: 'country_of_origin', label: 'Origin', render: (m) => <span className="text-slate-500">{m.country_of_origin ?? '—'}</span> },
                        { key: 'models_count', label: 'Models', align: 'right', sortable: 'models_count', render: (m) => <span className="tabular-nums">{m.models_count}</span> },
                        { key: 'parts_count', label: 'Parts fitted', align: 'right', render: (m) => <span className={`tabular-nums ${m.parts_count ? 'font-semibold text-slate-800' : 'text-slate-300'}`}>{m.parts_count.toLocaleString()}</span> },
                        { key: 'is_active', label: 'Status', render: (m) => <StatusBadge status={m.is_active ? 'active' : 'inactive'} /> },
                        {
                            key: 'actions', label: '', align: 'right', render: (m) => (
                                <RowActions actions={[
                                    { label: 'Open', icon: Eye, href: route('vehicle-ref.makes.show', m.id) },
                                    { label: 'Edit', icon: Pencil, onSelect: () => editor.open(m, makeValues(m)) },
                                    { label: 'Parts that fit', icon: Search, href: route('vehicle-ref.fitment', { make_id: m.id }) },
                                    { label: 'Delete', icon: Trash2, destructive: true, onSelect: () => confirmDelete({ url: route('vehicle-ref.makes.destroy', m.id), label: m.name }) },
                                ]} />
                            ),
                        },
                    ]}
                    rows={makes.data}
                    pagination={makes}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(m) => route('vehicle-ref.makes.show', m.id)}
                    emptyIcon={Factory}
                    emptyTitle={filters.search || filters.status ? 'No makes match' : 'No makes yet'}
                    emptyMessage={filters.search || filters.status ? 'Clear the search or status filter.' : 'Add the first vehicle make, or run the ReferenceDataSeeder.'}
                />
            </div>

            <MakeDialog editor={editor} />
            {deleteDialog}
        </ModuleLayout>
    );
}
