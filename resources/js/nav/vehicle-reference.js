// Vehicle Reference nav — content only (component-standards §1a).
import {
    Car, Cog, Factory, LayoutList, PackageSearch, Plus, Search, Shuffle,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'vehicle-reference',
    moduleLabel: 'Vehicle Reference',
    moduleIcon: Car,

    module: [
        {
            section: 'Lookup',
            items: [
                { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
                { label: 'Cross-Reference', route: 'vehicle-ref.cross-ref', icon: Shuffle },
            ],
        },
        {
            section: 'Data',
            items: [
                { label: 'Makes', route: 'vehicle-ref.makes.index', icon: Factory },
                { label: 'Models & Variants', route: 'vehicle-ref.models.index', icon: Car },
                { label: 'Engine Codes', route: 'vehicle-ref.engines.index', icon: Cog },
            ],
        },
    ],

    subModules: {
        fitment: {
            label: 'Fitment Lookup',
            icon: Search,
            routePrefix: 'vehicle-ref.fitment',
            work: [
                { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
            ],
            quickLinks: [
                { label: 'Cross-Reference', route: 'vehicle-ref.cross-ref', icon: Shuffle },
                { label: 'Models & Variants', route: 'vehicle-ref.models.index', icon: Car },
                { label: 'Inventory: Parts', route: 'inventory.parts.index', icon: PackageSearch },
            ],
        },
        crossref: {
            label: 'Cross-Reference',
            icon: Shuffle,
            routePrefix: 'vehicle-ref.cross-ref',
            work: [
                { label: 'Number Search', route: 'vehicle-ref.cross-ref', icon: Search },
            ],
            quickLinks: [
                { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
                { label: 'Inventory: Parts', route: 'inventory.parts.index', icon: PackageSearch },
            ],
        },
        makes: {
            label: 'Vehicle Makes',
            icon: Factory,
            routePrefix: 'vehicle-ref.makes',
            work: [
                { label: 'All Makes', route: 'vehicle-ref.makes.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Models & Variants', route: 'vehicle-ref.models.index', icon: Car },
                { label: 'Engine Codes', route: 'vehicle-ref.engines.index', icon: Cog },
            ],
        },
        models: {
            label: 'Models & Variants',
            icon: Car,
            routePrefix: 'vehicle-ref.models',
            work: [
                { label: 'All Models', route: 'vehicle-ref.models.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Makes', route: 'vehicle-ref.makes.index', icon: Factory },
                { label: 'Engine Codes', route: 'vehicle-ref.engines.index', icon: Cog },
                { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
            ],
        },
        engines: {
            label: 'Engine Codes',
            icon: Cog,
            routePrefix: 'vehicle-ref.engines',
            work: [
                { label: 'All Engine Codes', route: 'vehicle-ref.engines.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Models & Variants', route: 'vehicle-ref.models.index', icon: Car },
                { label: 'Makes', route: 'vehicle-ref.makes.index', icon: Factory },
            ],
        },
    },
};

export default navConfig;
