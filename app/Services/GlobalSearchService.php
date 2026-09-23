<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route as RouteFacade;

class GlobalSearchService
{
    /**
     * Grouped palette results. Prefix operators scope the search:
     *   > pages/actions only · u: users only
     *   (p:/c:/d:/v: are reserved for parts/customers/documents/vehicles
     *    and activate as those modules land.)
     */
    public function search(string $query, User $user): array
    {
        $query = trim($query);
        $scope = null;

        foreach (['>' => 'pages', 'u:' => 'users', 'p:' => 'parts', 'c:' => 'customers', 'd:' => 'documents', 'v:' => 'vehicles'] as $prefix => $name) {
            if (str_starts_with($query, $prefix)) {
                $scope = $name;
                $query = trim(substr($query, strlen($prefix)));
                break;
            }
        }

        $groups = [];

        if ($scope === null || $scope === 'pages') {
            $pages = $this->searchPages($query);
            if ($pages !== []) {
                $groups[] = ['group' => 'Pages', 'items' => $pages];
            }
        }

        if (($scope === null || $scope === 'users') && $user->hasRole('Superuser')) {
            $users = $this->searchUsers($query);
            if ($users !== []) {
                $groups[] = ['group' => 'Users', 'items' => $users];
            }
        }

        return $groups;
    }

    private function searchPages(string $query): array
    {
        if ($query === '') {
            return [];
        }

        $needle = mb_strtolower($query);

        return collect(config('search_pages', []))
            ->filter(fn ($page) => RouteFacade::has($page['route']))
            ->filter(fn ($page) => str_contains(mb_strtolower($page['label'].' '.($page['keywords'] ?? '')), $needle))
            ->take(6)
            ->map(fn ($page) => [
                'type' => 'page',
                'label' => $page['label'],
                'icon' => $page['icon'] ?? 'file',
                'url' => route($page['route'], $page['params'] ?? []),
                'hint' => null,
            ])
            ->values()
            ->all();
    }

    private function searchUsers(string $query): array
    {
        if (mb_strlen($query) < 2) {
            return [];
        }

        return User::where(fn ($q) => $q
            ->where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%"))
            ->limit(4)
            ->get()
            ->map(fn (User $u) => [
                'type' => 'user',
                'label' => $u->name,
                'icon' => 'user',
                'url' => route('auth.users.show', $u->id),
                'hint' => $u->status === 'active' ? $u->username : "{$u->username} · inactive",
            ])
            ->all();
    }
}
