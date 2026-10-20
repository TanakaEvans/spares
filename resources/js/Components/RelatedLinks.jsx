import { Link } from '@inertiajs/react';
import { ArrowRight, Link2 } from 'lucide-react';
import EntityLink, { entityHref } from '@/Components/EntityLink';

/**
 * The "where did this come from / where does it go" card for detail pages.
 * Shows the document's place in its workflow as a chain, plus grouped links
 * to everything it touches (customer, parts, journals…).
 *
 *   <RelatedLinks
 *     trace={[{ type: 'quote', id, label: 'QT-…' }, { type: 'order', id, label: 'SO-…' }, { type: 'invoice', current: true, label: 'INV-…' }]}
 *     groups={[{ title: 'Customer', items: [{ type: 'customer', id, label }] }, { title: 'Parts', items: [...] }]}
 *   />
 *
 * Items without an id (or a type without a page) render as plain text, so a
 * step that has not happened yet can be shown greyed with `pending: true`.
 */
export default function RelatedLinks({ trace = [], groups = [], title = 'Connected to' }) {
    const hasTrace = trace.length > 0;
    const visibleGroups = groups.filter((g) => (g.items ?? []).length > 0);
    if (!hasTrace && visibleGroups.length === 0) return null;

    return (
        <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-4">
            <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <Link2 className="h-4 w-4 text-orange-500" /> {title}
            </h2>

            {hasTrace && (
                <ol className="flex flex-wrap items-center gap-2 text-sm">
                    {trace.map((step, i) => {
                        const href = step.current || step.pending ? null : entityHref(step.type, step);
                        const cls = step.current
                            ? 'rounded-md bg-orange-600 px-2.5 py-1 font-semibold text-white'
                            : step.pending
                                ? 'rounded-md border border-dashed border-slate-300 px-2.5 py-1 text-slate-400'
                                : 'rounded-md border border-slate-200 px-2.5 py-1 font-medium text-slate-700 hover:border-orange-400 hover:text-orange-600';
                        return (
                            <li key={i} className="flex items-center gap-2">
                                {href ? <Link href={href} className={cls}>{step.label}</Link> : <span className={cls}>{step.label}</span>}
                                {i < trace.length - 1 && <ArrowRight className="h-3.5 w-3.5 text-slate-300" />}
                            </li>
                        );
                    })}
                </ol>
            )}

            {visibleGroups.length > 0 && (
                <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    {visibleGroups.map((g) => (
                        <div key={g.title}>
                            <dt className="text-xs uppercase tracking-wide text-slate-400">{g.title}</dt>
                            <dd className="mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                {g.items.map((it, i) => (
                                    <EntityLink key={i} type={it.type} id={it.id} number={it.number} label={it.label} params={it.params} mono={it.mono} />
                                ))}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </div>
    );
}
