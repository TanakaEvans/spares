import { Head } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import SettingsForm from '../SettingsForm';
export default function PasswordPolicy({ settings }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Password Policy" breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Password Policy' }]}>
            <Head title="Password Policy" />
            <p className="text-sm text-slate-500 mb-4 max-w-2xl">Rules enforced when users set or change passwords, and the failed-login lockout.</p>
            <SettingsForm settings={settings} action={route('admin.password-policy.update')} submitLabel="Save policy" />
        </ModuleLayout>
    );
}
