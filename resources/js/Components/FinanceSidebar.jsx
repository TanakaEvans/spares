import { Link, usePage } from '@inertiajs/react';

export default function FinanceSidebar() {
    const { url } = usePage();

    const isActive = (route) => url.startsWith(route);

    return (
        <aside className="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-purple-900 to-purple-800 text-white flex flex-col overflow-hidden shadow-xl z-50">
            {/* Logo Section */}
            <div className="p-6 bg-purple-900 flex-shrink-0 border-b border-purple-700/50">
                <div className="flex items-center space-x-3 group">
                    <span className="text-3xl">💰</span>
                    <div className="flex-1">
                        <h1 className="text-lg font-bold">SMS Finance</h1>
                        <p className="text-xs text-purple-300">Financial Management</p>
                    </div>
                </div>
            </div>

            {/* Navigation */}
            <div className="flex-1 overflow-y-auto custom-scrollbar">
                <nav className="py-4">
                    {/* Return to Home */}
                    <div className="mb-6">
                        <Link href={route('dashboard')} className="flex items-center px-6 py-3 transition-all duration-200 ease-in-out hover:bg-purple-700/30 hover:pl-8">
                            <span className="text-2xl mr-3">🏠</span>
                            <span className="font-medium">Return to Home</span>
                        </Link>
                    </div>

                    {/* Dashboard */}
                    <div className="mb-6">
                        <Link
                            href={route('financials.index')}
                            className={`flex items-center px-6 py-3 transition-all duration-200 ease-in-out ${route().current('financials.index') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-2xl mr-3">📊</span>
                            <span className="font-medium">Dashboard</span>
                        </Link>
                    </div>

                    {/* Settings Section */}
                    <div className="mb-6">
                        <div className="px-6 mb-2">
                            <h3 className="text-xs uppercase text-purple-300 font-semibold">Settings</h3>
                        </div>

                        <Link
                            href={route('financials.settings.categories.index')}
                            className={`flex items-center px-6 py-2 transition-all duration-200 ease-in-out ${route().current('financials.settings.categories.*') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-xl mr-3">📁</span>
                            <span className="font-medium">Categories</span>
                        </Link>

                        <Link
                            href={route('financials.settings.subcategories.index')}
                            className={`flex items-center px-6 py-2 transition-all duration-200 ease-in-out ${route().current('financials.settings.subcategories.*') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-xl mr-3">📂</span>
                            <span className="font-medium">Subcategories</span>
                        </Link>

                        <Link
                            href={route('financials.settings.payment-methods.index')}
                            className={`flex items-center px-6 py-2 transition-all duration-200 ease-in-out ${route().current('financials.settings.payment-methods.*') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-xl mr-3">💳</span>
                            <span className="font-medium">Payment Methods</span>
                        </Link>

                        <Link
                            href={route('financials.settings.currencies.index')}
                            className={`flex items-center px-6 py-2 transition-all duration-200 ease-in-out ${route().current('financials.settings.currencies.*') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-xl mr-3">💱</span>
                            <span className="font-medium">Currencies</span>
                        </Link>

                        <Link
                            href={route('financials.settings.gls.index')}
                            className={`flex items-center px-6 py-2 transition-all duration-200 ease-in-out ${route().current('financials.settings.gls.*') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-xl mr-3">�</span>
                            <span className="font-medium">GL Accounts</span>
                        </Link>

                        <Link
                            href={route('financials.settings.banks.index')}
                            className={`flex items-center px-6 py-2 transition-all duration-200 ease-in-out ${route().current('financials.settings.banks.*') ? 'bg-purple-700/50 border-l-4 border-yellow-400 pl-5' : 'hover:bg-purple-700/30 hover:pl-8'}`}
                        >
                            <span className="text-xl mr-3">🏦</span>
                            <span className="font-medium">Bank Accounts</span>
                        </Link>
                    </div>
                </nav>
            </div>

            {/* User Profile (Optional, or handled in Topbar) */}
            <div className="p-4 bg-purple-900/50 border-t border-purple-700/50">
                <div className="text-xs text-center text-purple-300">
                    SMS V1.0
                </div>
            </div>
        </aside>
    );
}
