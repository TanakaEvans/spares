// Sales & POS module nav — content only (styling lives in ModuleLayout).
import {
    ShoppingCart, Receipt, Undo2, FileText, ClipboardList, ListOrdered,
    LayoutList, Plus, ScanBarcode, Users, PackageSearch, Layers, Banknote,
    Percent, CalendarClock, Truck,
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
                { label: 'Lay-bys', route: 'sales.laybys.index', icon: CalendarClock },
            ],
        },
        {
            section: 'Documents',
            items: [
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                { label: 'Credit Notes', route: 'sales.credit-notes.index', icon: Undo2 },
                { label: 'Delivery Notes', route: 'sales.delivery-notes.index', icon: Truck },
            ],
        },
        {
            section: 'Setup',
            items: [
                { label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered },
                { label: 'Promotions', route: 'sales.promotions.index', icon: Percent },
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
                { label: 'Daily Cash-up', route: 'reports.cashup', icon: Banknote },
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
                { label: 'New Order', route: 'sales.orders.create', icon: Plus },
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
        laybys: {
            label: 'Lay-bys',
            icon: CalendarClock,
            routePrefix: 'sales.laybys',
            work: [
                { label: 'All Lay-bys', route: 'sales.laybys.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
                { label: 'Customers', route: 'customers.index', icon: Users },
            ],
        },
        promotions: {
            label: 'Promotions',
            icon: Percent,
            routePrefix: 'sales.promotions',
            work: [
                { label: 'All Promotions', route: 'sales.promotions.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Price Lists', route: 'sales.price-lists.index', icon: ListOrdered },
                { label: 'Point of Sale', route: 'sales.pos', icon: ScanBarcode },
            ],
        },
        deliveryNotes: {
            label: 'Delivery Notes',
            icon: Truck,
            routePrefix: 'sales.delivery-notes',
            work: [
                { label: 'All Delivery Notes', route: 'sales.delivery-notes.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Sales Orders', route: 'sales.orders.index', icon: ClipboardList },
                { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
            ],
        },
    },
};

export default navConfig;
