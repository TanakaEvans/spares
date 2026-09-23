import { Head, Link, router } from '@inertiajs/react';
import { GitBranch, ArrowRight } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function SupersessionsIndex({ rows, filters }) {
    return (
        <ModuleLayout navConfig={navConfig} title="Supersessions"
            breadcrumbs={[{ label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') }, { label: 'Supersession Management' }]}>
            <Head title="Supersessions" />
            <div className="space-y-4">
                <p className="text-sm text-slate-500">When a manufacturer replaces a part number, record it here — searches for the old number resolve to the replacement automatically.</p>
                <SearchInput id="ss-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('vehicle-ref.supersessions.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Old or new part number…" className="w-72" />
                <DataTable
                    columns={[
                        { key: 'old', label: 'Superseded (old)', render: (r) => <Link href={route('inventory.parts.show', r.old_part_id)} onClick={(e) => e.stopPropagation()} className="font-mono font-semibold text-slate-500 line-through hover:text-orange-600">{r.old_number}</Link> },
                        { key: 'arrow', label: '', render: () => <ArrowRight className="w-4 h-4 text-slate-300" /> },
                        { key: 'new', label: 'Replaced by (new)', render: (r) => <Link href={route('inventory.parts.show', r.new_part_id)} onClick={(e) => e.stopPropagation()} className="font-mono font-semibold text-slate-800 hover:text-orange-600">{r.new_number}</Link> },
                        { key: 'reason', label: 'Reason', render: (r) => <span className="text-slate-500">{r.reason ?? '—'}</span> },
                        { key: 'is_active', label: 'Status', render: (r) => <StatusBadge status={r.is_active ? 'active' : 'inactive'} /> },
                    ]}
                    rows={rows.data}
                    pagination={rows}
                    emptyTitle="No supersessions"
                    emptyMessage="Record a replaced part number to auto-redirect old-number searches."
                    emptyIcon={GitBranch}
                />
            </div>
        </ModuleLayout>
    );
}
