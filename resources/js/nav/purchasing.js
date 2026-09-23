// Purchasing module nav — content only.
import {
    Factory, FileInput, FileSpreadsheet, LayoutList, PackageCheck,
    PackageX, Plus, RefreshCw, Truck, FileMinus,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'purchasing',
    moduleLabel: 'Purchasing',
    moduleIcon: Factory,

    module: [
        {
            section: 'Order',
            items: [
                { label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput },
            ],
        },
        {
            section: 'Receive',
            items: [
                { label: 'Goods Receiving', route: 'purchasing.grns.index', icon: PackageCheck },
            ],
        },
        {
            section: 'Bills',
            items: [
                { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet },
                { label: 'Supplier Credits', route: 'purchasing.supplier-credits.index', icon: FileMinus },
            ],
        },
        {
            section: 'Returns',
            items: [
                { label: 'Returns to Supplier', route: 'purchasing.returns.index', icon: PackageX },
            ],
        },
    ],

    subModules: {
        supplierCredits: {
            label: 'Supplier Credit Notes', icon: FileMinus, routePrefix: 'purchasing.supplier-credits',
            work: [{ label: 'All Credits', route: 'purchasing.supplier-credits.index', icon: LayoutList }],
            quickLinks: [
                { label: 'Returns to Supplier', route: 'purchasing.returns.index', icon: PackageX },
                { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet },
            ],
        },
        orders: {
            label: 'Purchase Orders',
            icon: FileInput,
            routePrefix: 'purchasing.orders',
            work: [
                { label: 'All Orders', route: 'purchasing.orders.index', icon: LayoutList },
                { label: 'New Order', route: 'purchasing.orders.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Goods Receiving', route: 'purchasing.grns.index', icon: PackageCheck },
                { label: 'Suppliers', route: 'suppliers.index', icon: Truck },
                { label: 'Inventory: Reorder', route: 'inventory.reorder.index', icon: RefreshCw },
            ],
        },
        grns: {
            label: 'Goods Receiving',
            icon: PackageCheck,
            routePrefix: 'purchasing.grns',
            work: [
                { label: 'All GRNs', route: 'purchasing.grns.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput },
                { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet },
            ],
        },
        invoices: {
            label: 'Supplier Invoices',
            icon: FileSpreadsheet,
            routePrefix: 'purchasing.invoices',
            work: [
                { label: 'All Invoices', route: 'purchasing.invoices.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Goods Receiving', route: 'purchasing.grns.index', icon: PackageCheck },
                { label: 'Suppliers', route: 'suppliers.index', icon: Truck },
            ],
        },
        returns: {
            label: 'Returns to Supplier',
            icon: PackageX,
            routePrefix: 'purchasing.returns',
            work: [
                { label: 'All Returns', route: 'purchasing.returns.index', icon: LayoutList },
                { label: 'New Return', route: 'purchasing.returns.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Suppliers', route: 'suppliers.index', icon: Truck },
                { label: 'Supplier Invoices', route: 'purchasing.invoices.index', icon: FileSpreadsheet },
            ],
        },
    },
};

export default navConfig;
