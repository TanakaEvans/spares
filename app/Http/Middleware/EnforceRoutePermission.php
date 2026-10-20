<?php

namespace App\Http\Middleware;

use App\Models\SystemRoute;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The one place the role→route permission matrix is enforced (10.x RBAC).
 *
 * Every named route registered in `system_routes` is a permission key; a
 * signed-in user may only hit it when one of their roles holds it (Superuser
 * holds everything). Core plumbing routes — dashboard, module landing pages,
 * password change, logout, search, help, the bell — are exempt because every
 * staff member needs them. Before any routes are registered (fresh install,
 * tests that never seed them) enforcement is inactive so the system can boot.
 */
class EnforceRoutePermission
{
    /** Route-name prefixes every authenticated user may use. */
    public const EXEMPT_PREFIXES = [
        'dashboard', 'modules.', 'password.', 'logout', 'login', 'search.',
        'help.', 'notifications.', 'verify.', 'storage.', 'debugbar.', 'ignition.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $name = $request->route()?->getName();

        if (! $user || ! $name || self::isExempt($name) || $user->hasRole('Superuser')) {
            return $next($request);
        }

        if (! self::enforcementActive()) {
            return $next($request);
        }

        if (! $user->hasRouteAccess($name)) {
            Log::info('auth.route_denied', ['user_id' => $user->id, 'route' => $name]);

            $description = SystemRoute::where('name', $name)->value('description') ?? $name;

            return Inertia::render('Auth/Unauthorized', ['routeDescription' => $description])
                ->toResponse($request)
                ->setStatusCode(403);
        }

        return $next($request);
    }

    public static function isExempt(string $routeName): bool
    {
        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if ($routeName === rtrim($prefix, '.') || str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Enforcement switches on as soon as at least one route is registered as a permission. */
    public static function enforcementActive(): bool
    {
        return Cache::remember('rbac.enforcement_active', 60, fn () => SystemRoute::query()->exists());
    }
}
