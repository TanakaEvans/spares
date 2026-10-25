// Suppliers nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    BadgeCheck, FileInput, FileSpreadsheet, RefreshCw, Rows3, Scale, TrendingUp, Truck, UsersRound,
} from 'lucide-react';

export default {
    moduleKey: 'suppliers',
    moduleLabel: 'Suppliers',
    moduleIcon: Truck,

    sections: [
        {
            section: 'Suppliers',
            items: [
                {
                    // 'suppliers' owns profiles + per-supplier price-list import; the
                    // approved/contacts/performance prefixes below win by longest-match.
                    label: 'All Suppliers', route: 'suppliers.index', icon: Truck, match: 'suppliers',
                    children: [{ label: 'New Supplier', route: 'suppliers.create' }],
                    related: [
                        { label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput },
                        { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet },
                        { label: 'Supplier Payments', route: 'finance.payments.index', icon: Rows3 },
                    ],
                },
                { label: 'Contacts', route: 'suppliers.contacts.index', icon: UsersRound },
            ],
        },
        {
            section: 'Sourcing',
            items: [
                {
                    label: 'Approved Suppliers', route: 'suppliers.approved.index', icon: BadgeCheck,
                    related: [
                        { label: 'Reorder Report', route: 'inventory.reorder.index', icon: RefreshCw },
                        { label: 'Price Comparison', route: 'purchasing.price-comparison.index', icon: Scale },
                    ],
                },
                {
                    label: 'Performance', route: 'suppliers.performance.index', icon: TrendingUp,
                    related: [{ label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput }],
                },
            ],
        },
    ],
};
