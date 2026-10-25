// Reports & Analytics nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    Banknote, BarChart3, Clock, Columns2, Landmark, LayoutDashboard, Package, ScanBarcode,
    ShoppingCart, TrendingUp, Truck, Users, Wrench,
} from 'lucide-react';

export default {
    moduleKey: 'reports',
    moduleLabel: 'Reports & Analytics',
    moduleIcon: BarChart3,

    sections: [
        {
            section: 'Overview',
            items: [
                {
                    label: 'Executive Dashboard', route: 'reports.dashboard', icon: LayoutDashboard,
                    related: [
                        { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: Columns2 },
                        { label: 'Income Statement', route: 'finance.reports.income-statement', icon: TrendingUp },
                        { label: 'Balance Sheet', route: 'finance.reports.balance-sheet', icon: Landmark },
                    ],
                },
            ],
        },
        {
            section: 'Daily',
            items: [
                {
                    label: 'Daily Cash-up', route: 'reports.cashup', icon: Banknote,
                    related: [{ label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode }],
                },
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
        {
            section: 'Automation',
            items: [
                { label: 'Scheduled Reports', route: 'reports.scheduled.index', icon: Clock },
            ],
        },
    ],
};
