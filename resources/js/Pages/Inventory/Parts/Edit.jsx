import { Head, useForm } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import PartForm from './PartForm';

export default function PartEdit({ part, categories, brands, units }) {
    const { data, setData, patch, processing, errors } = useForm({
        part_number: part.part_number ?? '',
        oem_number: part.oem_number ?? '',
        description: part.description ?? '',
        short_description: part.short_description ?? '',
        category_id: part.category_id ?? '',
        brand_id: part.brand_id ?? '',
        unit_id: part.unit_id ?? '',
        barcode_ean: part.barcode_ean ?? '',
        weight_kg: part.weight_kg ?? '',
        is_oem: !!part.is_oem,
        is_active: !!part.is_active,
        is_discontinued: !!part.is_discontinued,
        notes: part.notes ?? '',
    });

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Edit ${part.part_number}`}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Parts', href: route('inventory.parts.index') },
                { label: part.part_number, href: route('inventory.parts.show', part.id) },
                { label: 'Edit' },
            ]}
        >
            <Head title={`Edit ${part.part_number}`} />
            <div className="max-w-3xl">
                <PartForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    categories={categories}
                    brands={brands}
                    units={units}
                    processing={processing}
                    submitLabel="Save Changes"
                    onSubmit={(e) => {
                        e.preventDefault();
                        patch(route('inventory.parts.update', part.id));
                    }}
                />
            </div>
        </ModuleLayout>
    );
}
