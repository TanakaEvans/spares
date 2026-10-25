// Sales & POS nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    Banknote, CalendarClock, ClipboardList, FileText, HandCoins, Layers, ListOrdered,
    PackageSearch, Percent, Receipt, ScanBarcode, ShoppingCart, Truck, Undo2, Users,
} from 'lucide-react';

export default {
    moduleKey: 'sales',
    moduleLabel: 'Sales & POS',
    moduleIcon: ShoppingCart,

    sections: [
        {
            section: 'Sell',
            items: [
                {
                    label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode,
                    related: [
                        { label: 'Daily Cash-up', route: 'reports.cashup', icon: Banknote },
                        { label: 'Customers', route: 'customers.index', icon: Users },
                        { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
                    ],
                },
                {
                    label: 'Quotations', route: 'sales.quotes.index', icon: FileText,
                    children: [{ label: 'New Quote', route: 'sales.quotes.create' }],
                    related: [{ label: 'Customers', route: 'customers.index', icon: Users }],
                },
                {
                    label: 'Sales Orders', route: 'sales.orders.index', icon: ClipboardList,
                    children: [{ label: 'New Order', route: 'sales.orders.create' }],
                    related: [{ label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers }],
                },
                {
                    label: 'Lay-bys', route: 'sales.laybys.index', icon: CalendarClock,
                    related: [{ label: 'Customers', route: 'customers.index', icon: Users }],
                },
            ],
        },
        {
            section: 'Documents',
            items: [
                {
                    label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt,
                    related: [
                        { label: 'Customer Receipts', route: 'finance.receipts.index', icon: HandCoins },
                        { label: 'Customers', route: 'customers.index', icon: Users },
                    ],
                },
                { label: 'Credit Notes', route: 'sales.credit-notes.index', icon: Undo2 },
                { label: 'Delivery Notes', route: 'sales.delivery-notes.index', icon: Truck },
            ],
        },
        {
            section: 'Pricing',
            items: [
                {
                    label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered,
                    related: [{ label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch }],
                },
                { label: 'Promotions', route: 'sales.promotions.index', icon: Percent },
            ],
        },
    ],
};
