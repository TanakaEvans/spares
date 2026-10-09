<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SystemModule;
use App\Models\SystemRoute;
use Illuminate\Database\Seeder;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Registers every named application route as a permission key (AGENTS rule 5:
 * route name = permission key), grouped under its system module, and grants
 * the Superuser role the full set. Idempotent — safe to re-run after adding
 * routes; it never deletes rows so role assignments survive.
 */
class SystemRouteSeeder extends Seeder
{
    /** Module registry: label, nav prefixes that belong to it, icon, order. */
    private const MODULES = [
        ['name' => 'Inventory Management', 'prefix' => 'inventory', 'icon' => 'Package', 'match' => ['inventory.']],
        ['name' => 'Sales & POS', 'prefix' => 'sales', 'icon' => 'ShoppingCart', 'match' => ['sales.']],
        ['name' => 'Purchasing', 'prefix' => 'purchasing', 'icon' => 'ClipboardList', 'match' => ['purchasing.']],
        ['name' => 'Workshop', 'prefix' => 'workshop', 'icon' => 'Wrench', 'match' => ['workshop.']],
        ['name' => 'Customers', 'prefix' => 'customers', 'icon' => 'Users', 'match' => ['customers.']],
        ['name' => 'Suppliers', 'prefix' => 'suppliers', 'icon' => 'Truck', 'match' => ['suppliers.']],
        ['name' => 'Finance & Accounts', 'prefix' => 'finance', 'icon' => 'Landmark', 'match' => ['finance.']],
        ['name' => 'Vehicle Reference', 'prefix' => 'vehicle-reference', 'icon' => 'Car', 'match' => ['vehicle-ref.']],
        ['name' => 'Reports & Analytics', 'prefix' => 'reports', 'icon' => 'BarChart3', 'match' => ['reports.']],
        ['name' => 'System Administration', 'prefix' => 'system-admin', 'icon' => 'Settings', 'match' => ['admin.', 'auth.', 'imports.', 'system.', 'settings.']],
    ];

    public function run(): void
    {
        $modules = [];
        foreach (self::MODULES as $i => $m) {
            $modules[$m['prefix']] = SystemModule::updateOrCreate(
                ['prefix' => $m['prefix']],
                ['name' => $m['name'], 'icon' => $m['icon'], 'order' => $i + 1, 'status' => 'Enabled', 'description' => $m['name'].' module']
            );
        }
        $fallback = $modules['system-admin'];

        $count = 0;
        $ids = [];
        foreach (Route::getRoutes() as $route) {
            /** @var RoutingRoute $route */
            $name = $route->getName();
            if (! $name || str_starts_with($name, 'ignition.') || str_starts_with($name, 'debugbar.')
                || str_starts_with($name, 'sanctum.') || str_starts_with($name, 'storage.')
                || in_array($name, ['login', 'login.submit', 'logout', 'welcome', 'verify.document'], true)) {
                continue; // public/auth plumbing and vendor routes are not permissions
            }

            $module = $fallback;
            foreach (self::MODULES as $m) {
                foreach ($m['match'] as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        $module = $modules[$m['prefix']];
                        break 2;
                    }
                }
            }

            $defaults = $route->defaults;
            $row = SystemRoute::updateOrCreate(
                ['name' => $name],
                [
                    'system_module_id' => $module->id,
                    'uri' => $route->uri(),
                    'description' => (string) ($defaults['description'] ?? $this->humanise($name)),
                    'status' => 'active',
                ]
            );
            $ids[] = $row->id;
            $count++;
        }

        // Superuser always holds every permission.
        Role::where('name', 'Superuser')->first()?->systemRoutes()->syncWithoutDetaching($ids);

        $this->command?->info("System routes registered: {$count} across ".count($modules).' modules.');
    }

    private function humanise(string $name): string
    {
        return ucfirst(str_replace(['.', '-', '_'], ' ', $name));
    }
}
