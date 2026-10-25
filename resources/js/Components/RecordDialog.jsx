import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

/**
 * The shared quick create/edit form modal (component-standards §3 `form`):
 * Radix Dialog, title = the action, ghost Cancel + one primary. While the
 * form is dirty an outside click is ignored and Esc/✕ asks before discarding.
 * Pair with `useRecordEditor()` from '@/lib/records'.
 */
export default function RecordDialog({
    open, onClose, title, description, onSubmit, processing = false, dirty = false,
    submitLabel = 'Save', size = 'md', submitDisabled = false, children,
}) {
    function requestClose(next) {
        if (next) return;
        if (dirty && !window.confirm('Discard your unsaved changes?')) return;
        onClose();
    }

    return (
        <Dialog open={open} onOpenChange={requestClose}>
            <DialogContent
                className={`bg-white ${size === 'lg' ? 'sm:max-w-3xl' : 'sm:max-w-xl'}`}
                onInteractOutside={(e) => { if (dirty) e.preventDefault(); }}
            >
                <form
                    onSubmit={(e) => { e.preventDefault(); onSubmit(); }}
                    className="space-y-5"
                >
                    <DialogHeader>
                        <DialogTitle className="text-lg font-semibold text-slate-900">{title}</DialogTitle>
                        {description && <DialogDescription className="text-sm text-slate-500">{description}</DialogDescription>}
                    </DialogHeader>
                    <div className="space-y-4">{children}</div>
                    <DialogFooter className="gap-2">
                        <button type="button" onClick={() => requestClose(false)} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing || submitDisabled}
                            className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                        >
                            {processing ? 'Saving…' : submitLabel}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
