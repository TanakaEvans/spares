<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the `admin.*` / `auth.*` prefixes. Admin and Superuser always pass;
 * any other role passes only for a route it has been explicitly granted in
 * the permission matrix (e.g. a Manager given `admin.health`).
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $routeName = $request->route()?->getName();

        if ($user->hasAnyRole(['Admin', 'Superuser'])) {
            return $next($request);
        }

        if ($routeName && EnforceRoutePermission::enforcementActive() && $user->hasRouteAccess($routeName)) {
            return $next($request);
        }

        abort(403, 'Unauthorized. Admin access required.');
    }
}
