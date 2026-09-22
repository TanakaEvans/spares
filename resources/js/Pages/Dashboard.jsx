import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Dashboard({ auth, stats = {} }) {
    const currentDate = new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    const smsModules = [
        {
            title: 'Students',
            description: 'Manage student enrollments, profiles, and records.',
            icon: '🎓',
            color: 'bg-blue-500',
            route: 'student_records.index',
            status: 'Active'
        },
        {
            title: 'Staff',
            description: 'Manage teachers, administrators, and support staff.',
            icon: '👨‍🏫',
            color: 'bg-indigo-500',
            route: 'staff.index',
            status: 'Active'
        },
        {
            title: 'Academics',
            description: 'Coursework, assignments, and curriculum planning.',
            icon: '📚',
            color: 'bg-green-500',
            route: 'academics.index',
            status: 'Active'
        },
        {
            title: 'Exams & Results',
            description: 'Manage examinations, grading, and report cards.',
            icon: '📝',
            color: 'bg-purple-500',
            route: 'exams.index',
            status: 'Active'
        },
        {
            title: 'Timetable',
            description: 'Schedule classes, teachers, and rooms.',
            icon: '📅',
            color: 'bg-orange-500',
            route: 'timetable.index',
            status: 'Active'
        },
        {
            title: 'Financials',
            description: 'Fee collection, billing, and expense tracking.',
            icon: '💰',
            color: 'bg-teal-500',
            route: 'financials.index',
            status: 'Active'
        },
        {
            title: 'Billing',
            description: 'Manage invoices and student billing.',
            icon: '💳',
            color: 'bg-rose-500',
            route: 'billing.index',
            status: 'Active'
        },
        {
            title: 'Receipting',
            description: 'Process payments and generate receipts.',
            icon: '🧾',
            color: 'bg-emerald-500',
            route: 'receipting.index',
            status: 'Active'
        },
        {
            title: 'E-Learning',
            description: 'Virtual classrooms and online resources.',
            icon: '💻',
            color: 'bg-red-500',
            route: 'elearning.index',
            status: 'Coming Soon'
        },
        {
            title: 'System Settings',
            description: 'User management, roles, and system administration.',
            icon: '⚙️',
            color: 'bg-gray-600',
            route: 'auth.users.index',
            status: 'Active'
        }
    ];

    return (
        <AppLayout title="Dashboard" auth={auth}>
            <Head title="School Dashboard" />

            <div className="space-y-6">
                {/* Welcome Header */}
                <div className="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-600">
                    <div className="flex justify-between items-center">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Welcome, {auth?.user?.name || 'Administrator'}
                            </h1>
                            <p className="text-gray-600 mt-1">
                                School Management System Dashboard
                            </p>
                        </div>
                        <div className="text-right">
                            <div className="text-sm font-medium text-gray-500">{currentDate}</div>
                        </div>
                    </div>
                </div>

                {/* Modules Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {smsModules.map((module, index) => (
                        <Link
                            key={index}
                            href={module.status === 'Active' && route().has(module.route) ? route(module.route) : '#'}
                            className={`group relative bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border border-gray-100 ${module.status !== 'Active' ? 'opacity-75 cursor-not-allowed' : 'hover:-translate-y-1'
                                }`}
                        >
                            <div className={`w-14 h-14 ${module.color} rounded-2xl flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform`}>
                                {module.icon}
                            </div>

                            <h3 className="text-lg font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                                {module.title}
                            </h3>

                            <p className="text-sm text-gray-500 leading-relaxed mb-4">
                                {module.description}
                            </p>

                            <div className="flex items-center justify-between mt-auto">
                                <span className={`text-xs font-semibold px-2 py-1 rounded-full ${module.status === 'Active'
                                    ? 'bg-blue-50 text-blue-600'
                                    : 'bg-gray-100 text-gray-500'
                                    }`}>
                                    {module.status}
                                </span>
                                {module.status === 'Active' && (
                                    <span className="text-gray-300 group-hover:text-blue-500 transition-colors">
                                        →
                                    </span>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
