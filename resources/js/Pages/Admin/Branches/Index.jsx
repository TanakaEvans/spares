import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';
import { Building2, Plus } from 'lucide-react';

export default function BranchesIndex({ auth, branches, filters = {} }) {
    const filter = useListFilter('admin.branches.index', filters);

    const handleDelete = (branch) => {
        if (confirm(`Are you sure you want to delete "${branch.name}"?`)) {
            router.delete(route('admin.branches.destroy', branch.id));
        }
    };

    const handleToggleStatus = (branch) => {
        router.patch(route('admin.branches.toggle-status', branch.id));
    };

    return (
        <AdminLayout title="Branches" auth={auth}>
            <Head title="Branches" />

            <div className="space-y-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">Branches</h2>
                        <p className="text-gray-600">Manage company branches and locations</p>
                    </div>
                    <Link
                        href={route('admin.branches.create')}
                        className="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors flex items-center gap-2"
                    >
                        <Plus className="w-4 h-4" />
                        Add Branch
                    </Link>
                </div>

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Name, code, phone or company…"
                    searchClass="w-64"
                />

                <DataTable
                    columns={[
                        { key: 'name', label: 'Branch', sortable: 'name', render: (b) => (
                            <div className="flex items-center">
                                <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <Building2 className="w-5 h-5 text-orange-600" />
                                </div>
                                <div className="ml-4">
                                    <div className="text-sm font-medium text-gray-900 flex items-center gap-2">
                                        {b.name}
                                        {b.is_main_branch && <span className="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">Main</span>}
                                    </div>
                                    <div className="text-sm text-gray-500">{b.city}</div>
                                </div>
                            </div>
                        ) },
                        { key: 'code', label: 'Code', render: (b) => <span className="px-2 py-1 text-xs font-mono bg-gray-100 rounded">{b.code}</span> },
                        { key: 'contact', label: 'Contact', render: (b) => (
                            <div><div className="text-sm text-gray-900">{b.phone || '-'}</div><div className="text-sm text-gray-500">{b.email || '-'}</div></div>
                        ) },
                        { key: 'employees', label: 'Employees', render: (b) => <span className="text-sm text-gray-900">{b.employees_count || 0}</span> },
                        { key: 'status', label: 'Status', render: (b) => (
                            <button onClick={(e) => { e.stopPropagation(); handleToggleStatus(b); }}
                                className={`px-3 py-1 text-xs font-semibold rounded-full ${b.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                {b.status}
                            </button>
                        ) },
                        { key: 'actions', label: '', align: 'right', render: (b) => (
                            <div className="flex justify-end gap-2 text-sm font-medium" onClick={(e) => e.stopPropagation()}>
                                <Link href={route('admin.branches.show', b.id)} className="text-gray-600 hover:text-gray-900">View</Link>
                                <Link href={route('admin.branches.edit', b.id)} className="text-orange-600 hover:text-orange-900">Edit</Link>
                                <button onClick={() => handleDelete(b)} className="text-red-600 hover:text-red-900">Delete</button>
                            </div>
                        ) },
                    ]}
                    rows={branches.data}
                    pagination={branches}
                    sorting={{ filters, onFilter: filter }}
                    emptyTitle="No branches found"
                    emptyMessage="Create your first branch to get started."
                    emptyIcon={Building2}
                />
            </div>
        </AdminLayout>
    );
}
