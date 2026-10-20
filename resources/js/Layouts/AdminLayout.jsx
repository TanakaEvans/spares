import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

/**
 * Legacy System-Administration pages (Company, Users, Roles, Branches,
 * Departments, Sections) were built on a bespoke cyan-themed sidebar. They now
 * delegate to the shared ModuleLayout with the System Administration nav, so
 * they wear the one sidebar theme — the same dark slate shell, orange accent
 * and module icon as every other module (component-standards §1a).
 *
 * The old `auth` / `flash` props are accepted for backwards compatibility and
 * ignored: ModuleLayout reads the signed-in user and flash messages itself.
 */
export default function AdminLayout({ children, title = 'System Administration', breadcrumbs }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title={title}
            breadcrumbs={
                breadcrumbs ?? [
                    { label: 'System Administration', href: route('modules.show', 'system-admin') },
                    { label: title },
                ]
            }
        >
            {children}
        </ModuleLayout>
    );
}
