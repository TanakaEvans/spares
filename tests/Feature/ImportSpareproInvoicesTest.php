<?php

namespace Tests\Feature;

use App\Models\ApprovedSupplier;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Services\GlPostingService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * spares:import-invoices loads a Sparepro invoice extract as opening stock:
 * parts created, quantities summed and costs weighted-averaged per part number,
 * stock posted through the ledger, Sparepro linked as preferred supplier, and
 * the GL inventory reconciled. Re-running the same file never double-counts.
 */
class ImportSpareproInvoicesTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        Branch::factory()->create(['is_main_branch' => true]);
        PartCategory::create(['name' => 'Unclassified', 'code' => 'MISC', 'sort_order' => 1, 'is_active' => true]);
        UnitOfMeasure::create(['name' => 'Each', 'abbreviation' => 'ea']);

        // B1170 appears twice (different invoices): 1 @ 156.80 and 5 @ 150.32
        // → 6 on hand, weighted-average 151.40. FA176: 5 @ 237.73.
        $this->file = tempnam(sys_get_temp_dir(), 'inv').'.csv';
        file_put_contents($this->file, implode("\n", [
            'Invoice Number,Date,Part Number,Description,Pac qty,Supp.Qty,Unit Price (ZAR),Nett Amount (ZAR)',
            'I001,2026-11-05,B1170,FIESTA FRONT,1,1,156.80,156.80',
            'I002,2026-11-06,B1170,MAZDA 323 FRONT,1,5,150.32,751.60',
            'I003,2026-11-05,FA176,AUDI/BMW GETZ,1,5,237.73,1188.65',
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_loads_parts_stock_supplier_and_reconciles_gl(): void
    {
        $this->artisan('spares:import-invoices', ['file' => $this->file])->assertSuccessful();

        $this->assertSame(2, Part::count());

        $b1170 = Part::where('part_number', 'B1170')->firstOrFail();
        $level = StockLevel::where('part_id', $b1170->id)->firstOrFail();
        $this->assertEquals(6, (float) $level->qty_on_hand);
        $this->assertEquals(151.40, round((float) $level->average_cost, 2));

        $supplier = Supplier::where('name', 'Sparepro')->firstOrFail();
        $this->assertTrue(ApprovedSupplier::where('part_id', $b1170->id)
            ->where('supplier_id', $supplier->id)->where('is_preferred', true)->exists());

        // Opening inventory journalised: GL 1310 == physical stock value (6×151.40 + 5×237.73).
        $stockValue = round(6 * 151.40 + 5 * 237.73, 2);
        $this->assertEquals($stockValue, round(app(GlPostingService::class)->accountBalance('1310'), 2));
        $this->assertTrue(app(StockLedgerService::class)->verifyIntegrity()->isEmpty());
    }

    public function test_re_running_the_same_file_does_not_double_count(): void
    {
        $this->artisan('spares:import-invoices', ['file' => $this->file])->assertSuccessful();
        $this->artisan('spares:import-invoices', ['file' => $this->file])->assertSuccessful();

        $level = StockLevel::whereHas('part', fn ($q) => $q->where('part_number', 'B1170'))->firstOrFail();
        $this->assertEquals(6, (float) $level->qty_on_hand);
        $this->assertSame(2, Part::count());
    }
}
