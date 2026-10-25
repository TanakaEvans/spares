// Customers nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    Award, BarChart3, CarFront, HandCoins, ListOrdered, MessageSquare, Receipt,
    ScanBarcode, Users, UsersRound,
} from 'lucide-react';

export default {
    moduleKey: 'customers',
    moduleLabel: 'Customers',
    moduleIcon: Users,

    sections: [
        {
            section: 'Customers',
            items: [
                {
                    // 'customers' owns customers.index/show/create/edit; the more specific
                    // groups/comms/loyalty prefixes below win by longest-match.
                    label: 'All Customers', route: 'customers.index', icon: Users, match: 'customers',
                    children: [{ label: 'Add Customer', route: 'customers.create' }],
                    related: [
                        { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
                        { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                        { label: 'Customer Receipts', route: 'finance.receipts.index', icon: HandCoins },
                        { label: 'Vehicle Registry', route: 'workshop.vehicles.index', icon: CarFront },
                    ],
                },
                {
                    label: 'Customer Groups', route: 'customers.groups.index', icon: UsersRound,
                    related: [{ label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered }],
                },
            ],
        },
        {
            section: 'Engagement',
            items: [
                { label: 'Communication Log', route: 'customers.comms.index', icon: MessageSquare },
                {
                    label: 'Loyalty Programme', route: 'customers.loyalty.index', icon: Award,
                    related: [{ label: 'Customer Reports', route: 'reports.customers.index', icon: BarChart3 }],
                },
            ],
        },
    ],
};
