// Workshop nav — content only (styling lives in ModuleLayout).
import {
    Wrench, KanbanSquare, ClipboardList, CarFront, Timer, HardHat,
    LayoutList, Plus, Car, Receipt, PackageSearch,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'workshop',
    moduleLabel: 'Workshop',
    moduleIcon: Wrench,

    module: [
        {
            section: 'Jobs',
            items: [
                { label: 'Technician Board', route: 'workshop.board', icon: KanbanSquare },
                { label: 'Job Cards', route: 'workshop.jobs.index', icon: ClipboardList },
            ],
        },
        {
            section: 'Registry',
            items: [
                { label: 'Vehicles', route: 'workshop.vehicles.index', icon: CarFront },
            ],
        },
        {
            section: 'Setup',
            items: [
                { label: 'Labour Codes', route: 'workshop.labour.index', icon: Timer },
                { label: 'Technicians', route: 'workshop.technicians.index', icon: HardHat },
            ],
        },
    ],

    subModules: {
        board: {
            label: 'Technician Board', icon: KanbanSquare, routePrefix: 'workshop.board',
            work: [{ label: 'Board', route: 'workshop.board', icon: KanbanSquare }],
            quickLinks: [
                { label: 'Job Cards', route: 'workshop.jobs.index', icon: ClipboardList },
                { label: 'Technicians', route: 'workshop.technicians.index', icon: HardHat },
            ],
        },
        jobs: {
            label: 'Job Cards', icon: ClipboardList, routePrefix: 'workshop.jobs',
            work: [
                { label: 'All Jobs', route: 'workshop.jobs.index', icon: LayoutList },
                { label: 'New Job', route: 'workshop.jobs.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Technician Board', route: 'workshop.board', icon: KanbanSquare },
                { label: 'Vehicles', route: 'workshop.vehicles.index', icon: CarFront },
                { label: 'Parts Catalogue', route: 'inventory.parts.index', icon: PackageSearch },
            ],
        },
        vehicles: {
            label: 'Vehicle Registry', icon: CarFront, routePrefix: 'workshop.vehicles',
            work: [
                { label: 'All Vehicles', route: 'workshop.vehicles.index', icon: LayoutList },
                { label: 'Register Vehicle', route: 'workshop.vehicles.create', icon: Plus },
            ],
            quickLinks: [
                { label: 'Job Cards', route: 'workshop.jobs.index', icon: ClipboardList },
                { label: 'Customers', route: 'customers.index', icon: Car },
            ],
        },
        labour: {
            label: 'Labour Codes', icon: Timer, routePrefix: 'workshop.labour',
            work: [{ label: 'Labour Codes', route: 'workshop.labour.index', icon: Timer }],
            quickLinks: [
                { label: 'Technicians', route: 'workshop.technicians.index', icon: HardHat },
                { label: 'Job Cards', route: 'workshop.jobs.index', icon: ClipboardList },
            ],
        },
        technicians: {
            label: 'Technicians', icon: HardHat, routePrefix: 'workshop.technicians',
            work: [{ label: 'Technicians', route: 'workshop.technicians.index', icon: HardHat }],
            quickLinks: [
                { label: 'Technician Board', route: 'workshop.board', icon: KanbanSquare },
                { label: 'Labour Codes', route: 'workshop.labour.index', icon: Timer },
            ],
        },
    },
};

export default navConfig;
