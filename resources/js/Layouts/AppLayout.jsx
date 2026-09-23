import { Head, router } from '@inertiajs/react';
import { Wrench } from 'lucide-react';
import FlashToasts from '@/Components/FlashToasts';
import { PageGuidePanel, PageGuideTrigger, usePageGuide } from '@/Components/PageGuide';
import CommandPalette from '@/Components/CommandPalette';
import NotificationBell from '@/Components/NotificationBell';

export default function AppLayout({ children, title = 'Dashboard', auth }) {
    const guide = usePageGuide();
    const logout = () => {
        router.post(route('logout'));
    };

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-slate-50">
                <header className="bg-slate-900 px-6 py-4">
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center">
                                <Wrench className="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <span className="text-xl font-bold text-white">SparesPro</span>
                                <span className="ml-2 text-sm text-slate-400 hidden sm:inline">Motor Spares ERP</span>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <PageGuideTrigger open={guide.open} onToggle={guide.toggle} />
                            <NotificationBell dark />
                            <div className="text-sm text-slate-300">
                                Welcome, <span className="font-semibold text-white">{auth?.user?.name}</span>
                            </div>
                            <button
                                onClick={logout}
                                className="px-4 py-2 text-sm font-medium text-slate-300 border border-slate-700 hover:bg-slate-800 hover:text-white rounded-lg transition-colors"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </header>

                <main className="max-w-7xl mx-auto p-6">
                    <PageGuidePanel open={guide.open} onClose={guide.close} />
                    {children}
                </main>
                <FlashToasts />
            <CommandPalette />
            </div>
        </>
    );
}
