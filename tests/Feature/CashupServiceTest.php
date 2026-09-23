<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\CashupService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daily cash-up (X/Z read): banked money (cash/card/eft) vs credit extended
 * (account), by payment method, from documents posted through the real services.
 */
class CashupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashup_splits_banked_from_credit_by_method(): void
    {
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $branch = Branch::factory()->create();
        $part = Part::factory()->create();
        app(StockLedgerService::class)->post($part->id, $branch, 'OPENING_BALANCE', 100, 2.00, 'Test', 1);

        $retail = PriceList::create(['name' => 'Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $part->id, 'price' => 10.00]);

        $walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);
        $account = Customer::create([
            'customer_number' => 'TRADE-1', 'type' => 'business', 'name' => 'Trade Co',
            'is_walk_in' => false, 'price_list_id' => $retail->id, 'credit_limit' => 1000,
        ]);

        $sales = app(SalesPostingService::class);
        // Cash sale: 1 @ 10 excl → 11.50 incl.
        $sales->postInvoice($walkIn, $branch->id, [['part_id' => $part->id, 'qty' => 1, 'unit_price' => 10.00]],
            [['method' => 'cash', 'amount' => 11.50, 'tendered' => 20.00]]);
        // Card sale: 2 @ 10 → 23.00 incl.
        $sales->postInvoice($walkIn, $branch->id, [['part_id' => $part->id, 'qty' => 2, 'unit_price' => 10.00]],
            [['method' => 'card', 'amount' => 23.00]]);
        // Account (credit) sale: 1 @ 10 → 11.50 on account.
        $sales->postInvoice($account, $branch->id, [['part_id' => $part->id, 'qty' => 1, 'unit_price' => 10.00]],
            [['method' => 'account', 'amount' => 11.50]]);

        $cashup = app(CashupService::class)->forDay(now()->toDateString(), $branch->id);
        $by = collect($cashup['by_method'])->keyBy('method');

        $this->assertEqualsWithDelta(11.50, $by['cash']['total'], 0.001);
        $this->assertEqualsWithDelta(23.00, $by['card']['total'], 0.001);
        $this->assertEqualsWithDelta(11.50, $by['account']['total'], 0.001);

        // Banked = cash + card + eft (not account); credit = account.
        $this->assertEqualsWithDelta(34.50, $cashup['totals']['banked'], 0.001);
        $this->assertEqualsWithDelta(11.50, $cashup['totals']['credit'], 0.001);
        $this->assertEqualsWithDelta(8.50, $cashup['totals']['change_given'], 0.001); // 20 − 11.50
        $this->assertSame(3, $cashup['totals']['invoice_count']);
    }
}
