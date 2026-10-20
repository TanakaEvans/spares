<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SystemRoute;
use Illuminate\Database\Seeder;

/**
 * Sensible default route grants for every seeded role, so an administrator
 * starts from working templates and only adjusts. Patterns are matched with
 * fnmatch() against the registered route names (SystemRouteSeeder must run
 * first). Additive and idempotent: it never removes a grant an admin added.
 */
class RolePermissionSeeder extends Seeder
{
    /** Read-only access that every operational role gets to the catalogue + vehicle reference. */
    private const LOOKUP = [
        'inventory.parts.index', 'inventory.parts.show', 'inventory.stock.index',
        'vehicle-ref.fitment', 'vehicle-ref.cross-ref', 'vehicle-ref.makes.index',
        'vehicle-ref.models.index', 'vehicle-ref.models.show', 'vehicle-ref.engines.index',
        'vehicle-ref.bulletins.index', 'vehicle-ref.supersessions.index',
    ];

    public const DEFAULTS = [
        'Admin' => [
            'admin.*', 'auth.*', 'imports.*', 'system.*', 'reports.*',
            '*.index', '*.show', '*.print', '*.export', 'sales.pos.approve-discount',
        ],
        'Manager' => [
            'inventory.*', 'sales.*', 'purchasing.*', 'workshop.*', 'customers.*',
            'suppliers.*', 'finance.*', 'vehicle-ref.*', 'reports.*', 'imports.*',
            'admin.health', 'admin.health.*', 'admin.currencies.*', 'admin.settings.index',
            'admin.sequences.index', 'admin.print.proof', 'admin.company.index', 'admin.backups.*',
            'admin.audit-log.*',
        ],
        'Cashier' => [
            'sales.pos', 'sales.pos.store', 'sales.pos.part-lookup', 'sales.pos.customer-lookup', 'sales.pos.verify-supervisor', 'sales.invoices.*', 'sales.credit-notes.*', 'sales.quotes.*',
            'sales.orders.*', 'sales.laybys.*', 'sales.delivery-notes.*', 'sales.promotions.index',
            'sales.price-lists.index', 'sales.price-lists.show',
            'customers.index', 'customers.show', 'customers.create', 'customers.store',
            'customers.edit', 'customers.update', 'customers.statement', 'customers.statement.csv',
            'customers.comms.*', 'customers.loyalty.index',
            'finance.receipts.*', 'reports.cashup', 'reports.cashup.print',
        ],
        'Buyer' => [
            'purchasing.*', 'suppliers.*', 'inventory.reorder.*', 'inventory.parts.*',
            'inventory.stock.index', 'inventory.stock.export', 'inventory.stock.reorder-levels',
            'inventory.categories.index', 'inventory.brands.index', 'inventory.serials.*',
            'reports.suppliers.index', 'reports.inventory.index', 'imports.*',
        ],
        'Accountant' => [
            'finance.*', 'reports.*', 'customers.index', 'customers.show', 'customers.statement',
            'customers.statement.csv', 'customers.hold', 'customers.groups.index',
            'suppliers.index', 'suppliers.show', 'sales.invoices.index', 'sales.invoices.show',
            'sales.invoices.print', 'sales.credit-notes.index', 'sales.credit-notes.show',
            'sales.credit-notes.print', 'purchasing.invoices.*', 'purchasing.supplier-credits.index',
            'purchasing.orders.index', 'purchasing.orders.show', 'purchasing.grns.index',
            'admin.currencies.*', 'admin.sequences.index', 'admin.health', 'admin.audit-log.*',
        ],
        'Workshop' => [
            'workshop.*', 'customers.index', 'customers.show', 'customers.create', 'customers.store',
            'sales.invoices.index', 'sales.invoices.show', 'sales.invoices.print',
            'sales.invoices.receipt', 'reports.workshop.index',
        ],
        'Inventory Clerk' => [
            'inventory.*', 'vehicle-ref.*', 'purchasing.grns.*', 'purchasing.orders.index',
            'purchasing.orders.show', 'purchasing.orders.print', 'purchasing.returns.*',
            'reports.inventory.index', 'suppliers.index', 'suppliers.show', 'imports.*',
        ],
    ];

    public function run(): void
    {
        $names = SystemRoute::pluck('id', 'name');
        if ($names->isEmpty()) {
            $this->command?->warn('No system routes registered — run SystemRouteSeeder first.');

            return;
        }

        foreach (self::DEFAULTS as $roleName => $patterns) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $patterns = $roleName === 'Admin' ? $patterns : array_merge($patterns, self::LOOKUP);
            $ids = self::expand($patterns, $names);
            $role->systemRoutes()->syncWithoutDetaching($ids);
            $this->command?->info(sprintf('%-16s %4d routes', $roleName, count($ids)));
        }
    }

    /** @return int[] */
    public static function expand(array $patterns, $names): array
    {
        $ids = [];
        foreach ($names as $name => $id) {
            foreach ($patterns as $pattern) {
                if ($name === $pattern || fnmatch($pattern, $name)) {
                    $ids[] = $id;
                    break;
                }
            }
        }

        return $ids;
    }
}
