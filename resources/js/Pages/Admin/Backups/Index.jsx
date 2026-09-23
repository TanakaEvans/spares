import { Head, useForm } from '@inertiajs/react';
import { DatabaseBackup } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

export default function Backups({ files }) {
    const { post, processing } = useForm();
    return (
        <ModuleLayout navConfig={navConfig} title="Backup Management" breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Backup Management' }]}>
            <Head title="Backup Management" />
            <div className="max-w-3xl space-y-4">
                <div className="flex items-center justify-between">
                    <p className="text-sm text-slate-500 max-w-xl">Database snapshots. Schedule and off-site copies are configured in operations; use this to trigger and review manual backups.</p>
                    <button onClick={() => post(route('admin.backups.store'))} disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        <DatabaseBackup className="w-4 h-4" /> {processing ? 'Backing up…' : 'Backup now'}
                    </button>
                </div>
                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {files.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><DatabaseBackup className="w-8 h-8 mx-auto mb-2 text-slate-300" />No backups yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">File</th><th className="px-2 py-2 text-right">Size</th><th className="px-4 py-2 text-right">Created</th></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {files.map((f) => (
                                    <tr key={f.name}><td className="px-4 py-2 font-mono text-slate-700">{f.name}</td><td className="px-2 py-2 text-right tabular-nums text-slate-500">{f.size_kb} KB</td><td className="px-4 py-2 text-right tabular-nums text-slate-500">{f.at}</td></tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
