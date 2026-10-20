<?php

namespace Tests\Feature;

use App\Models\ApprovedSupplier;
use App\Models\BinLocation;
use App\Models\Branch;
use App\Models\DataImport;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Role;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\OpeningBalanceService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data Upload › Parts & Stock: one sheet creates the part, prices it on the
 * retail list (VAT-inclusive input stored exclusive), loads opening stock
 * through the stock ledger, places the bin and links the preferred supplier
 * with its cost — then the opening value reconciles to the Inventory account.
 */
class DataUploadStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_parts_and_stock_sheet_lands_everywhere_it_should(): void
    {
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $branch = Branch::factory()->create(['is_main_branch' => true]);
        PartCategory::create(['name' => 'Oil Filters', 'code' => 'FLT-OIL', 'sort_order' => 1]);
        UnitOfMeasure::create(['name' => 'Each', 'abbreviation' => 'ea']);
        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true, 'is_active' => true]);

        $role = Role::create(['name' => 'Superuser']);
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);

        // A filled-in template, as the business would upload it.
        $dir = storage_path('app/private/imports/data');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir.DIRECTORY_SEPARATOR.'test-stock.csv';
        $header = ['Part Number', 'Description', 'OEM Number', 'Barcode', 'Category', 'Brand', 'Unit', 'Cost Price (excl VAT)', 'Selling Price (incl VAT)', 'Selling Price (excl VAT)', 'Qty On Hand', 'Bin', 'Reorder Point', 'Reorder Qty', 'Supplier'];
        $row = ['Z762', 'Oil filter — Hilux', '90915-YZZD4', '6001234567890', 'Oil Filters', 'GUD', 'Each', '85.00', '149.00', '', '24', 'A-01-01', '5', '20', 'GUD Distributors'];
        $bad = ['NO-COST', 'Part with stock but no cost', '', '', '', '', '', '', '', '', '3', '', '', '', ''];
        $out = fopen($file, 'w');
        foreach ([$header, $row, $bad] as $line) {
            fputcsv($out, $line, ',', '"', '', "\n");
        }
        fclose($out);

        $keys = ['part_number', 'description', 'oem_number', 'barcode', 'category', 'brand', 'unit', 'cost_price', 'selling_price_incl_vat', 'selling_price_excl_vat', 'qty_on_hand', 'bin', 'reorder_point', 'reorder_qty', 'supplier'];
        $map = array_combine($keys, range(0, 14));

        $response = $this->actingAs($user)->post(route('imports.store', 'stock'), [
            'path' => 'imports/data/test-stock.csv', 'original_name' => 'test-stock.csv', 'map' => $map,
        ]);

        $import = DataImport::latest('id')->first();
        $response->assertRedirect(route('imports.result', $import));
        $this->assertSame(1, $import->created_count, 'Row errors: '.json_encode($import->errors));
        $this->assertSame(1, $import->skipped_count, 'Row errors: '.json_encode($import->errors));
        $this->assertStringContainsString('cost price is needed', $import->errors[0]['message']);

        // Part + price (149 incl at 15% VAT → 129.57 excl).
        $part = Part::where('part_number', 'Z762')->firstOrFail();
        $this->assertSame('6001234567890', $part->barcode_ean);
        $this->assertEqualsWithDelta(129.57, (float) PriceListItem::where('price_list_id', $retail->id)->where('part_id', $part->id)->value('price'), 0.001);

        // Opening stock through the ledger at cost, in the bin, with reorder levels.
        $level = StockLevel::where('part_id', $part->id)->where('branch_id', $branch->id)->firstOrFail();
        $this->assertEqualsWithDelta(24, (float) $level->qty_on_hand, 0.001);
        $this->assertEqualsWithDelta(85, (float) $level->average_cost, 0.001);
        $this->assertSame(BinLocation::where('code', 'A-01-01')->value('id'), $level->bin_location_id);
        $this->assertEqualsWithDelta(5, (float) $level->reorder_point, 0.001);
        $this->assertDatabaseHas('stock_ledger', ['part_id' => $part->id, 'transaction_type' => 'OPENING_BALANCE', 'reference_type' => 'DataImport']);

        // Supplier created, preferred, with the cost on their price list.
        $supplier = Supplier::where('name', 'GUD Distributors')->firstOrFail();
        $this->assertTrue((bool) ApprovedSupplier::where('part_id', $part->id)->where('supplier_id', $supplier->id)->value('is_preferred'));
        $this->assertDatabaseHas('supplier_price_list_items', ['part_id' => $part->id, 'cost_price' => 85.00]);

        // Shelf value 24 × 85 = 2,040 awaits posting; one call reconciles it to Inventory (1310).
        $opening = app(OpeningBalanceService::class);
        $this->assertEqualsWithDelta(2040, $opening->inventoryGlGap(), 0.01);
        $opening->postOpeningInventory($user->id);
        $this->assertEqualsWithDelta(0, $opening->inventoryGlGap(), 0.01);

        // Re-uploading the same sheet updates rather than duplicating, and never re-posts stock.
        $out = fopen($file, 'w');
        foreach ([$header, $row] as $line) {
            fputcsv($out, $line, ',', '"', '', "\n");
        }
        fclose($out);
        $this->actingAs($user)->post(route('imports.store', 'stock'), ['path' => 'imports/data/test-stock.csv', 'original_name' => 'again.csv', 'map' => $map]);
        $this->assertSame(1, Part::where('part_number', 'Z762')->count());
        $this->assertEqualsWithDelta(24, (float) $level->fresh()->qty_on_hand, 0.001);
        $this->assertSame(1, DataImport::latest('id')->first()->updated_count);
    }
}
