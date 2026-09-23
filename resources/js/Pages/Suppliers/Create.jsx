import { Head, useForm } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import SupplierForm from './SupplierForm';

export default function SupplierCreate({ currencies }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '', trading_name: '', type: 'local', tax_number: '', vat_number: '',
        currency_id: '', payment_terms_days: 30, credit_limit: '', lead_time_days: 7,
        minimum_order_value: '', email: '', phone: '', address: '', city: '', country: 'Zimbabwe',
        bank_name: '', bank_branch_code: '', bank_account_name: '', bank_account_number: '',
        is_active: true, notes: '',
    });

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Supplier"
            breadcrumbs={[
                { label: 'Suppliers', href: route('modules.show', 'suppliers') },
                { label: 'All Suppliers', href: route('suppliers.index') },
                { label: 'New Supplier' },
            ]}
        >
            <Head title="New Supplier" />
            <div className="max-w-4xl">
                <SupplierForm
                    data={data} setData={setData} errors={errors} currencies={currencies}
                    processing={processing} submitLabel="Create Supplier"
                    onSubmit={(e) => { e.preventDefault(); post(route('suppliers.store')); }}
                />
            </div>
        </ModuleLayout>
    );
}
