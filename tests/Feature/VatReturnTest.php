<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\GlPostingService;
use App\Services\SalesPostingService;
use App\Services\StockLedgerService;
use App\Services\VatReturnService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * VAT returns (Module 7.6) reconcile to the VAT Output (2210) and Input (2220)
 * movements — output − input = net payable.
 */
class VatReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $branch = Branch::factory()->create();
        $part = Part::factory()->create(['part_number' => 'Z762']);
        app(StockLedgerService::class)->post($part->id, $branch, 'OPENING_BALANCE', 100, 3.20, 'Test', 1);
        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $part->id, 'price' => 6.40]);
        $walkIn = Customer::create(['customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash', 'is_walk_in' => true, 'price_list_id' => $retail->id, 'credit_limit' => 0]);

        // Two sales → output VAT 2 × 0.96 = 1.92.
        for ($i = 0; $i < 2; $i++) {
            app(SalesPostingService::class)->postInvoice($walkIn, $branch->id,
                [['part_id' => $part->id, 'qty' => 1, 'unit_price' => 6.40]],
                [['method' => 'cash', 'amount' => 7.36, 'tendered' => 7.36]]);
        }

        // A manual input-VAT entry (e.g. a purchase): DR 2220 / CR 1120 = 5.00.
        app(GlPostingService::class)->post('purchase', now(), 'Input VAT', [
            ['account' => '2220', 'debit' => 5.00, 'credit' => 0],
            ['account' => '1120', 'debit' => 0, 'credit' => 5.00],
        ], fromSubLedger: true);
    }

    public function test_compute_matches_ledger(): void
    {
        $c = app(VatReturnService::class)->compute('2000-01-01', now()->toDateString());
        $this->assertEqualsWithDelta(1.92, $c['output_vat'], 0.01);
        $this->assertEqualsWithDelta(5.00, $c['input_vat'], 0.01);
        $this->assertEqualsWithDelta(-3.08, $c['net_payable'], 0.01); // reclaim
    }

    public function test_generate_persists_a_draft_return(): void
    {
        $r = app(VatReturnService::class)->generate('2000-01-01', now()->toDateString());
        $this->assertSame('draft', $r->status);
        $this->assertEqualsWithDelta(1.92, (float) $r->output_vat, 0.01);
        $this->assertEqualsWithDelta(-3.08, (float) $r->net_payable, 0.01);
        $this->assertDatabaseHas('vat_returns', ['reference' => $r->reference, 'status' => 'draft']);
    }
}
