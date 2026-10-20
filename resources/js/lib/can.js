import { usePage } from '@inertiajs/react';

/**
 * Permission helpers for the UI. The server shares `auth.routes` — the route
 * names the user may open (`['*']` for Superuser). Screens hide what the user
 * may not do; the server still enforces (EnforceRoutePermission).
 */
export function canWith(routes, routeName) {
    if (!routeName) return true;
    if (!routes || routes.includes('*')) return true;
    return routes.includes(routeName);
}

export function canAnyWith(routes, prefixes) {
    if (!routes) return false;
    if (routes.includes('*')) return true;
    return routes.some((r) => prefixes.some((p) => r.startsWith(p)));
}

/** Prefixes that make a module card visible. */
export const MODULE_PREFIXES = {
    inventory: ['inventory.'],
    sales: ['sales.'],
    purchasing: ['purchasing.'],
    workshop: ['workshop.'],
    customers: ['customers.'],
    suppliers: ['suppliers.'],
    finance: ['finance.'],
    'vehicle-reference': ['vehicle-ref.'],
    reports: ['reports.'],
    'system-admin': ['admin.', 'auth.', 'imports.', 'system.'],
};

export function useCan() {
    const { auth } = usePage().props;
    const routes = auth?.routes ?? ['*'];
    return {
        can: (routeName) => canWith(routes, routeName),
        canModule: (moduleKey) => canAnyWith(routes, MODULE_PREFIXES[moduleKey] ?? [`${moduleKey}.`]),
        routes,
    };
}
