<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Part;
use App\Models\StockLevel;
use App\Services\GlPostingService;
use App\Services\StockLedgerService;
use App\Services\StockTakeService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stock take (Module 1.4): snapshot → count → post variance as one adjustment.
 * Shortages write off, surpluses write on; counted-equals-system posts nothing.
 */
class StockTakeVarianceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Part $partA;

    private Part $partB;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->partA = Part::factory()->create(['part_number' => 'Z762']);
        $this->partB = Part::factory()->create(['part_number' => 'BKR6E-11']);

        $stock = app(StockLedgerService::class);
        $stock->post($this->partA->id, $this->branch, 'OPENING_BALANCE', 36, 3.20, 'Test', 1);
        $stock->post($this->partB->id, $this->branch, 'OPENING_BALANCE', 60, 2.10, 'Test', 1);
    }

    public function test_variance_posts_as_adjustment_and_corrects_levels(): void
    {
        $service = app(StockTakeService::class);
        $gl = app(GlPostingService::class);

        $take = $service->start($this->branch->id, 'full');
        $this->assertCount(2, $take->lines);

        // Count A short by 2 (34), B over by 1 (61).
        $lineA = $take->lines->firstWhere('part_id', $this->partA->id);
        $lineB = $take->lines->firstWhere('part_id', $this->partB->id);
        $service->recordCount($lineA, 34);
        $service->recordCount($lineB, 61);

        $service->moveToReview($take);
        $posted = $service->post($take);

        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->adjustment_id);

        $levelA = StockLevel::where('part_id', $this->partA->id)->where('branch_id', $this->branch->id)->first();
        $levelB = StockLevel::where('part_id', $this->partB->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(34, (float) $levelA->qty_on_hand, 0.001);
        $this->assertEqualsWithDelta(61, (float) $levelB->qty_on_hand, 0.001);

        // Ledger integrity holds after the adjustment.
        $this->assertCount(0, app(StockLedgerService::class)->verifyIntegrity($this->branch->id));
    }

    public function test_uncounted_line_causes_no_movement(): void
    {
        $service = app(StockTakeService::class);

        $take = $service->start($this->branch->id, 'full');
        // Count only A, leave B uncounted.
        $service->recordCount($take->lines->firstWhere('part_id', $this->partA->id), 36);
        $posted = $service->post($take);

        // Both unchanged; no adjustment needed (A matched, B skipped).
        $this->assertNull($posted->adjustment_id);
        $levelB = StockLevel::where('part_id', $this->partB->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEqualsWithDelta(60, (float) $levelB->qty_on_hand, 0.001);
    }

    public function test_spot_take_snapshots_only_named_parts(): void
    {
        $service = app(StockTakeService::class);

        $take = $service->start($this->branch->id, 'spot', [$this->partA->id]);
        $this->assertCount(1, $take->lines);
        $this->assertSame($this->partA->id, $take->lines->first()->part_id);
    }
}
