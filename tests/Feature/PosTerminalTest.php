<?php

namespace Tests\Feature;

use App\Models\BinLocation;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\PartSupersession;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Role;
use App\Models\SalesDocument;
use App\Models\StockLevel;
use App\Models\User;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemRouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The till's read side and post-sale flow (2.1): one lookup gives price, stock,
 * bin, category and supersession, in-stock first; category browse; top sellers
 * and today's takings on the idle screen; a posted sale lands back on the till
 * with its receipt summary (change due) instead of leaving the screen.
 */
class PosTerminalTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Branch $branch;

    private Customer $walkIn;

    private Part $pads;

    private Part $oldPads;

    private Part $disc;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();
        Role::create(['name' => 'Cashier', 'description' => 'Till']);
        (new SystemRouteSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->branch = Branch::factory()->create(['is_main_branch' => true]);
        $brakes = PartCategory::create(['name' => 'Brakes', 'code' => 'BRK', 'sort_order' => 1]);
        $padsCat = PartCategory::create(['name' => 'Brake Pads', 'code' => 'BRK-PAD', 'parent_id' => $brakes->id, 'sort_order' => 2]);
        $engine = PartCategory::create(['name' => 'Engine Parts', 'code' => 'ENG', 'sort_order' => 3]);

        $this->pads = Part::factory()->create(['part_number' => 'BI106', 'description' => 'BRAKE PAD SET - HILUX 09- (FRONT)', 'category_id' => $padsCat->id]);
        $this->oldPads = Part::factory()->create(['part_number' => 'BI005', 'description' => 'BRAKE PAD SET - HILUX 80- (FRONT)', 'category_id' => $padsCat->id]);
        $this->disc = Part::factory()->create(['part_number' => 'BD224', 'description' => 'BRAKE DISC - RUN-X 1.8', 'category_id' => $brakes->id]);
        Part::factory()->create(['part_number' => 'OF1', 'description' => 'OIL FILTER - HILUX', 'category_id' => $engine->id]);
        PartSupersession::create(['old_part_id' => $this->oldPads->id, 'new_part_id' => $this->pads->id, 'is_active' => true]);

        app(StockLedgerService::class)->post($this->pads->id, $this->branch, 'OPENING_BALANCE', 10, 200, 'Test', 1);
        $bin = BinLocation::create(['branch_id' => $this->branch->id, 'code' => 'A-01-02', 'is_active' => true]);
        StockLevel::where('part_id', $this->pads->id)->update(['bin_location_id' => $bin->id]);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        foreach ([[$this->pads, 400], [$this->oldPads, 350], [$this->disc, 500]] as [$part, $price]) {
            PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $part->id, 'price' => $price]);
        }
        $this->walkIn = Customer::create(['customer_number' => 'WALK-IN', 'type' => 'cash', 'name' => 'Cash Customer', 'is_walk_in' => true, 'credit_limit' => 0]);

        $this->cashier = User::factory()->create(['password_changed_at' => now()]);
        $this->cashier->roles()->attach(Role::where('name', 'Cashier')->first()->id);
    }

    public function test_lookup_returns_price_stock_bin_category_and_supersession_in_stock_first(): void
    {
        $parts = $this->actingAs($this->cashier)
            ->getJson(route('sales.pos.part-lookup', ['q' => 'hilux pad']))
            ->assertOk()->json('parts');

        $this->assertSame(['BI106', 'BI005'], array_column($parts, 'part_number'), 'in-stock first, word-order independent');
        $this->assertEquals([
            'price' => 400, 'priced' => true, 'on_hand' => 10, 'available' => 10, 'bin' => 'A-01-02', 'category' => 'Brake Pads',
        ], array_intersect_key($parts[0], array_flip(['price', 'priced', 'on_hand', 'available', 'bin', 'category'])));
        $this->assertSame('BI106', $parts[1]['superseded_by']);
        $this->assertEquals(0, $parts[1]['available']);
    }

    public function test_category_browse_includes_sub_categories(): void
    {
        $parts = $this->actingAs($this->cashier)
            ->getJson(route('sales.pos.part-lookup', ['category_id' => PartCategory::where('code', 'BRK')->value('id')]))
            ->assertOk()->json('parts');

        $this->assertEqualsCanonicalizing(['BI106', 'BI005', 'BD224'], array_column($parts, 'part_number'));
        $this->assertSame('BI106', $parts[0]['part_number']);
    }

    public function test_sale_returns_to_the_till_with_receipt_summary_and_feeds_idle_screen(): void
    {
        $this->actingAs($this->cashier)->post(route('sales.pos.store'), [
            'customer_id' => $this->walkIn->id,
            'lines' => [['part_id' => $this->pads->id, 'qty' => 2, 'discount_pct' => 0]],
            'payments' => [['method' => 'cash', 'amount' => 920.00, 'tendered' => 1000]],
        ])->assertSessionHasNoErrors()->assertRedirect(route('sales.pos'));

        $invoice = SalesDocument::where('document_type', 'invoice')->firstOrFail();

        $this->actingAs($this->cashier)->get(route('sales.pos'))
            ->assertInertia(fn ($page) => $page
                ->component('Sales/Pos/Terminal')
                ->where('completedSale.id', $invoice->id)
                ->where('completedSale.number', $invoice->document_number)
                ->where('completedSale.change', 80)
                ->where('completedSale.total', 920)
                ->where('today.count', 1)
                ->where('today.total', 920)
                ->where('topSellers.0.part_number', 'BI106')
                ->where('topSellers.0.available', 8)
                ->where('categories', fn ($cats) => collect($cats)->pluck('name')->all() === ['Brakes', 'Engine Parts']
                    && collect($cats)->firstWhere('name', 'Brakes')['count'] === 3)
                ->where('allowNegative', false));

        // The flash is one-shot: a refresh doesn't re-show the completion screen.
        $this->actingAs($this->cashier)->get(route('sales.pos'))->assertInertia(fn ($page) => $page->where('completedSale', null));
    }
}
