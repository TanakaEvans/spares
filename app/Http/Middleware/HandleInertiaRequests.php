<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles') : null,
                // Route names this user may open ('*' = everything). Screens hide
                // what the user may not do; EnforceRoutePermission still enforces.
                'routes' => fn () => $request->user()
                    ? ($request->user()->hasRole('Superuser') ? ['*'] : $request->user()->getAccessibleRoutes())
                    : [],
            ],
            // The base currency, captured once (10.12) and used by MoneyDisplay everywhere.
            'currency' => fn () => $this->baseCurrency(),
            // The business identity, captured once under System Admin › Company and
            // reused everywhere (login branding, headers, titles).
            'company' => fn () => \App\Models\Company::query()->first(['name', 'trading_name', 'logo'])?->toArray(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            // Every page's collapsible guide (docs/design/page-guide.md) —
            // resolved automatically from the current route name.
            'pageGuide' => fn () => app(\App\Services\PageGuideService::class)
                ->contentFor($request->route()?->getName()),
            // The bell (10.13): unread count + latest few, on every page.
            'notifications' => fn () => $request->user() ? [
                'unread' => $request->user()->unreadNotifications()->count(),
                'latest' => $request->user()->notifications()->latest()->limit(7)->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'title' => $n->data['title'] ?? 'Notification',
                        'message' => $n->data['message'] ?? null,
                        'url' => $n->data['url'] ?? null,
                        'severity' => $n->data['severity'] ?? 'info',
                        'read' => $n->read_at !== null,
                        'when' => $n->created_at->diffForHumans(short: true),
                    ]),
            ] : null,
        ];
    }

    /** @return array{code:string,symbol:string,decimals:int} */
    private function baseCurrency(): array
    {
        try {
            $base = app(\App\Services\CurrencyService::class)->base();

            return ['code' => $base->code, 'symbol' => $base->symbol, 'decimals' => (int) $base->decimal_places];
        } catch (\Throwable) {
            return ['code' => 'ZAR', 'symbol' => 'R', 'decimals' => 2];
        }
    }
}
