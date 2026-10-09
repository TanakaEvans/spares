<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * OPT-IN demo layer for development, training and testing only — never for a
 * live system. Layers fabricated data on top of the clean DatabaseSeeder:
 * demo logins per role, a synthetic ~10k-part catalogue with prices, demo
 * suppliers with costs, and (via DemoDataSeeder) thousands of service-posted
 * transactions. Run with: `php artisan db:seed --class=DemoSeeder`.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoSeeder is blocked in production.');

            return;
        }

        $this->call([
            DemoUsersSeeder::class,        // one login per role
            CatalogueSeeder::class,        // synthetic catalogue + retail prices
            DemoPartsSeeder::class,        // a dozen real-style parts with opening stock
            SupplierCatalogueSeeder::class, // demo suppliers, preferred supplier + cost per part
            StockBinSeeder::class,         // home the new stock in bins
            DemoDataSeeder::class,         // volume: customers, documents, jobs (service-posted)
        ]);
    }
}
