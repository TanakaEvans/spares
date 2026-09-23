// Suppliers module nav — content only.
import {
    FileInput, LayoutList, ListOrdered, PackageCheck, Plus, Truck, Upload,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'suppliers',
    moduleLabel: 'Suppliers',
    moduleIcon: Truck,

    module: [
        {
            section: 'Suppliers',
            items: [
                { label: 'All Suppliers', route: 'suppliers.index', icon: LayoutList },
                { label: 'New Supplier', route: 'suppliers.create', icon: Plus },
            ],
        },
    ],

    subModules: {
        suppliers: {
            label: 'Supplier Profiles',
            icon: Truck,
            routePrefix: 'suppliers.',
            work: [
                { label: 'All Suppliers', route: 'suppliers.index', icon: LayoutList },
                { label: 'New Supplier', route: 'suppliers.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Purchasing: Orders', route: 'purchasing.orders.index', icon: FileInput },
                { label: 'Purchasing: Receiving', route: 'purchasing.grns.index', icon: PackageCheck },
                { label: 'Purchasing: Invoices', route: 'purchasing.invoices.index', icon: ListOrdered },
            ],
        },
    },
};

export default navConfig;
