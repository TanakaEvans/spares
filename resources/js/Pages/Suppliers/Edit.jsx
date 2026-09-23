import { Head, useForm } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import SupplierForm from './SupplierForm';

export default function SupplierEdit({ supplier, currencies }) {
    const { data, setData, patch, processing, errors } = useForm({
        ...supplier,
        trading_name: supplier.trading_name ?? '',
        tax_number: supplier.tax_number ?? '',
        vat_number: supplier.vat_number ?? '',
        currency_id: supplier.currency_id ?? '',
        credit_limit: supplier.credit_limit ?? '',
        minimum_order_value: supplier.minimum_order_value ?? '',
        email: supplier.email ?? '',
        phone: supplier.phone ?? '',
        address: supplier.address ?? '',
        city: supplier.city ?? '',
        country: supplier.country ?? '',
        bank_name: supplier.bank_name ?? '',
        bank_branch_code: supplier.bank_branch_code ?? '',
        bank_account_name: supplier.bank_account_name ?? '',
        bank_account_number: supplier.bank_account_number ?? '',
        notes: supplier.notes ?? '',
    });

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Edit ${supplier.name}`}
            breadcrumbs={[
                { label: 'Suppliers', href: route('modules.show', 'suppliers') },
                { label: supplier.name, href: route('suppliers.show', supplier.id) },
                { label: 'Edit' },
            ]}
        >
            <Head title={`Edit ${supplier.name}`} />
            <div className="max-w-4xl">
                <SupplierForm
                    data={data} setData={setData} errors={errors} currencies={currencies}
                    processing={processing} submitLabel="Save Changes"
                    onSubmit={(e) => { e.preventDefault(); patch(route('suppliers.update', supplier.id)); }}
                />
            </div>
        </ModuleLayout>
    );
}
