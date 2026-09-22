import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { MODULES } from '@/modules';

export default function Dashboard({ auth }) {
    const currentDate = new Date().toLocaleDateString('en-GB', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return (
        <AppLayout title="Dashboard" auth={auth}>
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Welcome header */}
                <div className="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-600">
                    <div className="flex justify-between items-center">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">
                                Welcome, {auth?.user?.name || 'Administrator'}
                            </h1>
                            <p className="text-slate-500 mt-1">
                                Select a module to get started
                            </p>
                        </div>
                        <div className="text-right">
                            <div className="text-sm font-medium text-slate-500">{currentDate}</div>
                        </div>
                    </div>
                </div>

                {/* Module cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {MODULES.map((module) => {
                        const Icon = module.icon;
                        return (
                            <Link
                                key={module.key}
                                href={route('modules.show', module.key)}
                                className="group relative bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border border-slate-100 hover:-translate-y-1 flex flex-col"
                            >
                                <div className={`w-14 h-14 ${module.color} rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform`}>
                                    <Icon className="w-7 h-7 text-white" />
                                </div>

                                <h3 className="text-lg font-bold text-slate-900 mb-2 group-hover:text-orange-600 transition-colors">
                                    {module.title}
                                </h3>

                                <p className="text-sm text-slate-500 leading-relaxed mb-4 flex-1">
                                    {module.description}
                                </p>

                                <div className="flex items-center justify-between mt-auto">
                                    <span className={`text-xs font-semibold px-2 py-1 rounded-full ${
                                        module.status === 'Active'
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-slate-100 text-slate-500'
                                    }`}>
                                        {module.status === 'Active' ? 'Active' : 'In Planning'}
                                    </span>
                                    <ArrowRight className="w-4 h-4 text-slate-300 group-hover:text-orange-500 group-hover:translate-x-0.5 transition-all" />
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
