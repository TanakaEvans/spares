import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import { useListFilter } from '@/lib/listFilters';
import { Eye, Pencil, Trash2, Plus } from 'lucide-react';

export default function RolesIndex({ auth, roles, filters = {} }) {
    const filter = useListFilter('auth.roles.index', filters);

    const deleteRole = (role) => {
        if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
            router.delete(route('auth.roles.destroy', role.id));
        }
    };

    return (
        <AdminLayout title="Role Management" auth={auth}>
            <Head title="Role Management" />

            <div className="space-y-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Role Management</h1>
                        <p className="text-gray-600">Manage system roles and permissions</p>
                    </div>
                    <Link
                        href={route('auth.roles.create')}
                        className="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <Plus className="w-4 h-4" />
                        Create New Role
                    </Link>
                </div>

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Search by name or description…"
                    searchClass="w-72"
                />

                <DataTable
                    columns={[
                        { key: 'name', label: 'Role Name', sortable: 'name', render: (r) => <span className="font-medium text-gray-900">{r.name}</span> },
                        { key: 'description', label: 'Description', render: (r) => <span className="text-gray-600">{r.description || 'No description'}</span> },
                        { key: 'users', label: 'Users', render: (r) => <span className="px-2 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">{r.users_count}</span> },
                        { key: 'permissions', label: 'Permissions', render: (r) => <span className="px-2 py-1 bg-purple-100 text-purple-800 text-sm rounded-full">{r.system_routes_count || 0}</span> },
                        { key: 'created_at', label: 'Created', render: (r) => <span className="text-gray-600">{new Date(r.created_at).toLocaleDateString()}</span> },
                        { key: 'actions', label: '', align: 'right', render: (r) => (
                            <div className="flex justify-end gap-2" onClick={(e) => e.stopPropagation()}>
                                <Link href={route('auth.roles.show', r.id)} className="text-blue-600 hover:text-blue-800 px-2 py-1 rounded transition-colors" title="View Details">
                                    <Eye className="w-4 h-4" />
                                </Link>
                                <Link href={route('auth.roles.edit', r.id)} className="text-green-600 hover:text-green-800 px-2 py-1 rounded transition-colors" title="Edit Role">
                                    <Pencil className="w-4 h-4" />
                                </Link>
                                <button onClick={() => deleteRole(r)} className="text-red-600 hover:text-red-800 px-2 py-1 rounded transition-colors" title="Delete Role">
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        ) },
                    ]}
                    rows={roles.data}
                    pagination={roles}
                    sorting={{ filters, onFilter: filter }}
                    emptyTitle="No roles found"
                    emptyMessage="Try adjusting your search criteria"
                />
            </div>
        </AdminLayout>
    );
}
