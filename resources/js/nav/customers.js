// Customers module nav — content only (styling lives in ModuleLayout).
import {
    Users, UsersRound, LayoutList, Plus, Landmark, ScanBarcode,
    Receipt, ListOrdered,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'customers',
    moduleLabel: 'Customers',
    moduleIcon: Users,

    module: [
        {
            section: 'Directory',
            items: [
                { label: 'Customers', route: 'customers.index', icon: Users },
                { label: 'Customer Groups', route: 'customers.groups.index', icon: UsersRound },
            ],
        },
    ],

    // NOTE: groups is listed before profiles so its more specific prefix
    // ('customers.groups') resolves before the catch-all 'customers.'.
    subModules: {
        groups: {
            label: 'Customer Groups',
            icon: UsersRound,
            routePrefix: 'customers.groups',
            work: [
                { label: 'All Groups', route: 'customers.groups.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Customers', route: 'customers.index', icon: Users },
                { label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered },
            ],
        },
        profiles: {
            label: 'Customer Profiles',
            icon: Users,
            routePrefix: 'customers.',
            work: [
                { label: 'All Customers', route: 'customers.index', icon: LayoutList },
                { label: 'Add Customer', route: 'customers.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Customer Groups', route: 'customers.groups.index', icon: UsersRound },
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
            ],
        },
    },
};

export default navConfig;
