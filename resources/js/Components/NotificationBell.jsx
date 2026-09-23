import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';

const SEVERITY_DOT = {
    critical: 'bg-red-500',
    warning: 'bg-amber-500',
    info: 'bg-blue-400',
};

// The bell (10.13): unread count badge, dropdown of latest alerts,
// deep links per UI-13, visiting a link marks it read.
export default function NotificationBell({ dark = false }) {
    const { notifications } = usePage().props;
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        function onClickOutside(e) {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        }
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    if (!notifications) return null;

    function openItem(n) {
        setOpen(false);
        router.post(route('notifications.read', n.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                if (n.url) router.visit(n.url);
            },
        });
    }

    return (
        <div className="relative" ref={ref}>
            <button
                onClick={() => setOpen((o) => !o)}
                aria-label={`Notifications${notifications.unread ? ` (${notifications.unread} unread)` : ''}`}
                className={`relative p-2 rounded-lg transition-colors ${
                    dark ? 'text-slate-300 hover:bg-slate-800 hover:text-white' : 'text-slate-400 hover:bg-slate-100 hover:text-slate-600'
                }`}
            >
                <Bell className="w-5 h-5" />
                {notifications.unread > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-orange-600 px-1 text-[10px] font-bold text-white flex items-center justify-center">
                        {notifications.unread > 9 ? '9+' : notifications.unread}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-80 rounded-xl bg-white shadow-xl border border-slate-100 overflow-hidden z-50">
                    <div className="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                        <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Notifications
                        </span>
                        {notifications.unread > 0 && (
                            <button
                                onClick={() => router.post(route('notifications.read-all'), {}, { preserveScroll: true })}
                                className="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-orange-600"
                            >
                                <CheckCheck className="w-3.5 h-3.5" />
                                Mark all read
                            </button>
                        )}
                    </div>
                    <div className="max-h-96 overflow-y-auto">
                        {notifications.latest.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-slate-400">All quiet — nothing needs you.</p>
                        ) : (
                            notifications.latest.map((n) => (
                                <button
                                    key={n.id}
                                    onClick={() => openItem(n)}
                                    className={`w-full flex items-start gap-2.5 px-4 py-3 text-left border-b border-slate-50 hover:bg-slate-50 ${
                                        n.read ? 'opacity-60' : ''
                                    }`}
                                >
                                    <span className={`mt-1.5 w-2 h-2 rounded-full shrink-0 ${SEVERITY_DOT[n.severity] ?? SEVERITY_DOT.info}`} />
                                    <span className="flex-1 min-w-0">
                                        <span className={`block text-sm ${n.read ? '' : 'font-semibold'} text-slate-800 truncate`}>
                                            {n.title}
                                        </span>
                                        {n.message && (
                                            <span className="block text-xs text-slate-500 truncate">{n.message}</span>
                                        )}
                                    </span>
                                    <span className="text-[10px] text-slate-400 shrink-0">{n.when}</span>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
