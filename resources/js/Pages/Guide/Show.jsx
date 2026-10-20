import { useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import * as Icons from 'lucide-react';
import { ArrowLeft, ArrowRight, BookOpen } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';

export default function GuideShow({ auth, section, content, prev, next, toc }) {
    const html = useMemo(() => DOMPurify.sanitize(marked.parse(content ?? '')), [content]);
    const Icon = Icons[section.icon] ?? BookOpen;

    // Internal links in the markdown written as (guide:customers) or (route:sales.pos) resolve in-app.
    function onClick(e) {
        const a = e.target.closest('a');
        if (!a) return;
        const href = a.getAttribute('href') ?? '';
        if (href.startsWith('guide:')) { e.preventDefault(); router.visit(route('guide.show', href.slice(6))); }
        else if (href.startsWith('route:')) { e.preventDefault(); try { router.visit(route(href.slice(6))); } catch { /* unknown route */ } }
    }

    return (
        <AppLayout title={`Guide — ${section.name}`} auth={auth}>
            <Head title={`Guide — ${section.name}`} />
            <div className="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6">
                {/* Contents */}
                <aside className="hidden lg:block">
                    <Link href={route('guide.index')} className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-orange-600 mb-4">
                        <ArrowLeft className="w-4 h-4" /> All guides
                    </Link>
                    <nav className="space-y-0.5 sticky top-4">
                        {toc.map((t) => {
                            const TIcon = Icons[t.icon] ?? BookOpen;
                            const active = t.code === section.code;
                            return (
                                <Link key={t.code} href={route('guide.show', t.code)}
                                    className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm ${active ? 'bg-orange-50 text-orange-700 font-medium' : 'text-slate-600 hover:bg-slate-50'}`}>
                                    <TIcon className="w-4 h-4 shrink-0" /> {t.name}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                {/* Body */}
                <div className="min-w-0">
                    <div className="flex items-center gap-3 mb-5">
                        <div className={`flex h-11 w-11 items-center justify-center rounded-xl ${section.color} text-white`}><Icon className="w-6 h-6" /></div>
                        <div>
                            <div className="text-xs uppercase tracking-wide text-slate-400">User Guide</div>
                            <h1 className="text-2xl font-bold text-slate-900">{section.name}</h1>
                        </div>
                    </div>

                    <article onClick={onClick} className="guide-prose rounded-xl border border-slate-100 bg-white p-6 sm:p-8"
                        dangerouslySetInnerHTML={{ __html: html }} />

                    <div className="mt-6 flex items-center justify-between gap-3">
                        {prev
                            ? <Link href={route('guide.show', prev.code)} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><ArrowLeft className="w-4 h-4" /> {prev.name}</Link>
                            : <span />}
                        {next && <Link href={route('guide.show', next.code)} className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">{next.name} <ArrowRight className="w-4 h-4" /></Link>}
                    </div>
                </div>
            </div>

            <style>{`
                .guide-prose { color: #334155; line-height: 1.7; }
                .guide-prose h1 { display:none; }
                .guide-prose h2 { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 1.6rem 0 .6rem; padding-top: .8rem; border-top: 1px solid #f1f5f9; }
                .guide-prose h2:first-child { border-top: 0; padding-top: 0; margin-top: 0; }
                .guide-prose h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin: 1.1rem 0 .4rem; }
                .guide-prose p { margin: .5rem 0; }
                .guide-prose ol { counter-reset: step; margin: .5rem 0 .9rem; padding: 0; list-style: none; }
                .guide-prose ol > li { counter-increment: step; position: relative; padding-left: 2.1rem; margin: .45rem 0; }
                .guide-prose ol > li::before { content: counter(step); position: absolute; left: 0; top: .05rem; width: 1.5rem; height: 1.5rem; border-radius: 9999px; background: #ea580c; color: #fff; font-size: .75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
                .guide-prose ul { margin: .4rem 0 .9rem; padding-left: 1.2rem; list-style: disc; }
                .guide-prose ul li { margin: .3rem 0; }
                .guide-prose a { color: #ea580c; text-decoration: underline; text-underline-offset: 2px; }
                .guide-prose strong { color: #0f172a; }
                .guide-prose code { background: #f1f5f9; border-radius: 4px; padding: .1rem .35rem; font-size: .85em; }
                .guide-prose blockquote { border-left: 3px solid #fdba74; background: #fff7ed; padding: .6rem .9rem; border-radius: 0 8px 8px 0; margin: .9rem 0; color: #9a3412; }
                .guide-prose table { width: 100%; border-collapse: collapse; margin: .8rem 0; font-size: .9rem; }
                .guide-prose th, .guide-prose td { border: 1px solid #e2e8f0; padding: .45rem .6rem; text-align: left; }
                .guide-prose th { background: #f8fafc; }
                .guide-prose hr { border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
            `}</style>
        </AppLayout>
    );
}
