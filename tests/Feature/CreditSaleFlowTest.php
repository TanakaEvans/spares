<?php

namespace Tests\Feature;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\CustomerOnHoldException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\GlPostingService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On-account trading and the credit controls that gate it (Modules 5.2/5.3):
 * debtors control posting, credit-limit block, on-hold block, walk-in guard.
 */
class CreditSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Part $part;

    private Customer $account;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'DB2074', 'description' => 'Brake Pads']);

        app(StockLedgerService::class)->post(
            $this->part->id, $this->branch, 'OPENING_BALANCE', 20, 22.50, 'Test', 1
        );

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 45.00]);

        $this->account = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'price_list_id' => $retail->id, 'payment_terms_days' => 30, 'credit_limit' => 200,
        ]);
    }

    private function sellOnAccount(Customer $customer, float $qty): \App\Models\SalesDocument
    {
        $sales = app(SalesPostingService::class);
        $maths = app(\App\Services\PricingService::class)->computeLine($qty, 45.00, 0);

        return $sales->postInvoice(
            $customer,
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => $qty, 'unit_price' => 45.00]],
            [['method' => 'account', 'amount' => $maths['line_total_incl']]],
        );
    }

    public function test_on_account_sale_debits_debtors_and_raises_balance(): void
    {
        $gl = app(GlPostingService::class);

        // 1 @ 45 excl → 51.75 incl.
        $invoice = $this->sellOnAccount($this->account, 1);

        $this->assertEqualsWithDelta(51.75, (float) $invoice->total_incl, 0.001);
        $this->assertEqualsWithDelta(51.75, $gl->accountBalance('1210'), 0.001);
        $this->assertEqualsWithDelta(51.75, $this->account->fresh()->arBalance(), 0.001);
    }

    public function test_sale_within_limit_succeeds_then_over_limit_blocks(): void
    {
        // Limit 200. First sale 3 @ 45 = 155.25 incl — OK.
        $this->sellOnAccount($this->account, 3);
        $this->assertEqualsWithDelta(155.25, $this->account->fresh()->arBalance(), 0.001);

        // Next 1 @ 45 = 51.75 → balance would be 207.00 > 200. Blocked.
        $this->expectException(CreditLimitExceededException::class);
        $this->sellOnAccount($this->account->fresh(), 1);
    }

    public function test_on_hold_customer_cannot_buy_on_account(): void
    {
        $this->account->update(['on_hold' => true, 'hold_reason' => 'Overdue 90 days']);

        $this->expectException(CustomerOnHoldException::class);
        $this->sellOnAccount($this->account->fresh(), 1);
    }

    public function test_walk_in_cannot_buy_on_account(): void
    {
        $walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'credit_limit' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->sellOnAccount($walkIn, 1);
    }

    public function test_on_hold_customer_can_still_pay_cash(): void
    {
        $this->account->update(['on_hold' => true, 'hold_reason' => 'Overdue']);

        $invoice = app(SalesPostingService::class)->postInvoice(
            $this->account->fresh(),
            $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 45.00]],
            [['method' => 'cash', 'amount' => 51.75, 'tendered' => 51.75]],
        );

        $this->assertSame('posted', $invoice->status);
    }
}
