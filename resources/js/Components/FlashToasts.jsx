import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, TriangleAlert, X, XCircle } from 'lucide-react';

const KINDS = {
    success: { icon: CheckCircle2, style: 'bg-green-50 border-green-200 text-green-800', auto: true },
    warning: { icon: TriangleAlert, style: 'bg-amber-50 border-amber-200 text-amber-800', auto: true },
    error: { icon: XCircle, style: 'bg-red-50 border-red-200 text-red-800', auto: false },
};

// Toasts for Inertia flash messages: top-right, success auto-dismisses,
// errors persist until dismissed (component-standards §4).
export default function FlashToasts() {
    const { flash } = usePage().props;
    const [toasts, setToasts] = useState([]);

    useEffect(() => {
        const next = [];
        for (const kind of Object.keys(KINDS)) {
            if (flash?.[kind]) {
                next.push({ id: `${kind}-${Date.now()}`, kind, message: flash[kind] });
            }
        }
        if (next.length) {
            setToasts((t) => [...t, ...next].slice(-3));
            for (const toast of next) {
                if (KINDS[toast.kind].auto) {
                    setTimeout(() => dismiss(toast.id), 5000);
                }
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash]);

    function dismiss(id) {
        setToasts((t) => t.filter((x) => x.id !== id));
    }

    if (toasts.length === 0) return null;

    return (
        <div className="fixed top-4 right-4 z-[60] space-y-2 w-80">
            {toasts.map(({ id, kind, message }) => {
                const { icon: Icon, style } = KINDS[kind];
                return (
                    <div
                        key={id}
                        role="alert"
                        className={`flex items-start gap-2.5 rounded-xl border px-4 py-3 shadow-lg text-sm ${style}`}
                    >
                        <Icon className="w-4.5 h-4.5 shrink-0 mt-0.5" style={{ width: 18, height: 18 }} />
                        <span className="flex-1">{message}</span>
                        <button onClick={() => dismiss(id)} aria-label="Dismiss" className="opacity-50 hover:opacity-100">
                            <X className="w-4 h-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
