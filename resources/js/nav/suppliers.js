// Suppliers module nav — content only.
import {
    FileInput, LayoutList, ListOrdered, PackageCheck, Plus, Truck, Upload,
    BadgeCheck, UsersRound, TrendingUp,
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
        {
            section: 'Lists',
            items: [
                { label: 'Approved Suppliers', route: 'suppliers.approved.index', icon: BadgeCheck },
                { label: 'Contacts', route: 'suppliers.contacts.index', icon: UsersRound },
                { label: 'Performance', route: 'suppliers.performance.index', icon: TrendingUp },
            ],
        },
    ],

    subModules: {
        approved: {
            label: 'Approved Suppliers', icon: BadgeCheck, routePrefix: 'suppliers.approved',
            work: [{ label: 'Approved List', route: 'suppliers.approved.index', icon: LayoutList }],
            quickLinks: [
                { label: 'All Suppliers', route: 'suppliers.index', icon: Truck },
                { label: 'Reorder Report', route: 'inventory.reorder.index', icon: ListOrdered },
            ],
        },
        contacts: {
            label: 'Supplier Contacts', icon: UsersRound, routePrefix: 'suppliers.contacts',
            work: [{ label: 'All Contacts', route: 'suppliers.contacts.index', icon: LayoutList }],
            quickLinks: [{ label: 'All Suppliers', route: 'suppliers.index', icon: Truck }],
        },
        performance: {
            label: 'Supplier Performance', icon: TrendingUp, routePrefix: 'suppliers.performance',
            work: [{ label: 'Performance', route: 'suppliers.performance.index', icon: TrendingUp }],
            quickLinks: [
                { label: 'All Suppliers', route: 'suppliers.index', icon: Truck },
                { label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput },
            ],
        },
        suppliers: {
            label: 'Supplier Profiles',
            icon: Truck,
            routePrefix: 'suppliers.',
            work: [
                { label: 'All Suppliers', route: 'suppliers.index', icon: LayoutList },
                { label: 'New Supplier', route: 'suppliers.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Approved Suppliers', route: 'suppliers.approved.index', icon: BadgeCheck },
                { label: 'Contacts', route: 'suppliers.contacts.index', icon: UsersRound },
                { label: 'Performance', route: 'suppliers.performance.index', icon: TrendingUp },
            ],
        },
    },
};

export default navConfig;
