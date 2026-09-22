import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';

// Safe route helper - returns '#' if route doesn't exist
const safeRoute = (name, params = {}) => {
    try {
        return route(name, params);
    } catch (e) {
        console.warn(`Route '${name}' not found`);
        return '#';
    }
};

// Safe current route checker
const isCurrentRoute = (pattern) => {
    try {
        return route().current(pattern);
    } catch (e) {
        return false;
    }
};

export default function AdminLayout({ children, title = 'Dashboard', auth, flash }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [profileMenuOpen, setProfileMenuOpen] = useState(false);

    const toggleSidebar = () => {
        setSidebarOpen(!sidebarOpen);
    };

    const toggleProfileMenu = () => {
        setProfileMenuOpen(!profileMenuOpen);
    };

    const logout = () => {
        router.post(route('logout'));
    };

    // Close profile menu when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (!event.target.closest('.profile-dropdown')) {
                setProfileMenuOpen(false);
            }
        };

        document.addEventListener('click', handleClickOutside);
        return () => document.removeEventListener('click', handleClickOutside);
    }, []);

    // Hide flash messages after 5 seconds
    useEffect(() => {
        if (flash?.success || flash?.error || flash?.warning) {
            const timer = setTimeout(() => {
                // Flash messages will fade out automatically with CSS
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gray-50">
                {/* Sidebar */}
                <nav className={`fixed left-0 top-0 h-full w-64 bg-slate-900 border-r border-slate-800 transition-transform duration-300 z-50 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
                    }`}>
                    {/* Sidebar Header */}
                    <div className="px-6 py-4 border-b border-slate-800">
                        <Link href={safeRoute('dashboard')} className="flex items-center gap-3 text-white hover:text-cyan-400 transition-colors">
                            <div className="w-8 h-8 bg-cyan-600 rounded-lg flex items-center justify-center text-xl shadow-lg shadow-cyan-900/20">
                                🍽
                            </div>
                            <span className="font-bold text-lg tracking-wide text-slate-100">SMS Admin</span>
                        </Link>
                    </div>

                    {/* Sidebar Navigation */}
                    <div className="py-4 overflow-y-auto h-[calc(100vh-80px)] scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">

                        {/* Drive Status Widget */}
                        <div className="px-4 mb-6">
                            <div className="bg-slate-800/50 rounded-lg p-3 border border-slate-700">
                                <div className="flex items-center gap-3 mb-2">
                                    <div className="text-cyan-400 text-xl">💾</div>
                                    <div className="flex-1">
                                        <h6 className="text-xs font-semibold text-slate-400 uppercase">System Drive</h6>
                                    </div>
                                </div>
                                <div className="w-full bg-slate-700 rounded-full h-1.5 mb-1">
                                    <div className="bg-cyan-500 h-1.5 rounded-full" style={{ width: '45%' }}></div>
                                </div>
                                <div className="flex justify-between text-[10px] text-slate-500">
                                    <span>45% used</span>
                                    <span>Monitoring active</span>
                                </div>
                            </div>
                        </div>

                        {/* Main Section */}
                        <div className="mb-2">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Menu
                            </div>
                            <div className="px-4 mb-2">
                                <div className="h-0.5 bg-gradient-to-r from-purple-500 to-transparent rounded opacity-20"></div>
                            </div>
                            <Link
                                href={safeRoute('dashboard')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('dashboard') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className={`w-5 transition-transform duration-200 ${isCurrentRoute('dashboard') ? 'scale-110' : ''}`}>📊</span>
                                Dashboard
                            </Link>
                        </div>

                        {/* Users & Employees Section */}
                        <div className="mb-4">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Users & Employees
                            </div>
                            <Link
                                href={safeRoute('auth.users.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('auth.users.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">👥</span>
                                System Users
                            </Link>
                            <Link
                                href={safeRoute('admin.employees.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.employees.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">👔</span>
                                Employees
                            </Link>
                        </div>

                        {/* Rights & Roles Section */}
                        <div className="mb-4">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Rights & Roles
                            </div>
                            <Link
                                href={safeRoute('auth.roles.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('auth.roles.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🛡</span>
                                User Roles
                            </Link>
                            <Link
                                href={safeRoute('auth.roles.bulk-assign')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('auth.roles.bulk-assign') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">➕</span>
                                Bulk Assign Roles
                            </Link>
                            <Link
                                href={safeRoute('auth.roles.bulk-remove')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('auth.roles.bulk-remove') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">➖</span>
                                Bulk Remove Roles
                            </Link>
                            <Link
                                href={safeRoute('auth.roles.users-report')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('auth.roles.users-report') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">📋</span>
                                Users with Roles
                            </Link>
                            <Link
                                href={safeRoute('auth.management')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('auth.management') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🔑</span>
                                Auth Management
                            </Link>
                        </div>

                        {/* Company Configuration Section */}
                        <div className="mb-4">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Company Configuration
                            </div>
                            <Link
                                href={safeRoute('admin.company.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.company.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🏢</span>
                                Company Details
                            </Link>
                            <Link
                                href={safeRoute('admin.branches.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.branches.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🏠</span>
                                Branches
                            </Link>
                            <Link
                                href={safeRoute('admin.departments.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.departments.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🏬</span>
                                Departments
                            </Link>
                            <Link
                                href={safeRoute('admin.sections.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.sections.*') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🧩</span>
                                Sections
                            </Link>
                        </div>

                        <div className="px-4 mb-4">
                            <div className="h-0.5 bg-gradient-to-r from-purple-500 to-transparent rounded opacity-20"></div>
                        </div>

                        {/* Management Section */}
                        <div className="mb-4">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Management
                            </div>
                            <Link
                                href={safeRoute('admin.tickets.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.tickets.index') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🎫</span>
                                Tickets
                            </Link>
                            <Link
                                href={safeRoute('admin.tickets.summary')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.tickets.summary') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">📑</span>
                                Ticket Summary
                            </Link>
                            <Link
                                href={safeRoute('admin.change-requests.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.change-requests.index') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">📝</span>
                                Change Requests
                            </Link>
                            <Link
                                href={safeRoute('admin.password-policy.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.password-policy.index') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">🔒</span>
                                Password Policy
                            </Link>
                            <Link
                                href={safeRoute('admin.backups.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.backups.index') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">💾</span>
                                Backup Management
                            </Link>
                            <Link
                                href={safeRoute('admin.drive-monitoring.index')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('admin.drive-monitoring.index') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">💿</span>
                                Drive Monitoring
                            </Link>
                        </div>

                        {/* Reports Section */}
                        <div className="mb-4">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Reports
                            </div>
                            <Link
                                href={safeRoute('system.logs')}
                                className={`flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border-l-3 ${isCurrentRoute('system.logs') ? 'border-cyan-500 bg-slate-800 text-cyan-400 shadow-[inset_10px_0_20px_-10px_rgba(6,182,212,0.15)]' : 'border-transparent'
                                    }`}
                            >
                                <span className="w-5">📄</span>
                                Activity Logs
                            </Link>
                        </div>

                        <div className="px-4 mb-4">
                            <div className="h-0.5 bg-gradient-to-r from-purple-500 to-transparent rounded opacity-20"></div>
                        </div>

                        {/* Logout Section */}
                        <div className="mb-4">
                            <div className="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Authentication
                            </div>
                            <button
                                onClick={logout}
                                className="flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-red-900/50 hover:text-red-400 transition-colors w-full text-left"
                            >
                                <span className="w-5">🚪</span>
                                Logout
                            </button>

                            <div className="px-4 my-2">
                                <div className="h-0.5 bg-slate-800 rounded"></div>
                            </div>

                            <Link
                                href={safeRoute('welcome')}
                                className="flex items-center gap-3 px-6 py-2.5 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors w-full text-left"
                            >
                                <span className="w-5">🏠</span>
                                Return Home
                            </Link>
                        </div>
                    </div>
                </nav>

                {/* Main Content */}
                <div className={`transition-all duration-300 ${sidebarOpen ? 'md:ml-64' : 'md:ml-64'}`}>
                    {/* Header */}
                    <header className="bg-white border-b border-gray-200 px-6 py-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <button
                                    onClick={toggleSidebar}
                                    className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors md:hidden"
                                >
                                    <span className="text-xl">☰</span>
                                </button>
                                <h1 className="text-2xl font-semibold text-gray-900">{title}</h1>
                            </div>

                            <div className="flex items-center gap-4">
                                {/* Search Box */}
                                <div className="hidden md:block relative">
                                    <input
                                        type="text"
                                        placeholder="Search..."
                                        className="w-72 px-4 py-2 pl-10 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                        🔍
                                    </span>
                                </div>

                                {/* Notifications */}
                                <button className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                    <span className="text-xl">🔔</span>
                                    <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                        3
                                    </span>
                                </button>

                                {/* Profile Dropdown */}
                                <div className="relative profile-dropdown">
                                    <button
                                        onClick={toggleProfileMenu}
                                        className="flex items-center gap-3 p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                            {auth?.user?.name?.charAt(0) || 'A'}
                                        </div>
                                    </button>

                                    {profileMenuOpen && (
                                        <div className="absolute right-0 top-full mt-2 w-72 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                                            <div className="px-4 py-3 border-b border-gray-100">
                                                <div className="font-semibold text-gray-900">{auth?.user?.name}</div>
                                                <div className="text-sm text-gray-500">{auth?.user?.email}</div>
                                                <div className="flex gap-1 mt-2">
                                                    {auth?.user?.roles?.map((role) => (
                                                        <span key={role.id} className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                            {role.name}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                            <Link
                                                href="#"
                                                className="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                <span>👤</span>
                                                Profile Settings
                                            </Link>
                                            <Link
                                                href="#"
                                                className="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                <span>⚙️</span>
                                                Account Settings
                                            </Link>
                                            <hr className="my-2" />
                                            <button
                                                onClick={logout}
                                                className="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 transition-colors w-full text-left"
                                            >
                                                <span>🚪</span>
                                                Sign Out
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </header>

                    {/* Content Area */}
                    <main className="p-6">
                        {/* Flash Messages */}
                        {flash?.success && (
                            <div className="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
                                <span>✅</span>
                                {flash.success}
                            </div>
                        )}

                        {flash?.error && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                                <span>❌</span>
                                {flash.error}
                            </div>
                        )}

                        {flash?.warning && (
                            <div className="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg flex items-center gap-2">
                                <span>⚠️</span>
                                {flash.warning}
                            </div>
                        )}

                        {children}
                    </main>
                </div>

                {/* Mobile sidebar overlay */}
                {sidebarOpen && (
                    <div
                        className="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
                        onClick={() => setSidebarOpen(false)}
                    />
                )}
            </div>
        </>
    );
}
