import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import { useListFilter } from '@/lib/listFilters';
import { Store, Plus } from 'lucide-react';

export default function DepartmentsIndex({ auth, departments, filters = {} }) {
    const filter = useListFilter('admin.departments.index', filters);

    const handleDelete = (department) => {
        if (confirm(`Are you sure you want to delete "${department.name}"?`)) {
            router.delete(route('admin.departments.destroy', department.id));
        }
    };

    const handleToggleStatus = (department) => {
        router.patch(route('admin.departments.toggle-status', department.id));
    };

    return (
        <AdminLayout title="Departments" auth={auth}>
            <Head title="Departments" />

            <div className="space-y-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">Departments</h2>
                        <p className="text-gray-600">Manage organizational departments</p>
                    </div>
                    <Link
                        href={route('admin.departments.create')}
                        className="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors flex items-center gap-2"
                    >
                        <Plus className="w-4 h-4" />
                        Add Department
                    </Link>
                </div>

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Name, code or branch…"
                    searchClass="w-64"
                />

                <DataTable
                    columns={[
                        { key: 'name', label: 'Department', sortable: 'name', render: (d) => (
                            <div className="flex items-center">
                                <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <Store className="w-5 h-5 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <div className="text-sm font-medium text-gray-900">{d.name}</div>
                                    {d.description && <div className="text-sm text-gray-500 truncate max-w-xs">{d.description}</div>}
                                </div>
                            </div>
                        ) },
                        { key: 'code', label: 'Code', render: (d) => <span className="px-2 py-1 text-xs font-mono bg-gray-100 rounded">{d.code}</span> },
                        { key: 'branch', label: 'Branch', render: (d) => <span className="text-sm text-gray-900">{d.branch?.name || '-'}</span> },
                        { key: 'head', label: 'Head', render: (d) => <span className="text-sm text-gray-900">{d.head_name || '-'}</span> },
                        { key: 'employees', label: 'Employees', render: (d) => <span className="text-sm text-gray-900">{d.employees_count || 0}</span> },
                        { key: 'status', label: 'Status', render: (d) => (
                            <button onClick={(e) => { e.stopPropagation(); handleToggleStatus(d); }}
                                className={`px-3 py-1 text-xs font-semibold rounded-full ${d.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                {d.status}
                            </button>
                        ) },
                        { key: 'actions', label: '', align: 'right', render: (d) => (
                            <div className="flex justify-end gap-2 text-sm font-medium" onClick={(e) => e.stopPropagation()}>
                                <Link href={route('admin.departments.edit', d.id)} className="text-orange-600 hover:text-orange-900">Edit</Link>
                                <button onClick={() => handleDelete(d)} className="text-red-600 hover:text-red-900">Delete</button>
                            </div>
                        ) },
                    ]}
                    rows={departments.data}
                    pagination={departments}
                    sorting={{ filters, onFilter: filter }}
                    emptyTitle="No departments found"
                    emptyMessage="Create your first department to get started."
                    emptyIcon={Store}
                />
            </div>
        </AdminLayout>
    );
}
