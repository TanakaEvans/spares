<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The sidebar contract (docs/design/navigation-and-layout.md §2):
 * every route a menu points at exists, and every screen is reachable from a
 * menu — no orphan pages that users can only find by typing a URL.
 */
class NavigationCoverageTest extends TestCase
{
    /** Screens deliberately NOT in a sidebar, each with its reason. */
    private const NOT_IN_A_MENU = [
        'dashboard' => 'the dashboard itself (fixed bottom link in every sidebar)',
        'welcome' => 'public landing',
        'guide.index' => 'top-bar Guide icon',
        'settings.index' => 'legacy redirect to admin.settings.index',
        'system.settings' => 'legacy redirect to admin.settings.index',
        'verify.document' => 'public QR verification page',
        'vehicle-ref.fitment.vehicles' => 'JSON lookup for the fitment vehicle picker, not a screen',
        'admin.branches.create' => 'primary button on Branches',
        'admin.departments.create' => 'primary button on Departments',
        'admin.employees.create' => 'primary button on Employees',
        'admin.sections.create' => 'primary button on Sections',
        'auth.roles.create' => 'primary button on Roles & Permissions',
    ];

    /** @return array<string, list<string>> nav file => route names */
    private function navRoutes(): array
    {
        $out = [];
        foreach (glob(resource_path('js/nav/*.js')) as $file) {
            if (basename($file) === 'index.js') {
                continue;
            }
            preg_match_all("/route:\s*'([^']+)'/", file_get_contents($file), $m);
            $out[basename($file)] = array_values(array_unique($m[1]));
        }

        return $out;
    }

    public function test_every_sidebar_item_points_at_a_real_route(): void
    {
        $missing = [];
        foreach ($this->navRoutes() as $file => $routes) {
            foreach ($routes as $name) {
                if (! Route::has($name)) {
                    $missing[] = "{$file}: {$name}";
                }
            }
        }

        $this->assertSame([], $missing, 'Sidebar items point at routes that do not exist.');
    }

    public function test_every_screen_is_reachable_from_a_sidebar(): void
    {
        $inMenus = array_merge(...array_values($this->navRoutes()));
        $skip = '/(export|print|pdf|lookup|download|template|search|feed|palette|^help\.|^api\.|login|logout|password\.|verification|sanctum|ignition|debugbar|storage)/';

        $orphans = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $r) => in_array('GET', $r->methods(), true))
            ->filter(fn (LaravelRoute $r) => $r->getName() && ! str_contains($r->uri(), '{'))
            ->map(fn (LaravelRoute $r) => $r->getName())
            ->reject(fn (string $n) => preg_match($skip, $n) === 1)
            ->reject(fn (string $n) => in_array($n, $inMenus, true) || array_key_exists($n, self::NOT_IN_A_MENU))
            ->values()
            ->all();

        $this->assertSame([], $orphans, 'Screens no sidebar links to — add them to a resources/js/nav config (or justify in NOT_IN_A_MENU).');
    }

    public function test_every_module_has_a_registered_nav_config(): void
    {
        $registry = file_get_contents(resource_path('js/nav/index.js'));
        preg_match_all("/key:\s*'([^']+)'/", file_get_contents(resource_path('js/modules.js')), $m);

        foreach ($m[1] as $key) {
            $this->assertMatchesRegularExpression(
                "/(^|\s)('{$key}'|{$key}):|^\s+{$key},/m",
                $registry,
                "Module '{$key}' has no nav config in resources/js/nav/index.js — its landing page would lose the sidebar."
            );
        }
    }
}
