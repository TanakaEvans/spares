import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import FormField from '@/Components/FormField';
import StatusBadge from '@/Components/StatusBadge';

export default function BinsIndex({ bins, branches, filters }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        branch_id: branches[0]?.id ?? '', code: '', notes: '',
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function add(e) {
        e.preventDefault();
        post(route('inventory.bins.store'), { preserveScroll: true, onSuccess: () => reset('code', 'notes') });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Bin Locations"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Bin Locations' },
            ]}
        >
            <Head title="Bin Locations" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div className="lg:col-span-2 space-y-4">
                    <select
                        aria-label="Filter by branch"
                        value={filters.branch_id ?? ''}
                        onChange={(e) => router.get(route('inventory.bins.index'), e.target.value ? { branch_id: e.target.value } : {}, { preserveState: true })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <DataTable
                        columns={[
                            { key: 'code', label: 'Bin Code', render: (b) => <span className="font-mono font-semibold text-slate-800">{b.code}</span> },
                            { key: 'branch', label: 'Branch', render: (b) => <span className="text-slate-500">{b.branch?.name}</span> },
                            { key: 'notes', label: 'Notes', render: (b) => <span className="text-slate-400">{b.notes}</span> },
                            { key: 'is_active', label: 'Status', render: (b) => <StatusBadge status={b.is_active ? 'active' : 'inactive'} /> },
                        ]}
                        rows={bins.data}
                        pagination={bins}
                        emptyTitle="No bins yet"
                        emptyMessage="Map your warehouse: aisle-shelf-bin codes like A-01-01."
                    />
                </div>

                <form onSubmit={add} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Bin</h2>
                    <FormField label="Branch" htmlFor="bin-branch" required error={errors.branch_id}>
                        <select id="bin-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                            {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Bin code" htmlFor="bin-code" required error={errors.code} help="e.g. A-01-04 (aisle-shelf-bin)">
                        <input id="bin-code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} className={`${input} font-mono`} maxLength={30} />
                    </FormField>
                    <FormField label="Notes" htmlFor="bin-notes" error={errors.notes}>
                        <input id="bin-notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={input} />
                    </FormField>
                    <button
                        type="submit"
                        disabled={processing || !data.code}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                    >
                        <Plus className="w-4 h-4" />
                        Add Bin
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
