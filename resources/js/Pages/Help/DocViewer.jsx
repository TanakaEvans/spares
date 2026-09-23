import { useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { ArrowLeft, BookOpen } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';

// Read-only viewer for the docs/ tree. Relative .md links resolve to
// other docs pages so the whole spec is browsable in-app.
export default function DocViewer({ auth, path, content }) {
    const html = useMemo(
        () => DOMPurify.sanitize(marked.parse(content ?? '')),
        [content]
    );

    function handleClick(e) {
        const a = e.target.closest('a');
        if (!a) return;
        const href = a.getAttribute('href') ?? '';

        if (href.startsWith('http') || href.startsWith('#')) return; // external/anchor: default

        e.preventDefault();
        // Resolve relative to the current doc's directory.
        const dir = path.includes('/') ? path.slice(0, path.lastIndexOf('/') + 1) : '';
        const url = new URL(href, `http://x/${dir}`);
        const target = url.pathname.replace(/^\//, '');
        router.visit(`/help/docs/${target}`);
    }

    return (
        <AppLayout title="Documentation" auth={auth}>
            <Head title="Documentation" />
            <div className="max-w-4xl mx-auto space-y-4">
                <div className="flex items-center justify-between">
                    <button
                        onClick={() => window.history.back()}
                        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back
                    </button>
                    <span className="inline-flex items-center gap-1.5 text-xs text-slate-400 font-mono">
                        <BookOpen className="w-3.5 h-3.5" />
                        docs/{path}
                    </span>
                </div>

                <article
                    onClick={handleClick}
                    className="bg-white rounded-xl shadow-sm border border-slate-100 px-8 py-6 text-sm leading-relaxed text-slate-700
                        [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:text-slate-900 [&_h1]:mb-4 [&_h1]:mt-2
                        [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-slate-900 [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:border-b [&_h2]:border-slate-100 [&_h2]:pb-1.5
                        [&_h3]:text-base [&_h3]:font-semibold [&_h3]:text-slate-800 [&_h3]:mt-5 [&_h3]:mb-2
                        [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:pl-5 [&_ul]:list-disc [&_ol]:mb-3 [&_ol]:pl-5 [&_ol]:list-decimal [&_li]:mb-1
                        [&_a]:text-orange-700 [&_a]:underline [&_a]:decoration-orange-300 hover:[&_a]:decoration-orange-600 [&_a]:cursor-pointer
                        [&_blockquote]:border-l-4 [&_blockquote]:border-orange-200 [&_blockquote]:bg-orange-50/50 [&_blockquote]:px-4 [&_blockquote]:py-2 [&_blockquote]:rounded-r-lg [&_blockquote]:mb-3 [&_blockquote]:text-slate-600
                        [&_table]:w-full [&_table]:mb-4 [&_table]:text-xs [&_th]:text-left [&_th]:bg-slate-50 [&_th]:px-3 [&_th]:py-2 [&_th]:font-semibold [&_td]:px-3 [&_td]:py-1.5 [&_td]:border-t [&_td]:border-slate-100
                        [&_code]:font-mono [&_code]:text-xs [&_code]:bg-slate-50 [&_code]:border [&_code]:border-slate-200 [&_code]:rounded [&_code]:px-1
                        [&_pre]:bg-slate-900 [&_pre]:text-slate-100 [&_pre]:rounded-lg [&_pre]:p-4 [&_pre]:overflow-x-auto [&_pre]:mb-4 [&_pre_code]:bg-transparent [&_pre_code]:border-0 [&_pre_code]:text-slate-100"
                    dangerouslySetInnerHTML={{ __html: html }}
                />
            </div>
        </AppLayout>
    );
}
