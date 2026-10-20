import { Head, Link } from '@inertiajs/react';
import * as Icons from 'lucide-react';
import { BookOpen, ArrowRight, Rocket } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';

export default function GuideIndex({ auth, sections }) {
    const start = sections.find((s) => s.code === 'getting-started');
    const rest = sections.filter((s) => s.code !== 'getting-started');

    return (
        <AppLayout title="User Guide" auth={auth}>
            <Head title="User Guide" />
            <div className="max-w-6xl mx-auto space-y-6">
                <div className="rounded-2xl bg-slate-900 text-white p-8 relative overflow-hidden">
                    <div className="absolute inset-0 opacity-20" style={{ background: 'radial-gradient(120% 120% at 15% 10%, rgba(234,88,12,0.6) 0%, rgba(2,6,23,0) 45%)' }} />
                    <div className="relative">
                        <div className="flex items-center gap-2 text-orange-400 text-sm font-medium"><BookOpen className="w-4 h-4" /> User Guide</div>
                        <h1 className="mt-2 text-3xl font-bold">How to use the system — step by step</h1>
                        <p className="mt-2 max-w-2xl text-slate-300">Plain-English walkthroughs for every job, page by page. New here? Start with Getting Started; otherwise jump straight to the task you need.</p>
                        {start && (
                            <Link href={route('guide.show', start.code)} className="mt-5 inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold hover:bg-orange-700">
                                <Rocket className="w-4 h-4" /> Start here
                            </Link>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {rest.map((s) => {
                        const Icon = Icons[s.icon] ?? BookOpen;
                        return (
                            <Link key={s.code} href={route('guide.show', s.code)}
                                className="group rounded-xl border border-slate-100 bg-white p-5 hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col">
                                <div className="flex items-center gap-3">
                                    <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${s.color} text-white`}><Icon className="w-5 h-5" /></div>
                                    <h2 className="text-base font-semibold text-slate-900 group-hover:text-orange-600">{s.name}</h2>
                                </div>
                                <p className="mt-2 text-sm text-slate-500 flex-1">{s.summary}</p>
                                <ul className="mt-3 space-y-1">
                                    {s.steps.slice(0, 5).map((st) => (
                                        <li key={st} className="flex items-center gap-1.5 text-xs text-slate-400">
                                            <ArrowRight className="w-3 h-3 text-orange-300" /> {st}
                                        </li>
                                    ))}
                                </ul>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
