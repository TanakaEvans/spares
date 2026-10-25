// Purchasing nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    BadgeCheck, Factory, FileInput, FileMinus, FileSpreadsheet, Layers, MapPin, PackageCheck,
    PackageX, Play, RefreshCw, Rows3, Scale, Ship, Truck,
} from 'lucide-react';

export default {
    moduleKey: 'purchasing',
    moduleLabel: 'Purchasing',
    moduleIcon: Factory,

    sections: [
        {
            section: 'Buy',
            items: [
                {
                    label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput,
                    children: [{ label: 'New Order', route: 'purchasing.orders.create' }],
                    related: [
                        { label: 'Reorder Report', route: 'inventory.reorder.index', icon: RefreshCw },
                        { label: 'Suppliers', route: 'suppliers.index', icon: Truck },
                    ],
                },
                {
                    label: 'Price Comparison', route: 'purchasing.price-comparison.index', icon: Scale,
                    related: [{ label: 'Approved Suppliers', route: 'suppliers.approved.index', icon: BadgeCheck }],
                },
                {
                    label: 'Import Shipments', route: 'purchasing.imports.index', icon: Ship,
                    related: [{ label: 'Suppliers', route: 'suppliers.index', icon: Truck }],
                },
            ],
        },
        {
            section: 'Receive',
            items: [
                {
                    label: 'Goods Receiving', route: 'purchasing.grns.index', icon: PackageCheck,
                    related: [
                        { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
                        { label: 'Bin Locations', route: 'inventory.bins.index', icon: MapPin },
                    ],
                },
            ],
        },
        {
            section: 'Bills',
            items: [
                {
                    label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet,
                    related: [
                        { label: 'Supplier Payments', route: 'finance.payments.index', icon: Rows3 },
                        { label: 'Payment Run', route: 'finance.payment-run.index', icon: Play },
                    ],
                },
                { label: 'Supplier Credits', route: 'purchasing.supplier-credits.index', icon: FileMinus },
            ],
        },
        {
            section: 'Returns',
            items: [
                {
                    label: 'Returns to Supplier', route: 'purchasing.returns.index', icon: PackageX,
                    children: [{ label: 'New Return', route: 'purchasing.returns.create' }],
                    related: [{ label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers }],
                },
            ],
        },
    ],
};
