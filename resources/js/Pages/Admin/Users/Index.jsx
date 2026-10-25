import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import StatusBadge from '@/Components/StatusBadge';
import { useListFilter } from '@/lib/listFilters';

export default function Index({ users, roles, filters = {} }) {
    const filter = useListFilter('auth.users.index', filters);

    const toggleStatus = (userId) => {
        router.post(route('auth.users.toggle-status', userId), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const deleteUser = (userId) => {
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(route('auth.users.destroy', userId), {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout>
            <Head title="Users Management" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    <div className="flex justify-between items-center">
                        <h1 className="text-2xl font-semibold text-gray-900">Users Management</h1>
                    </div>

                    <ListToolbar
                        filters={filters}
                        onFilter={filter}
                        searchPlaceholder="Name, email or username…"
                        searchClass="w-64"
                        dateRange
                    >
                        <select aria-label="Status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select aria-label="Role" value={filters.role ?? ''} onChange={(e) => filter({ role: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="">All Roles</option>
                            {(roles ?? []).map((r) => (
                                <option key={r.id} value={r.id}>{r.name}</option>
                            ))}
                        </select>
                    </ListToolbar>

                    <DataTable
                        columns={[
                            { key: 'name', label: 'Name', sortable: 'name', render: (u) => <span className="font-medium text-gray-900">{u.name}</span> },
                            { key: 'email', label: 'Email', sortable: 'email', render: (u) => u.email },
                            { key: 'roles', label: 'Roles', render: (u) => (
                                <div className="flex flex-wrap gap-1">
                                    {(u.roles ?? []).map((r) => (
                                        <span key={r.id} className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{r.name}</span>
                                    ))}
                                </div>
                            ) },
                            { key: 'status', label: 'Status', sortable: 'status', render: (u) => <StatusBadge status={u.status} /> },
                            { key: 'created_at', label: 'Created', sortable: 'created_at', render: (u) => <span className="tabular-nums text-slate-500">{u.created_at ? new Date(u.created_at).toLocaleDateString() : '—'}</span> },
                            { key: 'actions', label: '', align: 'right', render: (u) => (
                                <div className="space-x-2 text-sm font-medium" onClick={(e) => e.stopPropagation()}>
                                    <a href={route('auth.users.edit', u.id)} className="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <button onClick={() => toggleStatus(u.id)} className={u.status === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'}>
                                        {u.status === 'active' ? 'Deactivate' : 'Activate'}
                                    </button>
                                    <button onClick={() => deleteUser(u.id)} className="text-red-600 hover:text-red-900">Delete</button>
                                </div>
                            ) },
                        ]}
                        rows={users.data}
                        pagination={users}
                        sorting={{ filters, onFilter: filter }}
                        emptyTitle="No users found"
                    />
                </div>
            </div>
        </AdminLayout>
    );
}
