// Inventory Management nav — content only (component-standards §1a).
import {
    Car, FolderTree, Layers, LayoutList, MapPin, PackageSearch,
    Plus, Search, Shuffle, Tags,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'inventory',
    moduleLabel: 'Inventory Management',
    moduleIcon: PackageSearch,

    module: [
        {
            section: 'Catalogue',
            items: [
                { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                { label: 'Categories', route: 'inventory.categories.index', icon: FolderTree },
                { label: 'Brands', route: 'inventory.brands.index', icon: Tags },
            ],
        },
        {
            section: 'Stock',
            items: [
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
                { label: 'Bin Locations', route: 'inventory.bins.index', icon: MapPin },
            ],
        },
    ],

    subModules: {
        parts: {
            label: 'Parts Catalogue',
            icon: PackageSearch,
            routePrefix: 'inventory.parts',
            work: [
                { label: 'All Parts', route: 'inventory.parts.index', icon: LayoutList },
                { label: 'New Part', route: 'inventory.parts.create', icon: Plus },
            ],
            setup: [
                { label: 'Categories', route: 'inventory.categories.index', icon: FolderTree },
                { label: 'Brands', route: 'inventory.brands.index', icon: Tags },
            ],
            quickLinks: [
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
                { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
                { label: 'Cross-Reference', route: 'vehicle-ref.cross-ref', icon: Shuffle },
            ],
        },
        categories: {
            label: 'Categories',
            icon: FolderTree,
            routePrefix: 'inventory.categories',
            work: [
                { label: 'Category Tree', route: 'inventory.categories.index', icon: FolderTree },
            ],
            quickLinks: [
                { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                { label: 'Brands', route: 'inventory.brands.index', icon: Tags },
            ],
        },
        brands: {
            label: 'Brands',
            icon: Tags,
            routePrefix: 'inventory.brands',
            work: [
                { label: 'All Brands', route: 'inventory.brands.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                { label: 'Categories', route: 'inventory.categories.index', icon: FolderTree },
            ],
        },
        stock: {
            label: 'Stock Levels',
            icon: Layers,
            routePrefix: 'inventory.stock',
            work: [
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
            ],
            quickLinks: [
                { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                { label: 'Bin Locations', route: 'inventory.bins.index', icon: MapPin },
            ],
        },
        bins: {
            label: 'Bin Locations',
            icon: MapPin,
            routePrefix: 'inventory.bins',
            work: [
                { label: 'All Bins', route: 'inventory.bins.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: Layers },
                { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
            ],
        },
    },
};

export default navConfig;
