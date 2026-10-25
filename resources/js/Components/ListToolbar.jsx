import { Download } from 'lucide-react';
import SearchInput from '@/Components/SearchInput';

/**
 * The standard list-toolbar: search, optional date range, optional export
 * (CSV/PDF via the matching `*.export` route which honours the same filters),
 * plus a `children` slot for the screen's own filters (status pills, selects).
 *
 * `filters` + `onFilter` come from `useListFilter` — the URL stays the source
 * of truth, so every export and pagination link inherits the active filters.
 */
export default function ListToolbar({
    filters,
    onFilter,
    searchPlaceholder = 'Search…',
    searchClass = 'w-72',
    dateRange = false,
    exportRoute,
    exportFilename,
    children,
}) {
    const exportUrl = exportRoute
        ? (format) => route(exportRoute, { ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)), format })
        : null;

    return (
        <div className="flex flex-wrap items-center gap-3">
            <SearchInput
                id="list-search"
                value={filters.search ?? ''}
                onSearch={(s) => onFilter({ search: s })}
                placeholder={searchPlaceholder}
                className={searchClass}
            />

            {dateRange && (
                <div className="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 py-1">
                    <input
                        type="date"
                        aria-label="From date"
                        value={filters.date_from ?? ''}
                        onChange={(e) => onFilter({ date_from: e.target.value })}
                        className="border-0 bg-transparent p-1 text-xs text-slate-600 focus:ring-0"
                    />
                    <span className="text-xs text-slate-300">→</span>
                    <input
                        type="date"
                        aria-label="To date"
                        value={filters.date_to ?? ''}
                        onChange={(e) => onFilter({ date_to: e.target.value })}
                        className="border-0 bg-transparent p-1 text-xs text-slate-600 focus:ring-0"
                    />
                    {(filters.date_from || filters.date_to) && (
                        <button
                            type="button"
                            onClick={() => onFilter({ date_from: '', date_to: '' })}
                            className="ml-1 rounded px-1.5 py-0.5 text-[10px] font-medium text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                        >
                            ✕
                        </button>
                    )}
                </div>
            )}

            {children}

            {exportRoute && (
                <div className="ml-auto flex items-center gap-1.5" title={`Download the filtered list${exportFilename ? ` (${exportFilename})` : ''}`}>
                    <Download className="w-3.5 h-3.5 text-slate-400" />
                    <a href={exportUrl('csv')} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        CSV
                    </a>
                    <a href={exportUrl('pdf')} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        PDF
                    </a>
                </div>
            )}
        </div>
    );
}
