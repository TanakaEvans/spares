// Workshop nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    CarFront, ClipboardList, HardHat, KanbanSquare, PackageSearch, PackageX, Receipt,
    Search, ShieldCheck, Timer, Truck, Users, Wrench,
} from 'lucide-react';

export default {
    moduleKey: 'workshop',
    moduleLabel: 'Workshop',
    moduleIcon: Wrench,

    sections: [
        {
            section: 'Jobs',
            items: [
                { label: 'Technician Board', route: 'workshop.board', icon: KanbanSquare },
                {
                    label: 'Job Cards', route: 'workshop.jobs.index', icon: ClipboardList,
                    children: [{ label: 'New Job', route: 'workshop.jobs.create' }],
                    related: [
                        { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                        { label: 'Customers', route: 'customers.index', icon: Users },
                        { label: 'Tax Invoices', route: 'sales.invoices.index', icon: Receipt },
                    ],
                },
                {
                    label: 'Warranty Claims', route: 'workshop.warranty.index', icon: ShieldCheck,
                    related: [
                        { label: 'Suppliers', route: 'suppliers.index', icon: Truck },
                        { label: 'Returns to Supplier', route: 'purchasing.returns.index', icon: PackageX },
                    ],
                },
            ],
        },
        {
            section: 'Vehicles',
            items: [
                {
                    label: 'Vehicle Registry', route: 'workshop.vehicles.index', icon: CarFront,
                    children: [{ label: 'Register Vehicle', route: 'workshop.vehicles.create' }],
                    related: [
                        { label: 'Customers', route: 'customers.index', icon: Users },
                        { label: 'Fitment Lookup', route: 'vehicle-ref.fitment', icon: Search },
                    ],
                },
            ],
        },
        {
            section: 'Setup',
            items: [
                { label: 'Technicians', route: 'workshop.technicians.index', icon: HardHat },
                { label: 'Labour Codes', route: 'workshop.labour.index', icon: Timer },
            ],
        },
    ],
};
