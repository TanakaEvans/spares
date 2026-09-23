<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use Illuminate\Database\Seeder;

/**
 * Sales master data: a default retail price list + a trade list, prices for the
 * demo catalogue, the mandatory walk-in Cash Customer, and one demo account
 * customer. Idempotent (updateOrCreate on natural keys). Prices are
 * VAT-exclusive per docs/modules/02-sales/2.6-price-lists.md.
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

        // Price the whole demo catalogue: retail = cost×2, trade = cost×1.6,
        // derived from the opening-stock cost in DemoPartsSeeder.
        $costs = [
            '04152-38020' => 5.80, 'Z762' => 3.20, '17801-0L040' => 9.40,
            '23390-0L070' => 14.20, 'DB2074' => 22.50, 'BKR6E-11' => 2.10,
            'MAG-5W40-5L' => 21.00, '03C115561H' => 4.60, 'Z131' => 3.00,
            'G-HILUX-F' => 28.00, '90915-YZZD4' => 4.10,
        ];

        foreach ($costs as $partNumber => $cost) {
            $part = Part::where('part_number', $partNumber)->first();
            if (! $part) {
                continue; // demo parts not seeded in this environment
            }

            PriceListItem::updateOrCreate(
                ['price_list_id' => $retail->id, 'part_id' => $part->id],
                ['price' => round($cost * 2, 2)]
            );
            PriceListItem::updateOrCreate(
                ['price_list_id' => $trade->id, 'part_id' => $part->id],
                ['price' => round($cost * 1.6, 2)]
            );
        }

        $tradeGroup = CustomerGroup::updateOrCreate(
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

        // One demo account customer for credit-sale flows.
        Customer::updateOrCreate(
            ['customer_number' => 'TRADE-001'],
            [
                'type' => 'business',
                'name' => 'Highway Motors (Pvt) Ltd',
                'trading_name' => 'Highway Motors',
                'vat_number' => '2200123456',
                'phone' => '+263 772 000 111',
                'city' => 'Harare',
                'customer_group_id' => $tradeGroup->id,
                'payment_terms_days' => 30,
                'credit_limit' => 500,
                'is_active' => true,
            ]
        );

        $this->command?->info('Price lists, demo prices, Cash Customer and demo trade account seeded.');
    }
}
