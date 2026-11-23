<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BarcodeService;
use App\Services\GrnPostingService;
use App\Services\LabelService;
use App\Services\SettingsService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemRouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Inventory › Print Labels: barcode encoding (Code 128 / EAN-13), what each
 * label encodes, quantity defaults (on hand / received), the PDF in every
 * format, sheet start position, batching, density warnings and RBAC.
 */
class BarcodeLabelsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $user;

    private Part $stocked;   // D3117: 3 on hand @ cost 100 → auto 140 excl / 161 incl

    private Part $longCode;  // TR51574/TR5140: no stock

    private Part $ean;       // carries a valid manufacturer EAN-13

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();
        (new CurrencySeeder)->run();

        $this->branch = Branch::factory()->create(['is_main_branch' => true]);
        PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);

        $this->stocked = Part::factory()->create(['part_number' => 'D3117', 'description' => 'BRAKE PADS FRONT - FIESTA/MAZDA 1997']);
        app(StockLedgerService::class)->post($this->stocked->id, $this->branch, 'OPENING_BALANCE', 3, 100.00);
        $this->longCode = Part::factory()->create(['part_number' => 'TR51574/TR5140']);
        $this->ean = Part::factory()->create(['part_number' => 'EAN-1', 'barcode_ean' => '5012345678900']);

        // Staff always hold a role (users with none are refused everywhere); with no
        // system routes registered, RBAC route checks are not yet enforced.
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach(Role::create(['name' => 'Manager', 'description' => 'Manager'])->id);
    }

    // ── Encoding ─────────────────────────────────────────────────────────

    public function test_encoder_output_is_stable_and_well_formed(): void
    {
        $b = app(BarcodeService::class);

        // Golden strings, verified decodable by an independent reader (zxing-cpp).
        $this->assertSame('1101001000010110001000101110111101101100011010011100110111100101001100011101011', $b->code128('D3117'));
        $this->assertSame('10100011010110011001101101111010100011011100101010101000010001001001000111010011100101110010101', $b->ean13('5012345678900'));

        $this->assertSame(0, $b->eanCheckDigit('501234567890'));
        $this->assertSame('ean13', $b->choose('5012345678900')['symbology']);
        $this->assertSame('code128', $b->choose('5012345678901')['symbology'], 'bad check digit falls back to Code 128');
        // Every Code 128 symbol is 11 modules: START + A + B + check = 4 × 11, then a 13-module STOP.
        $this->assertSame(4 * 11 + 13, strlen($b->code128('AB')));
        $this->assertSame('', $b->code128("\x01\x02"), 'non-printables are dropped');
        $svg = $b->svg('D3117', 'code128', 40, 10);
        $this->assertMatchesRegularExpression('/^<svg [^>]*viewBox="0 0 99 /', $svg);
        $this->assertDoesNotMatchRegularExpression('/^<svg[^>]* (width|height)=/', $svg, 'viewBox-only SVG so DomPDF fills the box exactly');
    }

    public function test_label_encodes_ean_when_valid_otherwise_the_part_number(): void
    {
        $labels = app(LabelService::class);
        $this->assertSame(['symbology' => 'ean13', 'value' => '5012345678900'], $labels->encodingFor($this->ean));
        $this->assertSame(['symbology' => 'code128', 'value' => 'D3117'], $labels->encodingFor($this->stocked));
    }

    public function test_long_code_is_flagged_for_small_labels_only(): void
    {
        $line = app(LabelService::class)->line($this->longCode, $this->branch);
        $this->assertFalse($line['fits']['thermal'], '50 mm label at 203 dpi cannot print bars 2 dots wide');
        $this->assertTrue($line['fits']['a4']);
        $this->assertTrue($line['fits']['roll80']);

        $this->assertTrue(app(LabelService::class)->line($this->stocked, $this->branch)['fits']['thermal']);
    }

    // ── Screen ───────────────────────────────────────────────────────────

    public function test_from_a_part_one_label_per_unit_on_hand_at_the_till_price(): void
    {
        $this->actingAs($this->user)
            ->get(route('inventory.labels.index', ['part' => $this->stocked->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Labels/Index')
                ->where('lines.0.part_number', 'D3117')
                ->where('lines.0.qty', 3)
                ->where('lines.0.price_incl', 161)
                ->where('lines.0.price_source', 'auto')
                ->where('lines.0.value', 'D3117')
                ->has('formats.a4')->has('formats.thermal')->has('formats.roll80'));
    }

    public function test_from_a_grn_one_label_per_unit_received(): void
    {
        $supplier = Supplier::create(['supplier_number' => 'SUPP-1', 'name' => 'Sparepro', 'payment_terms_days' => 30]);
        $po = PurchaseOrder::create([
            'po_number' => 'PO-T1', 'supplier_id' => $supplier->id, 'branch_id' => $this->branch->id,
            'status' => 'submitted', 'order_date' => now()->toDateString(), 'subtotal' => 400, 'total' => 400,
        ]);
        $po->lines()->create(['part_id' => $this->longCode->id, 'description' => 'Tie rod end', 'qty_ordered' => 4, 'unit_cost' => 100, 'line_total' => 400]);
        $grn = app(GrnPostingService::class)->receive($po, [['po_line_id' => $po->lines->first()->id, 'qty_received' => 4]]);

        $this->actingAs($this->user)
            ->get(route('inventory.labels.index', ['grn' => $grn->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('source.type', 'grn')
                ->where('source.id', $grn->id)
                ->count('lines', 1)
                ->where('lines.0.part_number', 'TR51574/TR5140')
                ->where('lines.0.qty', 4));
    }

    public function test_lookup_search_and_everything_in_stock(): void
    {
        $this->actingAs($this->user)->getJson(route('inventory.labels.lookup', ['q' => 'D311']))
            ->assertOk()->assertJsonPath('parts.0.part_number', 'D3117')->assertJsonPath('parts.0.on_hand', 3);

        $this->actingAs($this->user)->getJson(route('inventory.labels.lookup', ['stocked' => 1]))
            ->assertOk()->assertJsonCount(1, 'lines')->assertJsonPath('lines.0.part_number', 'D3117');
    }

    // ── PDF ──────────────────────────────────────────────────────────────

    public function test_prints_a_pdf_in_every_format(): void
    {
        $items = "{$this->stocked->id}:3,{$this->longCode->id}:1,{$this->ean->id}:2";
        foreach (['a4', 'thermal', 'roll80'] as $format) {
            $res = $this->actingAs($this->user)->get(route('inventory.labels.print', ['items' => $items, 'format' => $format]));
            $res->assertOk();
            $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'), $format);
            $this->assertStringStartsWith('%PDF', $res->getContent(), $format);
        }
    }

    public function test_layout_honours_sheet_start_position_and_splits_big_runs(): void
    {
        $labels = app(LabelService::class);
        $a4 = $labels->formats($this->branch)['a4'];

        // Start at label 5 on a 3-across sheet → row 2, column 2.
        $layout = $labels->layout([$this->stocked->id => 2], $this->branch, 'a4', 5, true);
        $first = $layout['pages'][0][0];
        $this->assertEqualsWithDelta($a4['margin'] + 1 * $a4['cell_w'] + $a4['pad'], $first['left'], 0.01);
        $this->assertEqualsWithDelta($a4['margin'] + 1 * $a4['cell_h'] + $a4['pad'], $first['top'], 0.01);
        $this->assertSame('R161.00', $first['price']);

        app(SettingsService::class)->set('labels.max_per_print', 10);
        $labels = app(LabelService::class); // fresh instance reads the new setting
        $big = $labels->layout([$this->stocked->id => 25], $this->branch, 'thermal', 1, false, 3);
        $this->assertSame(25, $big['total']);
        $this->assertSame(3, $big['batches']);
        $this->assertCount(5, $big['pages'], 'third batch holds labels 21–25, one per page');
        $this->assertNull($big['pages'][0][0]['price'], 'price can be left off');
    }

    // ── Access ───────────────────────────────────────────────────────────

    public function test_inventory_clerk_and_buyer_can_label_a_cashier_cannot(): void
    {
        foreach (['Inventory Clerk', 'Buyer', 'Cashier'] as $name) {
            Role::firstOrCreate(['name' => $name], ['description' => $name]);
        }
        (new SystemRouteSeeder)->run();
        (new RolePermissionSeeder)->run();
        Cache::flush();

        foreach (['Inventory Clerk' => 200, 'Buyer' => 200, 'Cashier' => 403] as $role => $status) {
            $user = User::factory()->create(['password_changed_at' => now()]);
            $user->roles()->attach(Role::where('name', $role)->first()->id);
            $this->actingAs($user)->getJson(route('inventory.labels.lookup', ['q' => 'D311']))->assertStatus($status);
        }
    }
}
