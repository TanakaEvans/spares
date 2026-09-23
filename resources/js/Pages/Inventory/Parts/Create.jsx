import { Head, useForm } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import PartForm from './PartForm';

export default function PartCreate({ categories, brands, units }) {
    const { data, setData, post, processing, errors } = useForm({
        part_number: '', oem_number: '', description: '', short_description: '',
        category_id: '', brand_id: '', unit_id: '', barcode_ean: '',
        weight_kg: '', is_oem: false, is_active: true, notes: '',
    });

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Part"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Parts', href: route('inventory.parts.index') },
                { label: 'New Part' },
            ]}
        >
            <Head title="New Part" />
            <div className="max-w-3xl">
                <PartForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    categories={categories}
                    brands={brands}
                    units={units}
                    processing={processing}
                    submitLabel="Create Part"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('inventory.parts.store'));
                    }}
                />
            </div>
        </ModuleLayout>
    );
}
