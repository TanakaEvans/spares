import { Head, Link } from '@inertiajs/react';
import { Cog, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import ListToolbar from '@/Components/ListToolbar';
import RowActions from '@/Components/RowActions';
import { useListFilter } from '@/lib/listFilters';
import { useDeleteRecord, useRecordEditor } from '@/lib/records';
import EngineDialog, { BLANK_ENGINE, FUELS, engineValues } from './EngineDialog';

export default function EnginesIndex({ engines, makes, filters }) {
    const filter = useListFilter('vehicle-ref.engines.index', filters);
    const editor = useRecordEditor(BLANK_ENGINE);
    const { confirmDelete, deleteDialog } = useDeleteRecord();
    const usage = (n, one, many) => `${n} ${n === 1 ? one : many}`;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Engine Codes"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Engine Codes' },
            ]}
        >
            <Head title="Engine Codes" />

            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">Engine Codes</h1>
                        <p className="text-sm text-slate-500">Click an engine to see the vehicles that carry it and every part fitted to it.</p>
                    </div>
                    <button onClick={() => editor.open()} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> New Engine Code
                    </button>
                </div>

                <ListToolbar filters={filters} onFilter={filter} searchPlaceholder="Code, description or make… e.g. 1GD-FTV" searchClass="w-80">
                    <select aria-label="Filter by make" value={filters.make_id ?? ''} onChange={(e) => filter({ make_id: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All makes</option>
                        {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                    </select>
                    <div className="inline-flex rounded-lg border border-slate-200 bg-white p-0.5">
                        {['', ...FUELS.slice(0, 3)].map((f) => (
                            <button key={f} onClick={() => filter({ fuel_type: f })}
                                className={`rounded-md px-3 py-1.5 text-xs font-semibold capitalize ${(filters.fuel_type ?? '') === f ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'}`}>
                                {f || 'All fuels'}
                            </button>
                        ))}
                    </div>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'code', label: 'Code', sortable: 'code', render: (e) => <EntityLink type="engine" id={e.id} label={e.code} mono className="font-semibold" /> },
                        { key: 'make', label: 'Make', render: (e) => <EntityLink type="make" id={e.make_id} label={e.make?.name ?? '—'} muted /> },
                        { key: 'description', label: 'Description', render: (e) => <span className="text-slate-600">{e.description}</span> },
                        { key: 'capacity_cc', label: 'CC', align: 'right', sortable: 'capacity_cc', render: (e) => <span className="tabular-nums">{e.capacity_cc?.toLocaleString() ?? '—'}</span> },
                        { key: 'fuel_type', label: 'Fuel', sortable: 'fuel_type', render: (e) => <span className="capitalize text-slate-500">{e.fuel_type}</span> },
                        {
                            key: 'usage', label: 'Used by', align: 'right',
                            render: (e) => (
                                <span className="text-xs whitespace-nowrap">
                                    <span className={e.models_count ? 'text-slate-600' : 'text-slate-300'}>{usage(e.models_count, 'model', 'models')}</span>
                                    {' · '}
                                    {e.parts_count > 0
                                        ? <Link href={route('vehicle-ref.engines.show', e.id)} onClick={(ev) => ev.stopPropagation()} className="font-semibold text-orange-700 hover:underline">{usage(e.parts_count, 'part', 'parts')}</Link>
                                        : <span className="text-slate-300">no parts</span>}
                                </span>
                            ),
                        },
                        {
                            key: 'actions', label: '', align: 'right', render: (e) => (
                                <RowActions actions={[
                                    { label: 'Open', icon: Eye, href: route('vehicle-ref.engines.show', e.id) },
                                    { label: 'Edit', icon: Pencil, onSelect: () => editor.open(e, engineValues(e)) },
                                    {
                                        label: 'Delete', icon: Trash2, destructive: true,
                                        disabled: e.models_count || e.parts_count ? 'In use by vehicles or part fitments — rename it via Edit instead.' : false,
                                        onSelect: () => confirmDelete({ url: route('vehicle-ref.engines.destroy', e.id), label: `engine ${e.code}`, message: 'This engine code is not used anywhere and will be removed.' }),
                                    },
                                ]} />
                            ),
                        },
                    ]}
                    rows={engines.data}
                    pagination={engines}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(e) => route('vehicle-ref.engines.show', e.id)}
                    emptyIcon={Cog}
                    emptyTitle="No engine codes found"
                    emptyMessage={filters.search || filters.make_id || filters.fuel_type ? 'Clear a filter to see more.' : 'Add the first engine code.'}
                />
            </div>

            <EngineDialog editor={editor} makes={makes} />
            {deleteDialog}
        </ModuleLayout>
    );
}
