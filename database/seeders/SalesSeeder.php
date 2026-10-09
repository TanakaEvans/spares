<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\PriceList;
use Illuminate\Database\Seeder;

/**
 * Sales structure for a clean go-live: the default retail price list, a trade
 * list with its customer group, and the mandatory walk-in Cash Customer.
 * No prices and no demo accounts — real pricing arrives with the catalogue
 * (supplier price-list / CSV import) and real customers are captured by staff.
 * Idempotent (updateOrCreate on natural keys).
 */
class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $retail = PriceList::updateOrCreate(
            ['name' => 'Standard Retail'],
            ['type' => 'retail', 'is_default' => true, 'is_active' => true]
        );

        $trade = PriceList::updateOrCreate(
            ['name' => 'Trade'],
            ['type' => 'trade', 'is_default' => false, 'is_active' => true]
        );

        CustomerGroup::updateOrCreate(
            ['name' => 'Trade Accounts'],
            ['price_list_id' => $trade->id]
        );

        // The walk-in Cash Customer every POS sale falls back to.
        Customer::updateOrCreate(
            ['is_walk_in' => true],
            [
                'customer_number' => 'WALK-IN',
                'type' => 'cash',
                'name' => 'Cash Customer',
                'price_list_id' => $retail->id,
                'payment_terms_days' => 0,
                'credit_limit' => 0,
                'is_active' => true,
            ]
        );

        $this->command?->info('Price lists (Retail, Trade), Trade Accounts group and Cash Customer seeded.');
    }
}
