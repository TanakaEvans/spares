<?php

namespace Tests\Feature;

use App\Models\BinLocation;
use App\Models\Branch;
use App\Models\Part;
use App\Models\Role;
use App\Models\StockLevel;
use App\Models\User;
use App\Services\StockLedgerService;
use App\Services\StockMovementService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The read-only Inventory detail hubs (bin, adjustment, transfer, part) render
 * with every entity reference carrying an id so the page can hyperlink it (UI-13).
 */
class InventoryDetailPagesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Part $part;

    private BinLocation $bin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'Z762']);
        $this->bin = BinLocation::create(['branch_id' => $this->branch->id, 'code' => 'A-01-01']);

        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 5.00, 'Test', 1);
        // Bin assignment is stock-level metadata, not a movement.
        StockLevel::where('part_id', $this->part->id)->where('branch_id', $this->branch->id)
            ->update(['bin_location_id' => $this->bin->id]);

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);
    }

    public function test_bin_detail_lists_the_stock_stored_in_it(): void
    {
        $this->actingAs($this->user)
            ->get(route('inventory.bins.show', $this->bin))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Bins/Show')
                ->where('bin.code', 'A-01-01')
                ->where('bin.branch_id', $this->branch->id)
                ->has('levels', 1, fn (Assert $level) => $level
                    ->where('part_id', $this->part->id)
                    ->where('part_number', 'Z762')
                    ->where('qty_on_hand', 10)
                    ->where('stock_value', 50)
                    ->etc())
                ->where('totals.stock_value', 50));
    }

    public function test_part_detail_carries_link_ids_and_recent_ledger_movements(): void
    {
        $this->actingAs($this->user)
            ->get(route('inventory.parts.show', $this->part))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Parts/Show')
                ->where('part.id', $this->part->id)
                ->has('part.stock', 1, fn (Assert $level) => $level
                    ->where('branch_id', $this->branch->id)
                    ->where('bin_id', $this->bin->id)
                    ->where('bin', 'A-01-01')
                    ->etc())
                ->has('part.suppliers')
                ->has('part.price_lists')
                ->has('part.movements', 1, fn (Assert $m) => $m
                    ->where('type', 'OPENING_BALANCE')
                    ->where('qty', 10)
                    ->where('branch_id', $this->branch->id)
                    ->where('reference', null)          // 'Test' has no page → plain text fallback
                    ->where('reference_text', 'Test #1')
                    ->etc()));
    }

    public function test_adjustment_detail_links_parts_branch_and_journal(): void
    {
        $adjustment = app(StockMovementService::class)->postAdjustment($this->branch->id, 'damage', [
            ['part_id' => $this->part->id, 'direction' => 'out', 'qty' => 2],
        ], 'Dropped on the floor', $this->user->id);

        $this->actingAs($this->user)
            ->get(route('inventory.adjustments.show', $adjustment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Adjustments/Show')
                ->where('adjustment.adjustment_number', $adjustment->adjustment_number)
                ->where('adjustment.branch_id', $this->branch->id)
                ->where('adjustment.created_by_id', $this->user->id)
                ->where('adjustment.journal_id', $adjustment->gl_journal_id)
                ->where('adjustment.net_value', -10)
                ->has('adjustment.lines', 1, fn (Assert $line) => $line
                    ->where('part_id', $this->part->id)
                    ->where('direction', 'out')
                    ->where('qty', 2)
                    ->where('unit_cost', 5)
                    ->etc()));

        // The part hub now resolves that movement back to the adjustment page.
        $this->actingAs($this->user)
            ->get(route('inventory.parts.show', $this->part))
            ->assertInertia(fn (Assert $page) => $page
                ->where('part.movements.0.reference.type', 'adjustment')
                ->where('part.movements.0.reference.id', $adjustment->id)
                ->where('part.movements.0.reference.label', $adjustment->adjustment_number));
    }

    public function test_transfer_detail_shows_both_branches_and_who_moved_it(): void
    {
        $other = Branch::factory()->create();
        $moves = app(StockMovementService::class);
        $transfer = $moves->createTransfer($this->branch->id, $other->id, [
            ['part_id' => $this->part->id, 'qty' => 4],
        ], null, $this->user->id);

        $this->actingAs($this->user)
            ->get(route('inventory.transfers.show', $transfer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Transfers/Show')
                ->where('transfer.status', 'draft')
                ->where('transfer.from_branch_id', $this->branch->id)
                ->where('transfer.to_branch_id', $other->id)
                ->where('transfer.dispatched_by_id', null)
                ->has('transfer.lines', 1));

        $moves->dispatchTransfer($transfer, $this->user->id);
        $moves->receiveTransfer($transfer->fresh(), $this->user->id);

        $this->actingAs($this->user)
            ->get(route('inventory.transfers.show', $transfer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('transfer.status', 'received')
                ->where('transfer.dispatched_by_id', $this->user->id)
                ->where('transfer.received_by_id', $this->user->id)
                ->where('transfer.total_value', 20)
                ->where('transfer.lines.0.part_id', $this->part->id));
    }
}
