import { router } from '@inertiajs/react';

/**
 * The list-toolbar contract (frontend twin of the ListQuery trait):
 * every list screen keeps `filters` (search, sort, dir, date_from, date_to,
 * plus the screen's own keys) as query params; calling `filter(params)`
 * merges them and re-requests the same route preserving scroll/state.
 * Empty values are dropped so URLs stay clean.
 */
export function useListFilter(routeName, filters) {
    return function filter(params = {}) {
        router.get(
            route(routeName),
            Object.fromEntries(
                Object.entries({ ...filters, ...params })
                    .filter(([, v]) => v !== '' && v != null)
            ),
            { preserveState: true, replace: true }
        );
    };
}

/**
 * Column-header sort toggle: click a sortable column once for asc,
 * again for desc, third time back to the default ordering.
 */
export function nextSort(filters, key) {
    if (filters.sort !== key) return { sort: key, dir: 'asc' };
    if (filters.dir === 'asc') return { sort: key, dir: 'desc' };
    return { sort: '', dir: '' };
}
