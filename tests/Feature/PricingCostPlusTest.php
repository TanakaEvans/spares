<?php

namespace Tests\Feature;

use App\Exceptions\UnpricedPartException;
use App\Models\Branch;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\SupplierPriceList;
use App\Services\PricingService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Cost-plus pricing (settings-driven): a part with no price list entry sells at
 * cost × (1 + markup%), VAT added on top by computeLine. A list entry always
 * wins; with no known cost the part stays unpriced.
 */
class PricingCostPlusTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricing;

    private SettingsService $settings;

    private Branch $branch;

    private PriceList $retail;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->pricing = app(PricingService::class);
        $this->settings = app(SettingsService::class);
        $this->branch = Branch::factory()->create(['is_main_branch' => true]);
        $this->retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
    }

    private function partWithCost(string $number, float $cost): Part
    {
        $part = Part::factory()->create(['part_number' => $number]);
        StockLevel::create(['part_id' => $part->id, 'branch_id' => $this->branch->id, 'qty_on_hand' => 5, 'average_cost' => $cost]);

        return $part;
    }

    public function test_cost_plus_uses_the_default_40_percent_markup(): void
    {
        $part = $this->partWithCost('CP-1', 100.00);
        // 100 × 1.40 = 140 excl VAT.
        $this->assertEquals(140.00, $this->pricing->priceFor($part, null, $this->branch->id));

        $info = $this->pricing->resolve($part, null, $this->branch->id);
        $this->assertSame('auto', $info['source']);
        $this->assertEquals(100.00, $info['cost']);
    }

    public function test_markup_is_configurable(): void
    {
        $this->settings->set('sales.default_markup_pct', 50);
        $part = $this->partWithCost('CP-2', 100.00);
        $this->assertEquals(150.00, $this->pricing->priceFor($part, null, $this->branch->id));
    }

    public function test_a_list_price_overrides_cost_plus(): void
    {
        $part = $this->partWithCost('CP-3', 100.00);
        PriceListItem::create(['price_list_id' => $this->retail->id, 'part_id' => $part->id, 'price' => 222.00]);

        $this->assertEquals(222.00, $this->pricing->priceFor($part, null, $this->branch->id));
        $this->assertSame('list', $this->pricing->resolve($part, null, $this->branch->id)['source']);
    }

    public function test_auto_pricing_can_be_switched_off(): void
    {
        $this->settings->set('sales.auto_price_from_cost', false);
        $part = $this->partWithCost('CP-4', 100.00);

        $this->expectException(UnpricedPartException::class);
        $this->pricing->priceFor($part, null, $this->branch->id);
    }

    public function test_a_part_with_no_known_cost_stays_unpriced(): void
    {
        $bare = Part::factory()->create(['part_number' => 'CP-5']);
        $this->assertSame('none', $this->pricing->resolve($bare, null, $this->branch->id)['source']);

        $this->expectException(UnpricedPartException::class);
        $this->pricing->priceFor($bare, null, $this->branch->id);
    }

    public function test_supplier_price_list_cost_is_used_when_there_is_no_stock(): void
    {
        $part = Part::factory()->create(['part_number' => 'CP-6']); // no stock level
        $supplier = Supplier::create(['supplier_number' => 'SUPP-1', 'name' => 'Sparepro', 'type' => 'local', 'is_active' => true]);
        $list = SupplierPriceList::create(['supplier_id' => $supplier->id, 'status' => 'active', 'name' => 'Costs', 'effective_date' => now()->toDateString()]);
        $list->items()->create(['part_id' => $part->id, 'supplier_part_number' => 'CP-6', 'description' => 'x', 'cost_price' => 80.00]);

        // 80 × 1.40 = 112.
        $this->assertEquals(112.00, $this->pricing->priceFor($part, null, $this->branch->id));
    }
}
