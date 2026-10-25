import { Head, Link } from '@inertiajs/react';
import { Plus, CarFront } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import EntityLink from '@/Components/EntityLink';
import { useListFilter } from '@/lib/listFilters';

export default function VehiclesIndex({ vehicles, filters }) {
    const filter = useListFilter('workshop.vehicles.index', filters);
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Vehicle Registry"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Vehicle Registry' }]}
        >
            <Head title="Vehicle Registry" />
            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Registration, VIN or customer…"
                    searchClass="w-72"
                >
                    <Link href={route('workshop.vehicles.create')} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> Register Vehicle
                    </Link>
                </ListToolbar>
                <DataTable
                    columns={[
                        { key: 'registration', label: 'Registration', sortable: 'registration', render: (v) => <EntityLink type="vehicle" id={v.id} label={v.registration} mono className="font-semibold" /> },
                        { key: 'make_model', label: 'Vehicle', render: (v) => <EntityLink type="model" id={v.model_id} label={v.make_model} /> },
                        { key: 'year', label: 'Year', sortable: 'year', render: (v) => <span className="tabular-nums text-slate-500">{v.year ?? '—'}</span> },
                        { key: 'customer', label: 'Owner', render: (v) => <EntityLink type="customer" id={v.customer_id} label={v.customer} muted /> },
                    ]}
                    rows={vehicles.data}
                    pagination={vehicles}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(v) => route('workshop.vehicles.show', v.id)}
                    emptyTitle="No vehicles"
                    emptyMessage="Register a customer's vehicle to start its service history."
                    emptyIcon={CarFront}
                />
            </div>
        </ModuleLayout>
    );
}
