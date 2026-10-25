import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import FormField from '@/Components/FormField';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useListFilter } from '@/lib/listFilters';

export default function BinsIndex({ bins, branches, filters }) {
    const filter = useListFilter('inventory.bins.index', filters);
    const [editing, setEditing] = useState(null);   // bin being edited
    const [deleting, setDeleting] = useState(null); // bin pending delete
    const { data, setData, post, processing, errors, reset } = useForm({
        branch_id: branches[0]?.id ?? '', code: '', notes: '',
    });
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function add(e) {
        e.preventDefault();
        post(route('inventory.bins.store'), { preserveScroll: true, onSuccess: () => reset('code', 'notes') });
    }

    function destroyBin() {
        router.delete(route('inventory.bins.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
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
                    <ListToolbar
                        filters={filters}
                        onFilter={filter}
                        searchPlaceholder="Bin code…"
                        searchClass="w-56"
                    >
                        <select
                            aria-label="Filter by branch"
                            value={filters.branch_id ?? ''}
                            onChange={(e) => filter({ branch_id: e.target.value })}
                            className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">All branches</option>
                            {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </ListToolbar>
                    <DataTable
                        columns={[
                            { key: 'code', label: 'Bin Code', sortable: 'code', render: (b) => <EntityLink type="bin" id={b.id} label={b.code} mono className="font-semibold" /> },
                            { key: 'branch', label: 'Branch', render: (b) => <EntityLink type="branch" id={b.branch_id} label={b.branch?.name} muted /> },
                            { key: 'notes', label: 'Notes', render: (b) => <span className="text-slate-400">{b.notes}</span> },
                            { key: 'is_active', label: 'Status', render: (b) => <StatusBadge status={b.is_active ? 'active' : 'inactive'} /> },
                            { key: 'actions', label: '', align: 'right', render: (b) => (
                                <span className="inline-flex gap-1" onClick={(e) => e.stopPropagation()}>
                                    <button
                                        onClick={() => setEditing(b)}
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-orange-600 hover:bg-orange-50"
                                        aria-label={`Edit bin ${b.code}`}
                                    >
                                        <Pencil className="w-4 h-4" />
                                    </button>
                                    <button
                                        onClick={() => setDeleting(b)}
                                        className="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50"
                                        aria-label={`Delete bin ${b.code}`}
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </span>
                            ) },
                        ]}
                        rows={bins.data}
                        pagination={bins}
                        sorting={{ filters, onFilter: filter }}
                        rowHref={(b) => route('inventory.bins.show', b.id)}
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

            {editing && <EditBinDialog bin={editing} onClose={() => setEditing(null)} input={input} />}

            <ConfirmDialog
                open={deleting !== null}
                title={`Delete bin ${deleting?.code}?`}
                message="Bins with stock history are deactivated instead of deleted — empty bins are removed outright."
                confirmLabel="Delete"
                destructive
                onConfirm={destroyBin}
                onCancel={() => setDeleting(null)}
            />
        </ModuleLayout>
    );
}

function EditBinDialog({ bin, onClose, input }) {
    const [form, setForm] = useState({ code: bin.code, notes: bin.notes ?? '', is_active: bin.is_active });
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    function save(e) {
        e.preventDefault();
        setSaving(true);
        router.patch(route('inventory.bins.update', bin.id), form, {
            preserveScroll: true,
            onSuccess: onClose,
            onError: setErrors,
            onFinish: () => setSaving(false),
        });
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label={`Edit bin ${bin.code}`}>
            <div className="absolute inset-0 bg-slate-900/50" onClick={onClose} />
            <form onSubmit={save} className="relative w-full max-w-md rounded-xl bg-white shadow-xl p-6 space-y-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-slate-900">Edit bin <span className="font-mono">{bin.code}</span></h2>
                    <button type="button" onClick={onClose} className="p-1 text-slate-400 hover:text-slate-600" aria-label="Close"><X className="w-5 h-5" /></button>
                </div>
                <FormField label="Bin code" htmlFor="edit-bin-code" required error={errors.code}>
                    <input id="edit-bin-code" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} className={`${input} font-mono`} maxLength={30} autoFocus />
                </FormField>
                <FormField label="Notes" htmlFor="edit-bin-notes" error={errors.notes}>
                    <input id="edit-bin-notes" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} className={input} />
                </FormField>
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="rounded border-slate-300 text-orange-600" />
                    Active — inactive bins can't be picked as putaway destinations
                </label>
                <div className="flex justify-end gap-3 pt-2">
                    <button type="button" onClick={onClose} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" disabled={saving || !form.code} className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        {saving ? 'Saving…' : 'Save changes'}
                    </button>
                </div>
            </form>
        </div>
    );
}
