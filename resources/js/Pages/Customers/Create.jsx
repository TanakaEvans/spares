import { Head } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import CustomerForm from './CustomerForm';

export default function CustomerCreate({ groups, priceLists, types }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Add Customer"
            breadcrumbs={[
                { label: 'Customers', href: route('modules.show', 'customers') },
                { label: 'Customer Profiles', href: route('customers.index') },
                { label: 'Add Customer' },
            ]}
        >
            <Head title="Add Customer" />
            <CustomerForm
                groups={groups}
                priceLists={priceLists}
                types={types}
                action={route('customers.store')}
                method="post"
                submitLabel="Create Customer"
            />
        </ModuleLayout>
    );
}
