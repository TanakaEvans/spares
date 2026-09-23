<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\GlPostingService;
use App\Services\Health\HealthCheckRunner;
use App\Services\OpeningBalanceService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The System Health runner (operations/system-health.md ★) and the opening-
 * balance migration that closes the Inventory-vs-GL gap.
 */
class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'Z762']);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 100, 3.20, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 6.40]);
        $walkIn = Customer::create([
            'customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer',
            'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0,
        ]);
        app(SalesPostingService::class)->postInvoice(
            $walkIn, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 6.40]],
            [['method' => 'cash', 'amount' => 7.36, 'tendered' => 7.36]],
        );
    }

    private function statuses(): array
    {
        $report = app(HealthCheckRunner::class)->run();
        $out = [];
        foreach ($report['groups'] as $checks) {
            foreach ($checks as $c) {
                $out[$c['key']] = $c['status'];
            }
        }

        return $out;
    }

    public function test_core_invariants_pass(): void
    {
        $s = $this->statuses();
        $this->assertSame('ok', $s['journals_balanced']);
        $this->assertSame('ok', $s['trial_balance']);
        $this->assertSame('ok', $s['ar_control']);
        $this->assertSame('ok', $s['ap_control']);
        $this->assertSame('ok', $s['stock_integrity']);
        $this->assertSame('ok', $s['open_period']);
    }

    public function test_stock_gl_check_fails_until_opening_posted(): void
    {
        // Opening stock was loaded via the ledger only → GL 1310 ≠ stock value.
        $this->assertSame('fail', $this->statuses()['stock_gl']);

        $opening = app(OpeningBalanceService::class);
        $gap = $opening->inventoryGlGap();
        $this->assertGreaterThan(0, $gap);

        $journal = $opening->postOpeningInventory();
        $this->assertNotNull($journal);

        // Now the Inventory control reconciles and the check passes.
        $this->assertEqualsWithDelta(0, $opening->inventoryGlGap(), 0.01);
        $this->assertSame('ok', $this->statuses()['stock_gl']);
    }

    public function test_opening_post_is_idempotent(): void
    {
        $opening = app(OpeningBalanceService::class);
        $opening->postOpeningInventory();
        // A second run finds no gap and posts nothing.
        $this->assertNull($opening->postOpeningInventory());
    }

    public function test_overall_summary_reflects_failures(): void
    {
        $report = app(HealthCheckRunner::class)->run();
        // stock_gl fails out of the box, so overall is fail.
        $this->assertSame('fail', $report['summary']['overall']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['fail']);

        app(OpeningBalanceService::class)->postOpeningInventory();
        $report = app(HealthCheckRunner::class)->run();
        $this->assertSame('ok', $report['summary']['overall']);
    }
}
