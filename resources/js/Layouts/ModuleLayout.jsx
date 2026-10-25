import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import {
    ArrowLeftRight, BookOpen, Check, ChevronDown, ChevronsLeft, ChevronsRight, ChevronsUpDown,
    Home, LayoutGrid, LogOut, Menu, Plus, Search, Wrench, X,
} from 'lucide-react';
import FlashToasts from '@/Components/FlashToasts';
import { PageGuidePanel, PageGuideTrigger, usePageGuide } from '@/Components/PageGuide';
import CommandPalette, { openCommandPalette } from '@/Components/CommandPalette';
import NotificationBell from '@/Components/NotificationBell';
import { useCan } from '@/lib/can';
import { MODULES } from '@/modules';

/**
 * The ONE module shell (component-standards §1a): dark slate-900 sidebar,
 * top bar with breadcrumb, content area. Nav configs are CONTENT ONLY.
 *
 * navConfig = {
 *   moduleKey, moduleLabel, moduleIcon,
 *   sections: [{ section, items: [{
 *       label, route, icon,
 *       match?,     // route-name prefix that makes this item active (default: route minus ".index")
 *       children?,  // quick actions shown permanently under the item  [{ label, route, icon }]
 *       related?,   // cross-module jumps shown at the bottom while this item is active
 *   }] }],
 * }
 *
 * HCI rules (docs/design/navigation-and-layout.md §2):
 *  - the menu is IDENTICAL on every page of a module — nothing is swapped in or out;
 *  - the active item is the longest route-prefix match, so the highlight is exact;
 *  - sections collapse and remember it; the section holding the current page is always open;
 *  - sidebar scroll position survives navigation; `[` toggles the icon rail.
 */

const matchOf = (item) => item.match ?? item.route.replace(/\.index$/, '');
const isUnder = (current, prefix) => current === prefix || current.startsWith(`${prefix}.`);

function resolveActive(sections, current) {
    let best = null;
    for (const { section, items } of sections) {
        for (const item of items) {
            const prefixes = [matchOf(item), ...(item.children ?? []).map((c) => c.match ?? c.route)];
            for (const prefix of prefixes) {
                if (isUnder(current, prefix) && (!best || prefix.length > best.length)) {
                    best = { item, section, length: prefix.length };
                }
            }
        }
    }
    return best;
}

function useStored(key, initial, storage = 'localStorage') {
    const [value, setValue] = useState(() => {
        try {
            const raw = window[storage].getItem(key);
            return raw === null ? initial : JSON.parse(raw);
        } catch {
            return initial;
        }
    });
    const set = useCallback((next) => {
        setValue((prev) => {
            const v = typeof next === 'function' ? next(prev) : next;
            try { window[storage].setItem(key, JSON.stringify(v)); } catch { /* storage full/blocked — state still works */ }
            return v;
        });
    }, [key, storage]);
    return [value, set];
}

function NavItem({ item, active, rail, muted = false, child = false }) {
    const Icon = item.icon;
    const href = item.params !== undefined ? route(item.route, item.params) : route(item.route);
    return (
        <Link
            href={href}
            prefetch="hover"
            cacheFor={30000}
            title={rail ? item.label : undefined}
            aria-current={active ? 'page' : undefined}
            data-active={active ? 'true' : undefined}
            className={`mx-2 flex items-center gap-3 rounded-md text-sm transition-colors ${
                rail ? 'justify-center px-0 py-2' : child ? 'pl-9 pr-3 py-1.5 text-[13px]' : 'px-3 py-2'
            } ${
                active
                    ? 'bg-slate-800 text-white border-l-2 border-orange-500'
                    : muted
                        ? 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
                        : child
                            ? 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
            }`}
        >
            {Icon && <Icon className={`${child ? 'w-3.5 h-3.5' : 'w-4 h-4'} shrink-0`} />}
            {!rail && <span className="flex-1 truncate">{item.label}</span>}
            {!rail && muted && <ArrowLeftRight className="w-3 h-3 opacity-50" />}
            {!rail && item.badgeCount > 0 && (
                <span className="ml-auto rounded-full bg-orange-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                    {item.badgeCount}
                </span>
            )}
        </Link>
    );
}

function ModuleSwitcher({ navConfig, brand, rail }) {
    const { canModule } = useCan();
    const ModuleIcon = navConfig.moduleIcon;
    const modules = MODULES.filter((m) => canModule(m.key));

    return (
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild>
                <button
                    className={`group flex w-full items-center gap-3 rounded-lg text-left hover:bg-slate-800 transition-colors ${rail ? 'justify-center p-1.5' : 'px-2 py-1.5'}`}
                    title="Switch module"
                    aria-label="Switch module"
                >
                    <span className="w-9 h-9 bg-orange-600 rounded-lg flex items-center justify-center shrink-0">
                        {ModuleIcon ? <ModuleIcon className="w-[18px] h-[18px] text-white" /> : <Wrench className="w-[18px] h-[18px] text-white" />}
                    </span>
                    {!rail && (
                        <>
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-bold text-white truncate">{navConfig.moduleLabel}</span>
                                <span className="block text-[11px] text-slate-400 truncate">{brand}</span>
                            </span>
                            <ChevronsUpDown className="w-4 h-4 text-slate-500 group-hover:text-slate-300" />
                        </>
                    )}
                </button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Portal>
                <DropdownMenu.Content
                    side="bottom"
                    align="start"
                    sideOffset={6}
                    className="z-50 w-64 rounded-xl border border-slate-700 bg-slate-900 p-1.5 shadow-2xl"
                >
                    <DropdownMenu.Item asChild>
                        <Link href={route('dashboard')} className="flex items-center gap-3 rounded-md px-2.5 py-2 text-sm text-slate-300 outline-none data-[highlighted]:bg-slate-800 data-[highlighted]:text-white">
                            <Home className="w-4 h-4" /> Dashboard
                        </Link>
                    </DropdownMenu.Item>
                    <DropdownMenu.Separator className="my-1 h-px bg-slate-800" />
                    <DropdownMenu.Label className="px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Switch module</DropdownMenu.Label>
                    {modules.map((m) => {
                        const Icon = m.icon;
                        const current = m.key === navConfig.moduleKey;
                        return (
                            <DropdownMenu.Item key={m.key} asChild>
                                <Link
                                    href={route('modules.show', m.key)}
                                    className="flex items-center gap-3 rounded-md px-2.5 py-1.5 text-sm text-slate-300 outline-none data-[highlighted]:bg-slate-800 data-[highlighted]:text-white"
                                >
                                    <span className={`w-6 h-6 rounded-md ${m.color} flex items-center justify-center shrink-0`}><Icon className="w-3.5 h-3.5 text-white" /></span>
                                    <span className="flex-1 truncate">{m.title}</span>
                                    {current && <Check className="w-4 h-4 text-orange-400" />}
                                </Link>
                            </DropdownMenu.Item>
                        );
                    })}
                </DropdownMenu.Content>
            </DropdownMenu.Portal>
        </DropdownMenu.Root>
    );
}

function Sidebar({ navConfig, brand, rail, onToggleRail, inOverlay = false }) {
    const { can } = useCan();
    const currentRoute = route().current() ?? '';
    const moduleKey = navConfig.moduleKey;

    const sections = useMemo(() => (navConfig.sections ?? [])
        .map(({ section, items }) => ({
            section,
            items: items
                .filter((item) => can(item.route))
                .map((item) => ({ ...item, children: (item.children ?? []).filter((c) => can(c.route)) })),
        }))
        .filter(({ items }) => items.length > 0), [navConfig, can]);

    const active = useMemo(() => resolveActive(sections, currentRoute), [sections, currentRoute]);
    const related = (active?.item.related ?? []).filter((r) => can(r.route)).slice(0, 4);
    const onLanding = currentRoute === 'modules.show';

    const [closed, setClosed] = useStored(`nav.closed.${moduleKey}`, []);
    const toggleSection = (section) => setClosed((list) => (list.includes(section) ? list.filter((s) => s !== section) : [...list, section]));

    // Keep the sidebar where the user left it — Inertia remounts the layout per page.
    const navRef = useRef(null);
    const scrollKey = `nav.scroll.${moduleKey}${inOverlay ? '.m' : ''}`;
    useLayoutEffect(() => {
        const el = navRef.current;
        if (!el) return;
        const saved = Number(sessionStorage.getItem(scrollKey) ?? 0);
        el.scrollTop = saved;
        const activeEl = el.querySelector('[data-active="true"]');
        if (activeEl) {
            const a = activeEl.getBoundingClientRect();
            const n = el.getBoundingClientRect();
            if (a.top < n.top || a.bottom > n.bottom) activeEl.scrollIntoView({ block: 'nearest' });
        }
    }, [scrollKey, currentRoute]);
    const saveScroll = (e) => sessionStorage.setItem(scrollKey, String(e.currentTarget.scrollTop));

    return (
        <div className="flex h-full flex-col bg-slate-900">
            {/* Module header — click to switch module */}
            <div className={`border-b border-slate-800 ${rail ? 'px-2 py-3' : 'px-2 py-3'}`}>
                <ModuleSwitcher navConfig={navConfig} brand={brand} rail={rail} />
                <button
                    onClick={openCommandPalette}
                    title="Search parts, customers, documents and screens (Ctrl+K)"
                    className={`mt-2 flex w-full items-center gap-2 rounded-md border border-slate-700/70 bg-slate-800/50 text-sm text-slate-400 hover:border-slate-600 hover:text-slate-200 transition-colors ${rail ? 'justify-center py-2' : 'px-3 py-1.5'}`}
                >
                    <Search className="w-4 h-4 shrink-0" />
                    {!rail && (
                        <>
                            <span className="flex-1 text-left">Search…</span>
                            <kbd className="rounded border border-slate-700 px-1.5 text-[10px] font-semibold text-slate-500">Ctrl K</kbd>
                        </>
                    )}
                </button>
            </div>

            <nav ref={navRef} onScroll={saveScroll} className="flex-1 overflow-y-auto py-2" aria-label={`${navConfig.moduleLabel} menu`}>
                <NavItem
                    item={{ label: 'Overview', route: 'modules.show', params: moduleKey, icon: LayoutGrid }}
                    active={onLanding}
                    rail={rail}
                />

                {sections.map(({ section, items }) => {
                    const holdsActive = active?.section === section;
                    const open = rail || holdsActive || !closed.includes(section);
                    return (
                        <div key={section} className={rail ? 'mt-2 pt-2 border-t border-slate-800/70' : ''}>
                            {!rail && (
                                <button
                                    onClick={() => !holdsActive && toggleSection(section)}
                                    aria-expanded={open}
                                    title={holdsActive ? 'Contains the page you are on' : open ? 'Collapse section' : 'Expand section'}
                                    className={`flex w-full items-center gap-1 px-4 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider ${holdsActive ? 'text-slate-400 cursor-default' : 'text-slate-500 hover:text-slate-300'}`}
                                >
                                    <span className="flex-1 text-left">{section}</span>
                                    <ChevronDown className={`w-3.5 h-3.5 transition-transform ${open ? '' : '-rotate-90'} ${holdsActive ? 'opacity-0' : ''}`} />
                                </button>
                            )}
                            {open && items.map((item) => (
                                <div key={item.route}>
                                    <NavItem item={item} active={active?.item === item && !item.children?.some((c) => isUnder(currentRoute, c.match ?? c.route))} rail={rail} />
                                    {!rail && item.children.map((c) => (
                                        <NavItem key={c.route} item={{ icon: Plus, ...c }} active={isUnder(currentRoute, c.match ?? c.route)} rail={false} child />
                                    ))}
                                </div>
                            ))}
                        </div>
                    );
                })}

                {!rail && related.length > 0 && (
                    <div className="mt-3 border-t border-slate-800 pt-1">
                        <div className="px-4 pt-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            Related ⇄
                        </div>
                        {related.map((r) => <NavItem key={r.route} item={r} active={false} rail={false} muted />)}
                    </div>
                )}
            </nav>

            {/* Fixed bottom block */}
            <div className="border-t border-slate-800 py-2">
                <NavItem item={{ label: 'Dashboard', route: 'dashboard', icon: Home }} active={false} rail={rail} />
                {!inOverlay && (
                    <button
                        onClick={onToggleRail}
                        title={rail ? 'Expand sidebar  [' : 'Collapse to icons  ['}
                        className={`mx-2 flex w-[calc(100%-1rem)] items-center gap-3 rounded-md py-2 text-sm text-slate-400 hover:bg-slate-800 hover:text-white ${rail ? 'justify-center' : 'px-3'}`}
                    >
                        {rail ? <ChevronsRight className="w-4 h-4" /> : <ChevronsLeft className="w-4 h-4" />}
                        {!rail && <span className="flex-1 text-left">Collapse</span>}
                        {!rail && <kbd className="rounded border border-slate-700 px-1.5 text-[10px] text-slate-500">[</kbd>}
                    </button>
                )}
                <button
                    onClick={() => router.post(route('logout'))}
                    title={rail ? 'Logout' : undefined}
                    className={`mx-2 flex w-[calc(100%-1rem)] items-center gap-3 rounded-md py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white ${rail ? 'justify-center' : 'px-3'}`}
                >
                    <LogOut className="w-4 h-4" />
                    {!rail && 'Logout'}
                </button>
            </div>
        </div>
    );
}

export default function ModuleLayout({ navConfig, title, breadcrumbs = [], children }) {
    const { auth, company } = usePage().props;
    const brand = company?.trading_name || company?.name || 'SparesPro';
    const [mobileOpen, setMobileOpen] = useState(false);
    const [rail, setRail] = useStored('nav.rail', false);
    const guide = usePageGuide();
    const ModuleIcon = navConfig.moduleIcon;
    const toggleRail = useCallback(() => setRail((r) => !r), [setRail]);

    // `[` toggles the icon rail (never while typing).
    useEffect(() => {
        function onKey(e) {
            if (e.key !== '[' || e.ctrlKey || e.metaKey || e.altKey) return;
            const t = e.target;
            if (t instanceof HTMLElement && (t.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName))) return;
            e.preventDefault();
            toggleRail();
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [toggleRail]);

    // Close the mobile menu once a link is followed.
    useEffect(() => router.on('navigate', () => setMobileOpen(false)), []);

    return (
        <>
            <Head title={title} />
            <FlashToasts />
            <CommandPalette />
            <div className="min-h-screen bg-slate-50">
                {/* Desktop sidebar */}
                <aside className={`hidden lg:block fixed inset-y-0 left-0 z-30 transition-[width] duration-150 ${rail ? 'w-16' : 'w-64'}`}>
                    <Sidebar navConfig={navConfig} brand={brand} rail={rail} onToggleRail={toggleRail} />
                </aside>

                {/* Mobile overlay */}
                {mobileOpen && (
                    <div className="lg:hidden fixed inset-0 z-40">
                        <div className="absolute inset-0 bg-slate-900/50" onClick={() => setMobileOpen(false)} />
                        <aside className="absolute inset-y-0 left-0 w-64">
                            <Sidebar navConfig={navConfig} brand={brand} rail={false} inOverlay />
                        </aside>
                        <button
                            onClick={() => setMobileOpen(false)}
                            aria-label="Close menu"
                            className="absolute top-4 left-[17rem] p-2 rounded-lg bg-white shadow"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                )}

                <div className={`transition-[padding] duration-150 ${rail ? 'lg:pl-16' : 'lg:pl-64'}`}>
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
                            <Link href={route('guide.index')} title="User Guide" className="hover:text-orange-600"><BookOpen className="w-5 h-5" /></Link>
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
