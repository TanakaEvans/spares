import { Head, Link, router } from '@inertiajs/react';
import { Plus, CarFront } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';

export default function VehiclesIndex({ vehicles, filters }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Vehicle Registry"
            breadcrumbs={[{ label: 'Workshop', href: route('modules.show', 'workshop') }, { label: 'Vehicle Registry' }]}
        >
            <Head title="Vehicle Registry" />
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <SearchInput id="veh-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('workshop.vehicles.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Registration, VIN or customer…" className="w-72" />
                    <Link href={route('workshop.vehicles.create')} className="ml-auto inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                        <Plus className="w-4 h-4" /> Register Vehicle
                    </Link>
                </div>
                <DataTable
                    columns={[
                        { key: 'registration', label: 'Registration', render: (v) => <span className="font-mono font-semibold text-slate-800">{v.registration}</span> },
                        { key: 'make_model', label: 'Vehicle', render: (v) => <span className="text-slate-700">{v.make_model}</span> },
                        { key: 'year', label: 'Year', render: (v) => <span className="tabular-nums text-slate-500">{v.year ?? '—'}</span> },
                        { key: 'customer', label: 'Owner', render: (v) => <span className="text-slate-600">{v.customer}</span> },
                    ]}
                    rows={vehicles.data}
                    pagination={vehicles}
                    rowHref={(v) => route('workshop.vehicles.show', v.id)}
                    emptyTitle="No vehicles"
                    emptyMessage="Register a customer's vehicle to start its service history."
                    emptyIcon={CarFront}
                />
            </div>
        </ModuleLayout>
    );
}
