import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function BrandsIndex({ brands, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', code: '', country_of_origin: '', is_oem_brand: false,
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function add(e) {
        e.preventDefault();
        post(route('inventory.brands.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

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

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div className="lg:col-span-2 space-y-4">
                    <SearchInput
                        id="brands-search"
                        value={filters.search ?? ''}
                        onSearch={(s) => router.get(route('inventory.brands.index'), s ? { search: s } : {}, { preserveState: true })}
                        placeholder="Search brands…"
                        className="max-w-sm"
                    />
                    <DataTable
                        columns={[
                            { key: 'name', label: 'Brand', render: (b) => <span className="font-medium text-slate-800">{b.name}</span> },
                            { key: 'code', label: 'Code', render: (b) => <span className="font-mono text-slate-500">{b.code}</span> },
                            { key: 'country_of_origin', label: 'Origin' },
                            { key: 'is_oem_brand', label: 'Type', render: (b) => <StatusBadge status={b.is_oem_brand ? 'posted' : 'draft'} label={b.is_oem_brand ? 'OEM' : 'Aftermarket'} /> },
                        ]}
                        rows={brands.data}
                        pagination={brands}
                        emptyTitle="No brands found"
                    />
                </div>

                <form onSubmit={add} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Brand</h2>
                    <FormField label="Name" htmlFor="brand-name" required error={errors.name}>
                        <input id="brand-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Code" htmlFor="brand-code" required error={errors.code}>
                        <input id="brand-code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} className={`${input} font-mono`} maxLength={20} />
                    </FormField>
                    <FormField label="Country" htmlFor="brand-country" error={errors.country_of_origin}>
                        <input id="brand-country" value={data.country_of_origin} onChange={(e) => setData('country_of_origin', e.target.value)} className={input} />
                    </FormField>
                    <label className="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" checked={data.is_oem_brand} onChange={(e) => setData('is_oem_brand', e.target.checked)} className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                        OEM brand (vehicle manufacturer)
                    </label>
                    <button
                        type="submit"
                        disabled={processing || !data.name || !data.code}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                    >
                        <Plus className="w-4 h-4" />
                        Add Brand
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
