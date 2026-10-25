// Vehicle Reference nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    AlertTriangle, Car, CarFront, Cog, Factory, GitBranch, PackageSearch, Search, Shuffle, Upload,
} from 'lucide-react';

export default {
    moduleKey: 'vehicle-reference',
    moduleLabel: 'Vehicle Reference',
    moduleIcon: Car,

    sections: [
        {
            section: 'Lookup',
            items: [
                {
                    label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search,
                    related: [
                        { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                        { label: 'Vehicle Registry', route: 'workshop.vehicles.index', icon: CarFront },
                    ],
                },
                {
                    label: 'Cross-Reference', route: 'vehicle-ref.cross-ref', icon: Shuffle,
                    related: [{ label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch }],
                },
            ],
        },
        {
            section: 'Vehicles',
            items: [
                { label: 'Makes', route: 'vehicle-ref.makes.index', icon: Factory },
                { label: 'Models & Variants', route: 'vehicle-ref.models.index', icon: Car },
                { label: 'Engine Codes', route: 'vehicle-ref.engines.index', icon: Cog },
            ],
        },
        {
            section: 'Part Data',
            items: [
                {
                    label: 'Supersessions', route: 'vehicle-ref.supersessions.index', icon: GitBranch,
                    related: [
                        { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                        { label: 'Catalogue Upload', route: 'imports.index', icon: Upload },
                    ],
                },
                { label: 'Technical Bulletins', route: 'vehicle-ref.bulletins.index', icon: AlertTriangle },
            ],
        },
    ],
};
