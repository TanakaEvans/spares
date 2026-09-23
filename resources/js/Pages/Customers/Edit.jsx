import { Head } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/customers';
import CustomerForm from './CustomerForm';

export default function CustomerEdit({ customer, groups, priceLists, types }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Edit ${customer.name}`}
            breadcrumbs={[
                { label: 'Customers', href: route('modules.show', 'customers') },
                { label: 'Customer Profiles', href: route('customers.index') },
                { label: customer.name, href: route('customers.show', customer.id) },
                { label: 'Edit' },
            ]}
        >
            <Head title={`Edit ${customer.name}`} />
            <CustomerForm
                customer={customer}
                groups={groups}
                priceLists={priceLists}
                types={types}
                action={route('customers.update', customer.id)}
                method="patch"
                submitLabel="Save Changes"
            />
        </ModuleLayout>
    );
}
