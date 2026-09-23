// Reports & Analytics nav — content only (styling lives in ModuleLayout).
import {
    BarChart3, LayoutDashboard, ShoppingCart, Package, Users, Truck, Wrench,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'reports',
    moduleLabel: 'Reports & Analytics',
    moduleIcon: BarChart3,

    module: [
        {
            section: 'Overview',
            items: [
                { label: 'Executive Dashboard', route: 'reports.dashboard', icon: LayoutDashboard },
            ],
        },
        {
            section: 'Report Suites',
            items: [
                { label: 'Sales', route: 'reports.sales.index', icon: ShoppingCart },
                { label: 'Inventory', route: 'reports.inventory.index', icon: Package },
                { label: 'Customers', route: 'reports.customers.index', icon: Users },
                { label: 'Suppliers', route: 'reports.suppliers.index', icon: Truck },
                { label: 'Workshop', route: 'reports.workshop.index', icon: Wrench },
            ],
        },
    ],

    subModules: {
        dashboard: {
            label: 'Executive Dashboard', icon: LayoutDashboard, routePrefix: 'reports.dashboard',
            work: [{ label: 'Dashboard', route: 'reports.dashboard', icon: LayoutDashboard }],
            quickLinks: [
                { label: 'Sales Reports', route: 'reports.sales.index', icon: ShoppingCart },
                { label: 'Inventory Reports', route: 'reports.inventory.index', icon: Package },
            ],
        },
        sales: {
            label: 'Sales Reports', icon: ShoppingCart, routePrefix: 'reports.sales',
            work: [{ label: 'Sales', route: 'reports.sales.index', icon: ShoppingCart }],
            quickLinks: [
                { label: 'Dashboard', route: 'reports.dashboard', icon: LayoutDashboard },
                { label: 'Customer Reports', route: 'reports.customers.index', icon: Users },
            ],
        },
        inventory: {
            label: 'Inventory Reports', icon: Package, routePrefix: 'reports.inventory',
            work: [{ label: 'Inventory', route: 'reports.inventory.index', icon: Package }],
            quickLinks: [
                { label: 'Dashboard', route: 'reports.dashboard', icon: LayoutDashboard },
                { label: 'Supplier Reports', route: 'reports.suppliers.index', icon: Truck },
            ],
        },
        customers: {
            label: 'Customer Reports', icon: Users, routePrefix: 'reports.customers',
            work: [{ label: 'Customers', route: 'reports.customers.index', icon: Users }],
            quickLinks: [
                { label: 'Sales Reports', route: 'reports.sales.index', icon: ShoppingCart },
                { label: 'Dashboard', route: 'reports.dashboard', icon: LayoutDashboard },
            ],
        },
        suppliers: {
            label: 'Supplier Reports', icon: Truck, routePrefix: 'reports.suppliers',
            work: [{ label: 'Suppliers', route: 'reports.suppliers.index', icon: Truck }],
            quickLinks: [
                { label: 'Inventory Reports', route: 'reports.inventory.index', icon: Package },
                { label: 'Dashboard', route: 'reports.dashboard', icon: LayoutDashboard },
            ],
        },
        workshop: {
            label: 'Workshop Reports', icon: Wrench, routePrefix: 'reports.workshop',
            work: [{ label: 'Workshop', route: 'reports.workshop.index', icon: Wrench }],
            quickLinks: [
                { label: 'Dashboard', route: 'reports.dashboard', icon: LayoutDashboard },
                { label: 'Sales Reports', route: 'reports.sales.index', icon: ShoppingCart },
            ],
        },
    },
};

export default navConfig;
