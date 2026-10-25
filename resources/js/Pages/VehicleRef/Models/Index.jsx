import { Head } from '@inertiajs/react';
import { Car, Eye, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import ListToolbar from '@/Components/ListToolbar';
import RowActions from '@/Components/RowActions';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import { BLANK_MODEL, ModelDialog, modelValues } from './ModelDialogs';

export default function ModelsIndex({ models, makes, filters }) {
    const filter = useListFilter('vehicle-ref.models.index', filters);
    const editor = useRecordEditor({ ...BLANK_MODEL, make_id: filters.make_id ?? '' });
    const { confirmDelete, deleteDialog } = useDeleteRecord();

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Vehicle Models"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Models & Variants' },
            ]}
        >
            <Head title="Vehicle Models" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Models & Variants</h1>
                        <p className="text-sm text-slate-500">Click a model to manage its variants and see every part that fits.</p>
                    </div>
                    <button onClick={() => editor.open(null, { make_id: filters.make_id ?? '' })} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Model
                    </button>
                </div>

                <ListToolbar filters={filters} onFilter={filter} searchPlaceholder="Model, make or engine code…" searchClass="w-72">
                    <select aria-label="Filter by make" value={filters.make_id ?? ''} onChange={(e) => filter({ make_id: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All makes</option>
                        {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                    </select>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'make', label: 'Make', render: (m) => <EntityLink type="make" id={m.make_id} label={m.make?.name} muted /> },
                        { key: 'name', label: 'Model', sortable: 'name', render: (m) => <EntityLink type="model" id={m.id} label={m.name} className="font-semibold" /> },
                        { key: 'body_type', label: 'Body', render: (m) => <span className="capitalize text-slate-500">{m.body_type ?? '—'}</span> },
                        { key: 'years', label: 'Years', render: (m) => <span className="tabular-nums text-slate-500">{m.year_from ?? '?'}–{m.year_to ?? 'now'}</span> },
                        { key: 'variants_count', label: 'Variants', align: 'right' },
                        { key: 'is_active', label: '', render: (m) => !m.is_active && <StatusBadge status="inactive" /> },
                        {
                            key: 'actions', label: '', align: 'right', render: (m) => (
                                <RowActions actions={[
                                    { label: 'Open', icon: Eye, href: route('vehicle-ref.models.show', m.id) },
                                    { label: 'Edit', icon: Pencil, onSelect: () => editor.open(m, modelValues(m)) },
                                    { label: 'Parts that fit', icon: Search, href: route('vehicle-ref.fitment', { make_id: m.make_id, model_id: m.id }) },
                                    { label: 'Delete', icon: Trash2, destructive: true, onSelect: () => confirmDelete({ url: route('vehicle-ref.models.destroy', m.id), label: `${m.make?.name ?? ''} ${m.name}`.trim(), message: 'Its variants go with it. If parts or customer vehicles still reference it, it is deactivated instead.' }) },
                                ]} />
                            ),
                        },
                    ]}
                    rows={models.data}
                    pagination={models}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(m) => route('vehicle-ref.models.show', m.id)}
                    emptyIcon={Car}
                    emptyTitle="No models found"
                    emptyMessage={filters.search || filters.make_id ? 'Clear the search or make filter.' : 'Add the first model.'}
                />
            </div>

            <ModelDialog editor={editor} makes={makes} />
            {deleteDialog}
        </ModuleLayout>
    );
}
