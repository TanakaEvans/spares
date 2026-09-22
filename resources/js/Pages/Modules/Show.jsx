import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { getModule } from '@/modules';

export default function ModuleShow({ auth, moduleKey }) {
    const module = getModule(moduleKey);

    if (!module) return null;

    const Icon = module.icon;

    return (
        <AppLayout title={module.title} auth={auth}>
            <Head title={module.title} />

            <div className="space-y-6">
                {/* Module header */}
                <div className="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
                    <Link
                        href={route('dashboard')}
                        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600 transition-colors mb-4"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back to Dashboard
                    </Link>
                    <div className="flex items-center gap-4">
                        <div className={`w-16 h-16 ${module.color} rounded-2xl flex items-center justify-center shrink-0`}>
                            <Icon className="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-slate-900">{module.title}</h1>
                                <span className={`text-xs font-semibold px-2 py-1 rounded-full ${
                                    module.status === 'Active'
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-slate-100 text-slate-500'
                                }`}>
                                    {module.status === 'Active' ? 'Active' : 'In Planning'}
                                </span>
                            </div>
                            <p className="text-slate-500 mt-1">{module.description}</p>
                        </div>
                    </div>
                </div>

                {/* Sub-module cards */}
                <div>
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">
                        Sub-modules ({module.subModules.length})
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {module.subModules.map((sub) => {
                            const SubIcon = sub.icon;
                            const isActive = sub.active && sub.route && route().has(sub.route);
                            const card = (
                                <>
                                    <div className="flex items-start justify-between mb-3">
                                        <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                                            isActive ? module.color : 'bg-slate-100'
                                        }`}>
                                            <SubIcon className={`w-5 h-5 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                                        </div>
                                        <span className={`text-[11px] font-semibold px-2 py-0.5 rounded-full ${
                                            isActive
                                                ? 'bg-green-50 text-green-700'
                                                : 'bg-slate-100 text-slate-400'
                                        }`}>
                                            {isActive ? 'Active' : 'Coming Soon'}
                                        </span>
                                    </div>
                                    <h3 className={`text-sm font-bold mb-1 ${
                                        isActive ? 'text-slate-900 group-hover:text-orange-600' : 'text-slate-600'
                                    } transition-colors`}>
                                        {sub.title}
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed flex-1">
                                        {sub.description}
                                    </p>
                                    {isActive && (
                                        <div className="flex justify-end mt-3">
                                            <ArrowRight className="w-4 h-4 text-slate-300 group-hover:text-orange-500 group-hover:translate-x-0.5 transition-all" />
                                        </div>
                                    )}
                                </>
                            );

                            return isActive ? (
                                <Link
                                    key={sub.title}
                                    href={route(sub.route)}
                                    className="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 p-5 border border-slate-100 hover:-translate-y-0.5 flex flex-col"
                                >
                                    {card}
                                </Link>
                            ) : (
                                <div
                                    key={sub.title}
                                    className="bg-white rounded-xl shadow-sm p-5 border border-slate-100 opacity-80 flex flex-col"
                                >
                                    {card}
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
