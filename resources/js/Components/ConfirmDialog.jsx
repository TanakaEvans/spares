import { useEffect, useRef } from 'react';

// The one confirmation dialog (component-standards §3): irreversible actions only.
// Cancel is focused by default; Esc/overlay = cancel. Destructive variant renders red.
export default function ConfirmDialog({
    open,
    title,
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    destructive = false,
    processing = false,
    onConfirm,
    onCancel,
}) {
    const cancelRef = useRef(null);

    useEffect(() => {
        if (open) cancelRef.current?.focus();

        function onKey(e) {
            if (e.key === 'Escape' && open) onCancel?.();
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, onCancel]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div className="absolute inset-0 bg-slate-900/50" onClick={onCancel} />
            <div className="relative w-full max-w-md rounded-xl bg-white shadow-xl p-6">
                <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
                {message && <p className="mt-2 text-sm text-slate-500">{message}</p>}
                <div className="mt-6 flex justify-end gap-3">
                    <button
                        ref={cancelRef}
                        type="button"
                        onClick={onCancel}
                        className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300"
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className={`rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 focus:outline-none focus:ring-2 ${
                            destructive
                                ? 'bg-red-600 hover:bg-red-700 focus:ring-red-300'
                                : 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-300'
                        }`}
                    >
                        {processing ? 'Working…' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
