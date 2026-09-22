<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->roles()->count() === 0) {
            return \Inertia\Inertia::render('Auth/Unauthorized')->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
