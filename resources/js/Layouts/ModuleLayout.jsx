import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ArrowLeftRight, ChevronLeft, Home, LogOut, Menu, Settings, Wrench, X } from 'lucide-react';
import FlashToasts from '@/Components/FlashToasts';
import { PageGuidePanel, PageGuideTrigger, usePageGuide } from '@/Components/PageGuide';
import CommandPalette from '@/Components/CommandPalette';
import NotificationBell from '@/Components/NotificationBell';

/**
 * The ONE module shell (component-standards §1a): dark slate-900 sidebar,
 * top bar with breadcrumb, content area. Nav configs are CONTENT ONLY.
 *
 * navConfig = {
 *   moduleKey, moduleLabel, moduleIcon,
 *   module: [{ section, items: [{ label, route, icon, badge? }] }],
 *   subModules: {
 *     key: { label, icon, routePrefix, work: [...], insights: [...],
 *            setup: [...], quickLinks: [{label, route, icon, module?}] },
 *   },
 * }
 * The active sub-module is resolved from the current route name's prefix.
 */

function NavItem({ item, active, muted = false }) {
    const Icon = item.icon;
    return (
        <Link
            href={route(item.route)}
            className={`mx-2 flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors ${
                active
                    ? 'bg-slate-800 text-white border-l-2 border-orange-500 pl-[10px]'
                    : muted
                        ? 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            }`}
        >
            {Icon && <Icon className="w-4 h-4 shrink-0" />}
            <span className="flex-1 truncate">{item.label}</span>
            {muted && <ArrowLeftRight className="w-3 h-3 opacity-50" />}
            {item.badgeCount > 0 && (
                <span className="ml-auto rounded-full bg-orange-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                    {item.badgeCount}
                </span>
            )}
        </Link>
    );
}

function SectionLabel({ children }) {
    return (
        <div className="px-4 pt-5 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
            {children}
        </div>
    );
}

export default function ModuleLayout({ navConfig, title, breadcrumbs = [], children }) {
    const { auth } = usePage().props;
    const currentRoute = route().current() ?? '';
    const [mobileOpen, setMobileOpen] = useState(false);
    const guide = usePageGuide();

    // Level-3 resolution: which sub-module sidebar (if any) owns this route?
    const activeSub = useMemo(() => {
        for (const [key, sub] of Object.entries(navConfig.subModules ?? {})) {
            if (currentRoute.startsWith(sub.routePrefix)) return { key, ...sub };
        }
        return null;
    }, [navConfig, currentRoute]);

    const ModuleIcon = navConfig.moduleIcon;
    const SubIcon = activeSub?.icon;

    const sidebar = (
        <div className="flex h-full flex-col bg-slate-900">
            {/* Brand / context header */}
            <div className="flex items-center gap-3 px-4 py-4 border-b border-slate-800">
                <Link href={route('dashboard')} className="w-9 h-9 bg-orange-600 rounded-lg flex items-center justify-center shrink-0">
                    <Wrench className="w-4.5 h-4.5 text-white" style={{ width: 18, height: 18 }} />
                </Link>
                <div className="min-w-0">
                    <div className="text-sm font-bold text-white truncate">SparesPro</div>
                    <div className="text-[11px] text-slate-400 truncate">{navConfig.moduleLabel}</div>
                </div>
            </div>

            <nav className="flex-1 overflow-y-auto py-2">
                {activeSub ? (
                    <>
                        {/* ↰ back to the module landing (sub-module cards) */}
                        <Link
                            href={route('modules.show', navConfig.moduleKey)}
                            className="mx-2 mt-2 flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-400 hover:bg-slate-800 hover:text-white"
                        >
                            <ChevronLeft className="w-4 h-4" />
                            {navConfig.moduleLabel}
                        </Link>

                        {/* Context header */}
                        <div className="mx-2 mt-2 mb-1 flex items-center gap-2.5 rounded-md bg-slate-800/60 px-3 py-2.5 border-l-2 border-orange-500">
                            {SubIcon && <SubIcon className="w-4 h-4 text-orange-400" />}
                            <span className="text-xs font-bold uppercase tracking-wider text-white">
                                {activeSub.label}
                            </span>
                        </div>

                        {activeSub.work?.length > 0 && (
                            <>
                                <SectionLabel>Work</SectionLabel>
                                {activeSub.work.map((item) => (
                                    <NavItem key={item.route} item={item} active={route().current(item.route)} />
                                ))}
                            </>
                        )}
                        {activeSub.insights?.length > 0 && (
                            <>
                                <SectionLabel>Insights</SectionLabel>
                                {activeSub.insights.map((item) => (
                                    <NavItem key={item.route} item={item} active={route().current(item.route)} />
                                ))}
                            </>
                        )}
                        {activeSub.setup?.length > 0 && (
                            <>
                                <SectionLabel>Setup</SectionLabel>
                                {activeSub.setup.map((item) => (
                                    <NavItem key={item.route} item={item} active={route().current(item.route)} />
                                ))}
                            </>
                        )}
                        {activeSub.quickLinks?.length > 0 && (
                            <>
                                <SectionLabel>Quick Links ⇄</SectionLabel>
                                {activeSub.quickLinks.map((item) => (
                                    <NavItem key={item.route} item={item} active={false} muted />
                                ))}
                            </>
                        )}
                    </>
                ) : (
                    (navConfig.module ?? []).map(({ section, items }) => (
                        <div key={section}>
                            <SectionLabel>{section}</SectionLabel>
                            {items.map((item) => (
                                <NavItem key={item.route} item={item} active={route().current(item.route)} />
                            ))}
                        </div>
                    ))
                )}
            </nav>

            {/* Fixed bottom block */}
            <div className="border-t border-slate-800 py-2">
                <NavItem item={{ label: 'Dashboard', route: 'dashboard', icon: Home }} active={false} />
                <button
                    onClick={() => router.post(route('logout'))}
                    className="mx-2 flex w-[calc(100%-1rem)] items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white"
                >
                    <LogOut className="w-4 h-4" />
                    Logout
                </button>
            </div>
        </div>
    );

    return (
        <>
            <Head title={title} />
            <FlashToasts />
            <CommandPalette />
            <div className="min-h-screen bg-slate-50">
                {/* Desktop sidebar */}
                <aside className="hidden lg:block fixed inset-y-0 left-0 w-64 z-30">{sidebar}</aside>

                {/* Mobile overlay */}
                {mobileOpen && (
                    <div className="lg:hidden fixed inset-0 z-40">
                        <div className="absolute inset-0 bg-slate-900/50" onClick={() => setMobileOpen(false)} />
                        <aside className="absolute inset-y-0 left-0 w-64">{sidebar}</aside>
                        <button
                            onClick={() => setMobileOpen(false)}
                            aria-label="Close menu"
                            className="absolute top-4 left-[17rem] p-2 rounded-lg bg-white shadow"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                )}

                <div className="lg:pl-64">
                    {/* Top bar */}
                    <header className="sticky top-0 z-20 flex items-center gap-4 border-b border-slate-200 bg-white/95 backdrop-blur px-4 lg:px-6 py-3">
                        <button
                            onClick={() => setMobileOpen(true)}
                            aria-label="Open menu"
                            className="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100"
                        >
                            <Menu className="w-5 h-5 text-slate-600" />
                        </button>

                        {/* Breadcrumb */}
                        <nav className="flex-1 flex items-center gap-1.5 text-sm min-w-0" aria-label="Breadcrumb">
                            {ModuleIcon && <ModuleIcon className="w-4 h-4 text-slate-400 shrink-0" />}
                            {breadcrumbs.map((crumb, i) => (
                                <span key={i} className="flex items-center gap-1.5 min-w-0">
                                    {i > 0 && <span className="text-slate-300">›</span>}
                                    {crumb.href ? (
                                        <Link href={crumb.href} className="text-slate-500 hover:text-orange-600 truncate">
                                            {crumb.label}
                                        </Link>
                                    ) : (
                                        <span className="font-semibold text-slate-800 truncate">{crumb.label}</span>
                                    )}
                                </span>
                            ))}
                        </nav>

                        <div className="flex items-center gap-3 text-sm text-slate-500">
                            <PageGuideTrigger open={guide.open} onToggle={guide.toggle} />
                            <NotificationBell />
                            <span className="hidden sm:inline">
                                {auth?.user?.name}
                            </span>
                        </div>
                    </header>

                    <main className="p-4 lg:p-6">
                        <PageGuidePanel open={guide.open} onClose={guide.close} />
                        {children}
                    </main>
                </div>
            </div>
        </>
    );
}
