// System Administration nav — content only (component-standards §1a).
// Shape + rules: docs/design/navigation-and-layout.md §2.
import {
    Bell, Boxes, Building2, Coins, DatabaseBackup, FileBarChart, FolderKanban, Hash, HeartPulse,
    History, IdCard, KeyRound, LayoutList, Mail, MapPin, PackageSearch, Printer, ScrollText,
    Settings, SlidersHorizontal, UserCog, UserMinus, UserPlus, Users, Upload, Layers3, ShieldAlert,
} from 'lucide-react';

export default {
    moduleKey: 'system-admin',
    moduleLabel: 'System Administration',
    moduleIcon: Settings,

    sections: [
        {
            section: 'Organisation',
            items: [
                { label: 'Company Details', route: 'admin.company.index', icon: Building2 },
                { label: 'Branches', route: 'admin.branches.index', icon: MapPin },
                { label: 'Departments', route: 'admin.departments.index', icon: FolderKanban },
                { label: 'Sections', route: 'admin.sections.index', icon: Layers3 },
                { label: 'Employees', route: 'admin.employees.index', icon: IdCard },
            ],
        },
        {
            section: 'Users & Access',
            items: [
                { label: 'Users', route: 'auth.users.index', icon: Users },
                {
                    label: 'Roles & Permissions', route: 'auth.roles.index', icon: UserCog,
                    children: [
                        { label: 'Bulk Assign', route: 'auth.roles.bulk-assign', icon: UserPlus },
                        { label: 'Bulk Remove', route: 'auth.roles.bulk-remove', icon: UserMinus },
                        { label: 'Users by Role', route: 'auth.roles.users-report', icon: FileBarChart },
                    ],
                },
                { label: 'Password Resets', route: 'auth.management', icon: ShieldAlert },
                { label: 'Password Policy', route: 'admin.password-policy.index', icon: KeyRound },
            ],
        },
        {
            section: 'Configuration',
            items: [
                { label: 'Configuration Centre', route: 'admin.settings.index', icon: SlidersHorizontal },
                { label: 'Currencies & Rates', route: 'admin.currencies.index', icon: Coins },
                { label: 'Number Sequences', route: 'admin.sequences.index', icon: Hash },
                { label: 'Print Templates', route: 'admin.print-templates.index', icon: Printer },
                { label: 'Email & SMS', route: 'admin.comms.index', icon: Mail },
                { label: 'Notifications', route: 'admin.notifications-centre.index', icon: Bell },
            ],
        },
        {
            section: 'Data',
            items: [
                {
                    label: 'Data Upload', route: 'imports.index', icon: Upload, match: 'imports',
                    related: [
                        { label: 'Parts', route: 'inventory.parts.index', icon: PackageSearch },
                        { label: 'Stock Levels', route: 'inventory.stock.index', icon: Boxes },
                    ],
                },
            ],
        },
        {
            section: 'Governance',
            items: [
                {
                    label: 'System Health', route: 'admin.health', icon: HeartPulse,
                    related: [{ label: 'Trial Balance', route: 'finance.reports.trial-balance', icon: LayoutList }],
                },
                { label: 'Audit Trail', route: 'admin.audit-log.index', icon: History },
                { label: 'Activity Logs', route: 'system.logs', icon: ScrollText },
                { label: 'Backups', route: 'admin.backups.index', icon: DatabaseBackup },
            ],
        },
    ],
};
