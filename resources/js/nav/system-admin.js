// System Administration nav config — CONTENT ONLY, the shared ModuleLayout
// owns all styling (component-standards §1a). Level-2 module menu +
// Level-3 per-sub-module contextual sidebars (docs/design/sidebars/).
import {
    Building2, Coins, FolderKanban, Hash, History, LayoutList,
    MapPin, ScrollText, Settings, SlidersHorizontal, UserCog, Users, HeartPulse, Upload, Boxes,
} from 'lucide-react';

const navConfig = {
    moduleKey: 'system-admin',
    moduleLabel: 'System Administration',
    moduleIcon: Settings,

    module: [
        {
            section: 'Organisation',
            items: [
                { label: 'Company Details', route: 'admin.company.index', icon: Building2 },
                { label: 'Data Upload', route: 'imports.index', icon: Upload },
                { label: 'Branches', route: 'admin.branches.index', icon: MapPin },
                { label: 'Departments', route: 'admin.departments.index', icon: FolderKanban },
            ],
        },
        {
            section: 'Access',
            items: [
                { label: 'Users', route: 'auth.users.index', icon: Users },
                { label: 'Roles & Permissions', route: 'auth.roles.index', icon: UserCog },
            ],
        },
        {
            section: 'Configuration',
            items: [
                { label: 'Configuration Centre', route: 'admin.settings.index', icon: SlidersHorizontal },
                { label: 'Currencies & Rates', route: 'admin.currencies.index', icon: Coins },
                { label: 'Number Sequences', route: 'admin.sequences.index', icon: Hash },
            ],
        },
        {
            section: 'Governance',
            items: [
                { label: 'System Health', route: 'admin.health', icon: HeartPulse },
                { label: 'Activity Logs', route: 'system.logs', icon: ScrollText },
            ],
        },
    ],

    subModules: {
        dataUpload: {
            label: 'Data Upload',
            icon: Upload,
            routePrefix: 'imports.',
            work: [
                { label: 'Upload Hub', route: 'imports.index', icon: Upload },
            ],
            quickLinks: [
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: Boxes },
                { label: 'Parts', route: 'inventory.parts.index', icon: LayoutList },
                { label: 'Suppliers', route: 'suppliers.index', icon: LayoutList },
                { label: 'Customers', route: 'customers.index', icon: Users },
                { label: 'System Health', route: 'admin.health', icon: HeartPulse },
            ],
        },
        logs: {
            label: 'Activity Log',
            icon: ScrollText,
            routePrefix: 'system.logs',
            work: [
                { label: 'Activity Log', route: 'system.logs', icon: ScrollText },
            ],
            quickLinks: [
                { label: 'System Health', route: 'admin.health', icon: HeartPulse },
                { label: 'Journals', route: 'finance.journals.index', icon: LayoutList },
            ],
        },
        health: {
            label: 'System Health',
            icon: HeartPulse,
            routePrefix: 'admin.health',
            work: [
                { label: 'Integrity Dashboard', route: 'admin.health', icon: HeartPulse },
            ],
            quickLinks: [
                { label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: LayoutList },
                { label: 'Stock Levels', route: 'inventory.stock.index', icon: LayoutList },
                { label: 'Configuration Centre', route: 'admin.settings.index', icon: SlidersHorizontal },
            ],
        },
        settings: {
            label: 'Configuration Centre',
            icon: SlidersHorizontal,
            routePrefix: 'admin.settings',
            work: [
                { label: 'All Settings', route: 'admin.settings.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Currencies & Rates', route: 'admin.currencies.index', icon: Coins },
                { label: 'Number Sequences', route: 'admin.sequences.index', icon: Hash },
                { label: 'Company Details', route: 'admin.company.index', icon: Building2 },
                { label: 'Branches', route: 'admin.branches.index', icon: MapPin },
            ],
        },
        currencies: {
            label: 'Currencies & Rates',
            icon: Coins,
            routePrefix: 'admin.currencies',
            work: [
                { label: 'Currencies & Rates', route: 'admin.currencies.index', icon: Coins },
            ],
            quickLinks: [
                { label: 'Configuration Centre', route: 'admin.settings.index', icon: SlidersHorizontal },
                { label: 'Number Sequences', route: 'admin.sequences.index', icon: Hash },
                { label: 'Company Details', route: 'admin.company.index', icon: Building2 },
            ],
        },
        sequences: {
            label: 'Number Sequences',
            icon: Hash,
            routePrefix: 'admin.sequences',
            work: [
                { label: 'All Sequences', route: 'admin.sequences.index', icon: LayoutList },
            ],
            quickLinks: [
                { label: 'Configuration Centre', route: 'admin.settings.index', icon: SlidersHorizontal },
                { label: 'Currencies & Rates', route: 'admin.currencies.index', icon: Coins },
                { label: 'Activity Logs', route: 'system.logs', icon: History },
            ],
        },
    },
};

export default navConfig;
