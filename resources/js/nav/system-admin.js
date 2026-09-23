// System Administration nav config — CONTENT ONLY, the shared ModuleLayout
// owns all styling (component-standards §1a). Level-2 module menu +
// Level-3 per-sub-module contextual sidebars (docs/design/sidebars/).
import {
    Building2, Coins, FolderKanban, Hash, History, LayoutList,
    MapPin, ScrollText, Settings, SlidersHorizontal, UserCog, Users, HeartPulse,
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
