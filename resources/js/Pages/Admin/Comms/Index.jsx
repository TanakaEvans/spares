import { Head } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import SettingsForm from '../SettingsForm';
export default function Comms({ settings }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Email & SMS" breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Email & SMS' }]}>
            <Head title="Email & SMS" />
            <p className="text-sm text-slate-500 mb-4 max-w-2xl">Delivery identity for emailed documents and SMS notifications. SMS requires a gateway to be wired in operations.</p>
            <SettingsForm settings={settings} action={route('admin.comms.update')} submitLabel="Save settings" />
        </ModuleLayout>
    );
}
