import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable from '@/Components/DataTable';
import ListToolbar from '@/Components/ListToolbar';
import { useListFilter } from '@/lib/listFilters';
import { Users, Plus } from 'lucide-react';

export default function EmployeesIndex({ auth, employees, branches, departments, filters = {} }) {
    const filter = useListFilter('admin.employees.index', filters);

    const handleDelete = (employee) => {
        if (confirm(`Are you sure you want to delete "${employee.first_name} ${employee.last_name}"?`)) {
            router.delete(route('admin.employees.destroy', employee.id));
        }
    };

    const handleToggleStatus = (employee) => {
        router.patch(route('admin.employees.toggle-status', employee.id));
    };

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-gray-100 text-gray-800',
            terminated: 'bg-red-100 text-red-800',
            suspended: 'bg-yellow-100 text-yellow-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout title="Employees" auth={auth}>
            <Head title="Employees" />

            <div className="space-y-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">Employees</h2>
                        <p className="text-gray-600">Manage employee records and user accounts</p>
                    </div>
                    <Link
                        href={route('admin.employees.create')}
                        className="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors flex items-center gap-2"
                    >
                        <Plus className="w-4 h-4" />
                        Add Employee
                    </Link>
                </div>

                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Name, number, email or branch…"
                    searchClass="w-64"
                >
                    <select aria-label="Branch" value={filters.branch_id ?? ''} onChange={(e) => filter({ branch_id: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <select aria-label="Department" value={filters.department_id ?? ''} onChange={(e) => filter({ department_id: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Departments</option>
                        {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                    </select>
                    <select aria-label="Status" value={filters.status ?? ''} onChange={(e) => filter({ status: e.target.value })} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="terminated">Terminated</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'name', label: 'Employee', sortable: 'first_name', render: (e) => (
                            <div className="flex items-center">
                                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                                    {e.photo ? (
                                        <img src={`/${e.photo}`} alt="" className="w-full h-full object-cover" />
                                    ) : (
                                        <span className="text-gray-500 font-semibold">{e.first_name.charAt(0)}{e.last_name.charAt(0)}</span>
                                    )}
                                </div>
                                <div className="ml-4">
                                    <div className="text-sm font-medium text-gray-900">{e.first_name} {e.last_name}</div>
                                    <div className="text-sm text-gray-500">{e.email || '-'}</div>
                                </div>
                            </div>
                        ) },
                        { key: 'employee_number', label: 'ID / Job Title', sortable: 'employee_number', render: (e) => (
                            <div><div className="text-sm font-mono text-gray-900">{e.employee_number}</div><div className="text-sm text-gray-500">{e.job_title || '-'}</div></div>
                        ) },
                        { key: 'department', label: 'Department', render: (e) => (
                            <div><div className="text-sm text-gray-900">{e.department?.name || '-'}</div><div className="text-sm text-gray-500">{e.branch?.name || '-'}</div></div>
                        ) },
                        { key: 'phone', label: 'Contact', render: (e) => <span className="text-sm text-gray-900">{e.phone || '-'}</span> },
                        { key: 'account', label: 'User Account', render: (e) => e.user ? (
                            <div>
                                <span className="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Has Account</span>
                                <div className="text-xs text-gray-500 mt-1">{e.user.roles?.map((r) => r.name).join(', ') || 'No roles'}</div>
                            </div>
                        ) : (
                            <Link href={route('admin.employees.create-user', e.id)} onClick={(ev) => ev.stopPropagation()}
                                className="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200">Create Account</Link>
                        ) },
                        { key: 'status', label: 'Status', sortable: 'status', render: (e) => (
                            <button onClick={(ev) => { ev.stopPropagation(); handleToggleStatus(e); }}
                                className={`px-3 py-1 text-xs font-semibold rounded-full ${getStatusColor(e.status)}`}>
                                {e.status}
                            </button>
                        ) },
                        { key: 'actions', label: '', align: 'right', render: (e) => (
                            <div className="flex justify-end gap-2 text-sm font-medium" onClick={(ev) => ev.stopPropagation()}>
                                <Link href={route('admin.employees.show', e.id)} className="text-gray-600 hover:text-gray-900">View</Link>
                                <Link href={route('admin.employees.edit', e.id)} className="text-orange-600 hover:text-orange-900">Edit</Link>
                                <button onClick={() => handleDelete(e)} className="text-red-600 hover:text-red-900">Delete</button>
                            </div>
                        ) },
                    ]}
                    rows={employees.data}
                    pagination={employees}
                    sorting={{ filters, onFilter: filter }}
                    emptyTitle="No employees found"
                    emptyMessage="Add your first employee to get started."
                    emptyIcon={Users}
                />
            </div>
        </AdminLayout>
    );
}
