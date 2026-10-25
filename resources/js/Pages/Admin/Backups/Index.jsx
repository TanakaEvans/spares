import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { DatabaseBackup, Download, Clock, HardDrive } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';
import SearchInput from '@/Components/SearchInput';

export default function Backups({ files, latest, retentionDays, maxAgeHours, method, directory }) {
    const { post, processing } = useForm();
    const [search, setSearch] = useState('');
    const stale = latest ? latest.age_hours > maxAgeHours : true;
    const shown = search ? files.filter((f) => f.name.toLowerCase().includes(search.toLowerCase())) : files;

    return (
        <ModuleLayout navConfig={navConfig} title="Backup Management" breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Backup Management' }]}>
            <Head title="Backup Management" />
            <div className="max-w-3xl space-y-4">
                <div className={`flex flex-wrap items-center justify-between gap-3 rounded-xl border px-5 py-4 ${stale ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`}>
                    <div className="flex items-center gap-3">
                        <Clock className="w-5 h-5" />
                        <div>
                            <div className="text-sm font-semibold">
                                {latest ? `Newest backup ${latest.age_hours} h ago (${latest.at})` : 'No backup has been taken yet'}
                            </div>
                            <div className="text-xs opacity-80">
                                Nightly at 22:00 via the scheduler · kept {retentionDays} days · stale after {maxAgeHours} h · method: {method}
                            </div>
                        </div>
                    </div>
                    <button onClick={() => post(route('admin.backups.store'))} disabled={processing} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        <DatabaseBackup className="w-4 h-4" /> {processing ? 'Backing up…' : 'Backup now'}
                    </button>
                </div>

                <p className="flex items-start gap-2 text-xs text-slate-500">
                    <HardDrive className="w-3.5 h-3.5 mt-0.5 shrink-0" />
                    <span>Dumps are written to <code className="font-mono">{directory}</code>. A backup on the same disk is not a backup: download or sync the newest file to an external drive or cloud folder every day (operations/backup-and-resilience.md).</span>
                </p>

                {files.length > 5 && (
                    <SearchInput
                        id="backup-search"
                        value={search}
                        onSearch={setSearch}
                        placeholder="Filter by filename…"
                        className="max-w-sm"
                    />
                )}

                <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                    {shown.length === 0 ? (
                        <div className="py-10 text-center text-sm text-slate-400"><DatabaseBackup className="w-8 h-8 mx-auto mb-2 text-slate-300" />{files.length === 0 ? 'No backups yet.' : 'No backups match that name.'}</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">File</th><th className="px-2 py-2 text-right">Size</th><th className="px-4 py-2 text-right">Created</th><th className="px-4 py-2" /></tr></thead>
                            <tbody className="divide-y divide-slate-50">
                                {shown.map((f) => (
                                    <tr key={f.name}>
                                        <td className="px-4 py-2 font-mono text-slate-700">{f.name}</td>
                                        <td className="px-2 py-2 text-right tabular-nums text-slate-500">{f.size_kb} KB</td>
                                        <td className="px-4 py-2 text-right tabular-nums text-slate-500">{f.at}</td>
                                        <td className="px-4 py-2 text-right">
                                            <a href={route('admin.backups.download', f.name)} className="inline-flex items-center gap-1 text-xs font-medium text-orange-600 hover:text-orange-700"><Download className="w-3.5 h-3.5" /> Download</a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
