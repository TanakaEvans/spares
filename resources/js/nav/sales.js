// Sales & POS module nav — content only (styling lives in ModuleLayout).
import {
    ShoppingCart, Receipt, Undo2, FileText, ClipboardList, ListOrdered,
    LayoutList, Plus, ScanBarcode, Users, PackageSearch, Layers,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'sales',
    moduleLabel: 'Sales & POS',
    moduleIcon: ShoppingCart,

    module: [
        {
            section: 'Sell',
            items: [
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
                { label: 'Quotations', route: 'sales.quotes.index', icon: FileText },
                { label: 'Sales Orders', route: 'sales.orders.index', icon: ClipboardList },
            ],
        },
        {
            section: 'Documents',
            items: [
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                { label: 'Credit Notes', route: 'sales.credit-notes.index', icon: Undo2 },
            ],
        },
        {
            section: 'Setup',
            items: [
                { label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered },
            ],
        },
    ],

    subModules: {
        pos: {
            label: 'Point of Sale',
            icon: ScanBarcode,
            routePrefix: 'sales.pos',
            work: [
                { label: 'New Sale', route: 'sales.pos', icon: ScanBarcode },
            ],
            quickLinks: [
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                { label: 'Customers', route: 'customers.index', icon: Users },
                { label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered },
            ],
        },
        invoices: {
            label: 'Tax Invoices',
            icon: Receipt,
            routePrefix: 'sales.invoices',
            work: [
                { label: 'All Invoices', route: 'sales.invoices.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
                { label: 'Credit Notes', route: 'sales.credit-notes.index', icon: Undo2 },
                { label: 'Customers', route: 'customers.index', icon: Users },
            ],
        },
        creditNotes: {
            label: 'Credit Notes',
            icon: Undo2,
            routePrefix: 'sales.credit-notes',
            work: [
                { label: 'All Credit Notes', route: 'sales.credit-notes.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
            ],
        },
        quotes: {
            label: 'Quotations',
            icon: FileText,
            routePrefix: 'sales.quotes',
            work: [
                { label: 'All Quotes', route: 'sales.quotes.index', icon: LayoutList },
                { label: 'New Quote', route: 'sales.quotes.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Sales Orders', route: 'sales.orders.index', icon: ClipboardList },
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
                { label: 'Customers', route: 'customers.index', icon: Users },
            ],
        },
        orders: {
            label: 'Sales Orders',
            icon: ClipboardList,
            routePrefix: 'sales.orders',
            work: [
                { label: 'All Orders', route: 'sales.orders.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Quotations', route: 'sales.quotes.index', icon: FileText },
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
            ],
        },
        priceLists: {
            label: 'Price Lists',
            icon: ListOrdered,
            routePrefix: 'sales.price-lists',
            work: [
                { label: 'All Price Lists', route: 'sales.price-lists.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Parts Catalogue', route: 'inventory.parts.index', icon: PackageSearch },
                { label: 'Customers', route: 'customers.index', icon: Users },
            ],
        },
    },
};

export default navConfig;
