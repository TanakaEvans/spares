<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Clean initial setup for a real deployment (Limpopo, South Africa):
 * `php artisan migrate:fresh --seed`.
 *
 * Seeds only genuine structure and reference data — company & branch, roles,
 * the admin login, every route as a permission key, the ZAR finance backbone
 * (currencies, document sequences, chart of accounts, open periods), the
 * vehicle / parts reference packs, price-list structure, the Cash Customer,
 * the workshop labour pack and a warehouse bin grid.
 *
 * Nothing is fabricated: no parts, suppliers, customers, staff or transactions.
 * Real parts and suppliers load through the Import wizard / supplier price-list
 * import. Demo and volume data is opt-in only: `php artisan db:seed --class=DemoSeeder`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Identity & access
            SuperuserSeeder::class,        // company, main branch, roles, admin login
            SystemRouteSeeder::class,      // every route registered as a permission key
            RolePermissionSeeder::class,   // default grants per role (additive, idempotent)

            // Finance backbone (ZAR)
            CurrencySeeder::class,
            NumberSequenceSeeder::class,
            ChartOfAccountsSeeder::class,
            FinancialPeriodSeeder::class,

            // Reference data (real SA car parc, categories, brands, units)
            ReferenceDataSeeder::class,

            // Operating structure
            SalesSeeder::class,            // Retail + Trade price lists, Trade group, Cash Customer
            WorkshopSeeder::class,         // labour code pack (rand)
            StockBinSeeder::class,         // warehouse bin grid for the main branch
        ]);
    }
}
