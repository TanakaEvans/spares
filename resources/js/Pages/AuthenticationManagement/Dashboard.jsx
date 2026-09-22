import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useState } from 'react';

export default function AuthenticationDashboard({ auth, stats = {} }) {
    const {
        total_users = 0,
        active_users = 0,
        inactive_users = 0,
        total_roles = 0,
        students_count = 0,
        teachers_count = 0,
        parents_count = 0,
        staff_count = 0,
        admin_count = 0
    } = stats;

    const quickActions = [
        {
            title: 'Add New User',
            description: 'Create a new user account with custom roles',
            icon: '👤',
            color: 'bg-blue-500',
            route: 'auth.users.create'
        },
        {
            title: 'Create Role',
            description: 'Define new system roles and permissions',
            icon: '🛡️',
            color: 'bg-green-500',
            route: 'auth.roles.create'
        },
        {
            title: 'Add Student',
            description: 'Register a new student in the system',
            icon: '🎓',
            color: 'bg-indigo-500',
            route: 'auth.students.create'
        },
        {
            title: 'Add Teacher',
            description: 'Register a new teacher account',
            icon: '👨‍🏫',
            color: 'bg-purple-500',
            route: 'auth.teachers.create'
        },
        {
            title: 'Add Parent',
            description: 'Create a parent account for student monitoring',
            icon: '👨‍👩‍👧‍👦',
            color: 'bg-pink-500',
            route: 'auth.parents.create'
        },
        {
            title: 'Add Staff',
            description: 'Register administrative staff member',
            icon: '👔',
            color: 'bg-yellow-500',
            route: 'auth.staff.create'
        }
    ];

    const managementSections = [
        {
            title: 'All Users',
            description: 'Manage all system users, their roles, and permissions',
            count: total_users,
            icon: '👥',
            color: 'bg-blue-500',
            route: 'auth.users.index'
        },
        {
            title: 'Students',
            description: 'Manage student accounts and academic profiles',
            count: students_count,
            icon: '🎓',
            color: 'bg-indigo-500',
            route: 'auth.students.index'
        },
        {
            title: 'Teachers',
            description: 'Manage teacher accounts and teaching assignments',
            count: teachers_count,
            icon: '👨‍🏫',
            color: 'bg-purple-500',
            route: 'auth.teachers.index'
        },
        {
            title: 'Parents',
            description: 'Manage parent accounts and family connections',
            count: parents_count,
            icon: '👨‍👩‍👧‍👦',
            color: 'bg-green-500',
            route: 'auth.parents.index'
        },
        {
            title: 'Staff',
            description: 'Manage administrative staff and their roles',
            count: staff_count,
            icon: '👔',
            color: 'bg-yellow-500',
            route: 'auth.staff.index'
        },
        {
            title: 'System Roles',
            description: 'Configure roles and permission levels',
            count: total_roles,
            icon: '🛡️',
            color: 'bg-red-500',
            route: 'auth.roles.index'
        }
    ];

    const statusCards = [
        {
            title: 'Total Users',
            value: total_users,
            icon: '👥',
            color: 'bg-blue-500',
            description: 'All registered users'
        },
        {
            title: 'Active Users',
            value: active_users,
            icon: '✅',
            color: 'bg-green-500',
            description: 'Currently active accounts'
        },
        {
            title: 'Inactive Users',
            value: inactive_users,
            icon: '❌',
            color: 'bg-red-500',
            description: 'Deactivated accounts'
        },
        {
            title: 'System Roles',
            value: total_roles,
            icon: '🛡️',
            color: 'bg-indigo-500',
            description: 'Available user roles'
        }
    ];

    return (
        <AdminLayout title="Authentication Management" auth={auth}>
            <Head title="Authentication Management" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="bg-white rounded-xl shadow-sm p-6">
                    <div className="flex justify-between items-center">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                Authentication & Role Management
                            </h1>
                            <p className="text-gray-600">
                                Manage user accounts, roles, permissions, and access control for the school portal system.
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Link
                                href={route('dashboard')}
                                className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                ← Back to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Status Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {statusCards.map((card, index) => (
                        <div key={index} className="bg-white rounded-xl shadow-sm p-6">
                            <div className="flex items-center justify-between mb-4">
                                <div className={`w-12 h-12 ${card.color} rounded-xl flex items-center justify-center text-white text-xl`}>
                                    {card.icon}
                                </div>
                            </div>
                            <div className="text-3xl font-bold text-gray-900 mb-1">{card.value}</div>
                            <div className="text-gray-600 font-medium mb-1">{card.title}</div>
                            <div className="text-sm text-gray-500">{card.description}</div>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Quick Actions */}
                    <div className="lg:col-span-1">
                        <div className="bg-white rounded-xl shadow-sm p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-4">Quick Actions</h3>
                            <div className="space-y-3">
                                {quickActions.map((action, index) => (
                                    <Link
                                        key={index}
                                        href={route(action.route)}
                                        className={`flex items-start gap-3 p-4 ${action.color} text-white rounded-lg hover:opacity-90 transition-opacity group`}
                                    >
                                        <span className="text-2xl">{action.icon}</span>
                                        <div>
                                            <div className="font-semibold">{action.title}</div>
                                            <div className="text-sm opacity-90">{action.description}</div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Management Sections */}
                    <div className="lg:col-span-2">
                        <div className="bg-white rounded-xl shadow-sm p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-4">User Management</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {managementSections.map((section, index) => (
                                    <Link
                                        key={index}
                                        href={route(section.route)}
                                        className="block p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:shadow-md transition-all hover:-translate-y-1"
                                    >
                                        <div className="flex items-center gap-4 mb-3">
                                            <div className={`w-12 h-12 ${section.color} rounded-xl flex items-center justify-center text-white text-xl`}>
                                                {section.icon}
                                            </div>
                                            <div className="flex-1">
                                                <h4 className="font-semibold text-gray-900">{section.title}</h4>
                                                <div className="text-2xl font-bold text-gray-900">{section.count}</div>
                                            </div>
                                        </div>
                                        <p className="text-sm text-gray-600">{section.description}</p>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recent Activity & System Status */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* System Health */}
                    <div className="bg-white rounded-xl shadow-sm p-6">
                        <h3 className="text-xl font-bold text-gray-900 mb-4">System Health</h3>
                        <div className="space-y-4">
                            <div className="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <div className="flex items-center gap-3">
                                    <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span className="font-medium">Authentication System</span>
                                </div>
                                <span className="text-green-600 font-semibold">Online</span>
                            </div>
                            <div className="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <div className="flex items-center gap-3">
                                    <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span className="font-medium">Role Management</span>
                                </div>
                                <span className="text-green-600 font-semibold">Operational</span>
                            </div>
                            <div className="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <div className="flex items-center gap-3">
                                    <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span className="font-medium">User Sessions</span>
                                </div>
                                <span className="text-green-600 font-semibold">Active</span>
                            </div>
                        </div>
                    </div>

                    {/* Quick Stats */}
                    <div className="bg-white rounded-xl shadow-sm p-6">
                        <h3 className="text-xl font-bold text-gray-900 mb-4">User Distribution</h3>
                        <div className="space-y-3">
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600">Students</span>
                                <div className="flex items-center gap-2">
                                    <div className="w-24 bg-gray-200 rounded-full h-2">
                                        <div 
                                            className="bg-indigo-500 h-2 rounded-full" 
                                            style={{ width: `${total_users > 0 ? (students_count / total_users * 100) : 0}%` }}
                                        ></div>
                                    </div>
                                    <span className="font-semibold text-gray-900 min-w-[3rem] text-right">{students_count}</span>
                                </div>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600">Teachers</span>
                                <div className="flex items-center gap-2">
                                    <div className="w-24 bg-gray-200 rounded-full h-2">
                                        <div 
                                            className="bg-purple-500 h-2 rounded-full" 
                                            style={{ width: `${total_users > 0 ? (teachers_count / total_users * 100) : 0}%` }}
                                        ></div>
                                    </div>
                                    <span className="font-semibold text-gray-900 min-w-[3rem] text-right">{teachers_count}</span>
                                </div>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600">Parents</span>
                                <div className="flex items-center gap-2">
                                    <div className="w-24 bg-gray-200 rounded-full h-2">
                                        <div 
                                            className="bg-green-500 h-2 rounded-full" 
                                            style={{ width: `${total_users > 0 ? (parents_count / total_users * 100) : 0}%` }}
                                        ></div>
                                    </div>
                                    <span className="font-semibold text-gray-900 min-w-[3rem] text-right">{parents_count}</span>
                                </div>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600">Staff</span>
                                <div className="flex items-center gap-2">
                                    <div className="w-24 bg-gray-200 rounded-full h-2">
                                        <div 
                                            className="bg-yellow-500 h-2 rounded-full" 
                                            style={{ width: `${total_users > 0 ? (staff_count / total_users * 100) : 0}%` }}
                                        ></div>
                                    </div>
                                    <span className="font-semibold text-gray-900 min-w-[3rem] text-right">{staff_count}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}