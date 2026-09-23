import { useEffect, useMemo, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { Info, X } from 'lucide-react';

/**
 * The collapsible page guide (docs/design/page-guide.md).
 * Collapsed by default; ⓘ button or F1 opens, Esc/✕ closes.
 * Content = shared `pageGuide` prop (markdown), resolved server-side
 * from the current route (DB override → shipped file).
 * Link schemes: route:name → in-app page · doc:path → docs viewer.
 */

export function PageGuideTrigger({ open, onToggle }) {
    const { pageGuide } = usePage().props;
    if (!pageGuide) return null;

    return (
        <button
            onClick={onToggle}
            aria-expanded={open}
            title="Page guide (F1)"
            className={`inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors ${
                open
                    ? 'bg-orange-100 text-orange-700'
                    : 'text-slate-400 hover:text-orange-600 hover:bg-orange-50'
            }`}
        >
            <Info className="w-4 h-4" />
            <span className="hidden sm:inline">Page guide</span>
            <kbd className="hidden md:inline text-[10px] text-slate-300 font-sans">F1</kbd>
        </button>
    );
}

export function PageGuidePanel({ open, onClose }) {
    const { pageGuide } = usePage().props;
    const panelRef = useRef(null);

    const html = useMemo(() => {
        if (!pageGuide) return '';
        const raw = marked.parse(pageGuide, { breaks: true });
        return DOMPurify.sanitize(raw, {
            ADD_ATTR: ['target'],
            // Permit the guide link schemes (resolved in handleClick) alongside normal URLs.
            ALLOWED_URI_REGEXP: /^(?:route:|doc:|https?:|mailto:|#)/i,
        });
    }, [pageGuide]);

    // Resolve guide link schemes to real navigation.
    function handleClick(e) {
        const a = e.target.closest('a');
        if (!a) return;
        const href = a.getAttribute('href') ?? '';

        if (href.startsWith('route:')) {
            e.preventDefault();
            const name = href.slice(6);
            if (route().has(name)) router.visit(route(name));
        } else if (href.startsWith('doc:')) {
            e.preventDefault();
            router.visit(`/help/docs/${href.slice(4)}`);
        }
    }

    if (!open || !pageGuide) return null;

    return (
        <div
            ref={panelRef}
            className="mb-4 rounded-xl border border-orange-200 bg-orange-50/60 px-5 py-4 animate-in"
            role="region"
            aria-label="Page guide"
        >
            <div className="flex items-start justify-between gap-4">
                <div
                    onClick={handleClick}
                    className="guide-content flex-1 text-sm text-slate-700 leading-relaxed
                        [&_h1]:text-base [&_h1]:font-bold [&_h1]:text-slate-900 [&_h1]:mb-2
                        [&_h2]:text-xs [&_h2]:font-semibold [&_h2]:uppercase [&_h2]:tracking-wider [&_h2]:text-slate-500 [&_h2]:mt-4 [&_h2]:mb-1.5
                        [&_p]:mb-2 [&_ul]:mb-2 [&_ul]:pl-5 [&_ul]:list-disc [&_li]:mb-0.5
                        [&_a]:text-orange-700 [&_a]:font-medium [&_a]:underline [&_a]:decoration-orange-300 hover:[&_a]:decoration-orange-600 [&_a]:cursor-pointer
                        [&_strong]:text-slate-900 [&_code]:font-mono [&_code]:text-xs [&_code]:bg-white [&_code]:border [&_code]:border-slate-200 [&_code]:rounded [&_code]:px-1"
                    dangerouslySetInnerHTML={{ __html: html }}
                />
                <button
                    onClick={onClose}
                    aria-label="Close page guide"
                    className="shrink-0 p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-white"
                >
                    <X className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
}

/** Hook wiring F1/Esc for the guide. */
export function usePageGuide() {
    const [open, setOpen] = useState(false);
    const { pageGuide } = usePage().props;

    useEffect(() => {
        function onKey(e) {
            if (e.key === 'F1') {
                e.preventDefault();
                if (pageGuide) setOpen((o) => !o);
            }
            if (e.key === 'Escape') setOpen(false);
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [pageGuide]);

    // New page = collapsed again (guides are pulled, not pushed).
    useEffect(() => setOpen(false), [pageGuide]);

    return { open, toggle: () => setOpen((o) => !o), close: () => setOpen(false) };
}
