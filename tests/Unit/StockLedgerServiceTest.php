<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\StockLevel;
use App\Services\SettingsService;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockLedgerService $stock;

    private Branch $branch;

    private int $partId = 101;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stock = app(StockLedgerService::class);
        $this->branch = Branch::factory()->create();
    }

    private function level(): StockLevel
    {
        return StockLevel::where('part_id', $this->partId)
            ->where('branch_id', $this->branch->id)
            ->first();
    }

    public function test_receipt_increases_stock_and_sets_avco(): void
    {
        $entry = $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 5.00, 'GRN', 1);

        $this->assertSame(10.0, (float) $entry->running_balance);
        $this->assertSame(10.0, (float) $this->level()->qty_on_hand);
        $this->assertSame(5.0, (float) $this->level()->average_cost);
    }

    public function test_avco_recalculates_as_weighted_average(): void
    {
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 5.00);
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 7.00);

        // (10×5 + 10×7) / 20 = 6.00
        $this->assertSame(6.0, (float) $this->level()->average_cost);
        $this->assertSame(20.0, (float) $this->level()->qty_on_hand);
    }

    public function test_sale_decreases_stock_at_avco_without_changing_it(): void
    {
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 5.00);
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 7.00);

        $sale = $this->stock->post($this->partId, $this->branch, 'SALE', 5, null, 'SalesDocument', 42);

        $this->assertSame(-5.0, (float) $sale->qty);
        $this->assertSame(6.0, (float) $sale->unit_cost);       // issued at AVCO
        $this->assertSame(15.0, (float) $sale->running_balance);
        $this->assertSame(6.0, (float) $this->level()->average_cost); // unchanged
    }

    public function test_negative_stock_blocked_by_default(): void
    {
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 3, 5.00);

        $this->expectException(InsufficientStockException::class);

        $this->stock->post($this->partId, $this->branch, 'SALE', 5);
    }

    public function test_negative_stock_allowed_when_branch_setting_enables_it(): void
    {
        app(SettingsService::class)->set('inventory.allow_negative_stock', true, $this->branch);

        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 3, 5.00);
        $entry = $this->stock->post($this->partId, $this->branch, 'SALE', 5);

        $this->assertSame(-2.0, (float) $entry->running_balance);
    }

    public function test_running_balance_tracks_through_mixed_movements(): void
    {
        $this->stock->post($this->partId, $this->branch, 'OPENING_BALANCE', 10, 4.00);
        $this->stock->post($this->partId, $this->branch, 'SALE', 3);
        $this->stock->post($this->partId, $this->branch, 'RETURN_IN', 1, 4.00);
        $this->stock->post($this->partId, $this->branch, 'ADJUSTMENT_OUT', 2);

        $this->assertSame(6.0, (float) $this->level()->qty_on_hand);
        $this->assertSame([10.0, 7.0, 8.0, 6.0], \App\Models\StockLedgerEntry::orderBy('id')
            ->pluck('running_balance')->map(fn ($v) => (float) $v)->all());
    }

    public function test_in_movements_require_unit_cost(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10);
    }

    public function test_unknown_type_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->stock->post($this->partId, $this->branch, 'MAGIC', 1, 1.0);
    }

    public function test_reservations_reduce_availability_and_block_over_reserving(): void
    {
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 5.00);

        $this->stock->reserve($this->partId, $this->branch, 6);
        $this->assertSame(4.0, $this->level()->qty_available);

        try {
            $this->stock->reserve($this->partId, $this->branch, 5);
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException) {
            // expected
        }

        $this->stock->releaseReservation($this->partId, $this->branch, 6);
        $this->assertSame(10.0, $this->level()->qty_available);
    }

    public function test_integrity_check_detects_tampered_cache(): void
    {
        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 5.00);

        $this->assertCount(0, $this->stock->verifyIntegrity());

        // Simulate corruption (a raw write bypassing the service — forbidden!).
        StockLevel::where('part_id', $this->partId)->update(['qty_on_hand' => 99]);

        $mismatches = $this->stock->verifyIntegrity();
        $this->assertCount(1, $mismatches);
        $this->assertSame(99.0, $mismatches[0]['cached']);
        $this->assertSame(10.0, $mismatches[0]['ledger']);
    }

    public function test_stock_is_branch_isolated(): void
    {
        $other = Branch::factory()->create();

        $this->stock->post($this->partId, $this->branch, 'PURCHASE_RECEIPT', 10, 5.00);
        $this->stock->post($this->partId, $other, 'PURCHASE_RECEIPT', 3, 6.00);

        $this->assertSame(10.0, (float) $this->level()->qty_on_hand);
        $this->assertSame(3.0, (float) StockLevel::where('part_id', $this->partId)
            ->where('branch_id', $other->id)->first()->qty_on_hand);
    }
}
