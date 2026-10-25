// Inventory Management nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    ArrowLeftRight, ClipboardCheck, FileInput, FolderTree, Layers, ListOrdered, MapPin,
    PackageCheck, PackageSearch, RefreshCw, ScanBarcode, Search, Shuffle, SlidersHorizontal,
    Tags, Upload, BadgeCheck,
} from 'lucide-react';

export default {
    moduleKey: 'inventory',
    moduleLabel: 'Inventory Management',
    moduleIcon: PackageSearch,

    sections: [
        {
            section: 'Catalogue',
            items: [
                {
                    label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch,
                    children: [{ label: 'New Part', route: 'inventory.parts.create' }],
                    related: [
                        { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
                        { label: 'Cross-Reference', route: 'vehicle-ref.cross-ref', icon: Shuffle },
                        { label: 'Selling Price Lists', route: 'sales.price-lists.index', icon: ListOrdered },
                        { label: 'Data Upload', route: 'imports.index', icon: Upload },
                    ],
                },
                { label: 'Categories', route: 'inventory.categories.index', icon: FolderTree },
                { label: 'Brands', route: 'inventory.brands.index', icon: Tags },
                { label: 'Serial & Batch', route: 'inventory.serials.index', icon: ScanBarcode },
            ],
        },
        {
            section: 'Stock',
            items: [
                {
                    label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers,
                    related: [
                        { label: 'Goods Receiving', route: 'purchasing.grns.index', icon: PackageCheck },
                        { label: 'Data Upload', route: 'imports.index', icon: Upload },
                    ],
                },
                { label: 'Adjustments', route: 'inventory.adjustments.index', icon: SlidersHorizontal },
                { label: 'Transfers', route: 'inventory.transfers.index', icon: ArrowLeftRight },
                { label: 'Bin Locations', route: 'inventory.bins.index', icon: MapPin },
            ],
        },
        {
            section: 'Counting',
            items: [
                {
                    label: 'Stock Takes', route: 'inventory.stock-takes.index', icon: ClipboardCheck,
                    children: [{ label: 'New Stock Take', route: 'inventory.stock-takes.create' }],
                },
            ],
        },
        {
            section: 'Replenishment',
            items: [
                {
                    label: 'Reorder Report', route: 'inventory.reorder.index', icon: RefreshCw,
                    related: [
                        { label: 'Purchase Orders', route: 'purchasing.orders.index', icon: FileInput },
                        { label: 'Approved Suppliers', route: 'suppliers.approved.index', icon: BadgeCheck },
                    ],
                },
            ],
        },
    ],
};
