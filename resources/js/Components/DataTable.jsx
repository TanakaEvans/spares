import { Link, router } from '@inertiajs/react';
import EmptyState from '@/Components/EmptyState';

/**
 * The one table (component-standards §5).
 * columns: [{ key, label, render?, align?: 'right', className? }]
 * rows: array of records; rowHref(row) makes the whole row a link (UI-13).
 * pagination: Laravel paginator object (data/links/from/to/total) — pass the
 *   paginator itself as `rows.data` + `pagination`.
 */
export default function DataTable({
    columns,
    rows,
    rowHref,
    pagination,
    emptyTitle = 'Nothing here yet',
    emptyMessage,
    emptyAction,
}) {
    if (!rows || rows.length === 0) {
        return (
            <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                <EmptyState title={emptyTitle} message={emptyMessage} action={emptyAction} />
            </div>
        );
    }

    return (
        <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100 sticky top-0 bg-white">
                        {columns.map((col) => (
                            <th
                                key={col.key}
                                className={`px-4 py-3 first:pl-6 last:pr-6 ${col.align === 'right' ? 'text-right' : ''} ${col.className ?? ''}`}
                            >
                                {col.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                    {rows.map((row, i) => {
                        const href = rowHref?.(row);
                        return (
                            <tr
                                key={row.id ?? i}
                                onClick={href ? () => router.visit(href) : undefined}
                                className={`${href ? 'cursor-pointer hover:bg-slate-50/70' : 'hover:bg-slate-50/40'}`}
                            >
                                {columns.map((col) => (
                                    <td
                                        key={col.key}
                                        className={`px-4 py-2.5 first:pl-6 last:pr-6 ${
                                            col.align === 'right' ? 'text-right tabular-nums' : ''
                                        } ${col.className ?? ''}`}
                                    >
                                        {col.render ? col.render(row) : row[col.key]}
                                    </td>
                                ))}
                            </tr>
                        );
                    })}
                </tbody>
            </table>

            {pagination && pagination.last_page > 1 && (
                <div className="flex items-center justify-between border-t border-slate-100 px-6 py-3 text-sm text-slate-500">
                    <span>
                        {pagination.from}–{pagination.to} of {pagination.total}
                    </span>
                    <div className="flex gap-1">
                        {pagination.links?.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    preserveState
                                    preserveScroll
                                    className={`px-2.5 py-1 rounded-md ${
                                        link.active
                                            ? 'bg-orange-600 text-white font-semibold'
                                            : 'hover:bg-slate-100 text-slate-600'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="px-2.5 py-1 text-slate-300"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            )
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
