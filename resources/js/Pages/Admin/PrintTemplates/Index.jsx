import { Head } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import SettingsForm from '../SettingsForm';
export default function PrintTemplates({ settings }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Print Templates" breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Print Templates' }]}>
            <Head title="Print Templates" />
            <p className="text-sm text-slate-500 mb-4 max-w-2xl">Text that appears on printed documents. The company/branch identity and logo are set under Company Details and print through the shared header on every document.</p>
            <SettingsForm settings={settings} action={route('admin.print-templates.update')} submitLabel="Save templates" />
        </ModuleLayout>
    );
}
