<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The shared list-toolbar contract: search, sort whitelist, date range and
 * filter-preserving CSV/PDF export behave identically on every list screen.
 * Exercised here against purchase orders; the same ListQuery trait drives the rest.
 */
class ListToolbarTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PurchaseOrder $poA;

    private PurchaseOrder $poB;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $branch = Branch::factory()->create();
        $gud = Supplier::create(['supplier_number' => 'SUPP-00001', 'name' => 'GUD Distributors']);
        $bosal = Supplier::create(['supplier_number' => 'SUPP-00002', 'name' => 'Bosal Africa']);

        $this->poA = PurchaseOrder::create([
            'po_number' => 'PO-A-001', 'supplier_id' => $gud->id, 'branch_id' => $branch->id,
            'status' => 'confirmed', 'order_date' => '2026-01-05', 'subtotal' => 500, 'total' => 500,
        ]);
        $this->poB = PurchaseOrder::create([
            'po_number' => 'PO-B-002', 'supplier_id' => $bosal->id, 'branch_id' => $branch->id,
            'status' => 'draft', 'order_date' => '2026-02-10', 'subtotal' => 1200, 'total' => 1200,
        ]);

        $role = Role::create(['name' => 'Superuser', 'description' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);
    }

    public function test_search_matches_across_whitelisted_columns_and_relation(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.orders.index', ['search' => 'bosal']))
            ->assertInertia(fn (Assert $p) => $p
                ->has('orders.data', 1)
                ->where('orders.data.0.po_number', 'PO-B-002'));

        $this->actingAs($this->user)->get(route('purchasing.orders.index', ['search' => 'PO-A']))
            ->assertInertia(fn (Assert $p) => $p->has('orders.data', 1));
    }

    public function test_sort_whitelist_orders_results_and_unknown_keys_fall_back(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.orders.index', ['sort' => 'total', 'dir' => 'asc']))
            ->assertInertia(fn (Assert $p) => $p
                ->where('orders.data.0.total', 500)
                ->where('orders.data.1.total', 1200)
                ->where('filters.sort', 'total')
                ->where('filters.dir', 'asc'));

        // An un-whitelisted sort key must not reach the query — default order stands.
        $this->actingAs($this->user)->get(route('purchasing.orders.index', ['sort' => 'id;drop', 'dir' => 'asc']))
            ->assertInertia(fn (Assert $p) => $p->has('orders.data', 2));
    }

    public function test_date_range_scopes_the_list_and_status_pills_compose(): void
    {
        $this->actingAs($this->user)->get(route('purchasing.orders.index', [
            'date_from' => '2026-02-01', 'date_to' => '2026-02-28',
        ]))->assertInertia(fn (Assert $p) => $p
            ->has('orders.data', 1)
            ->where('orders.data.0.po_number', 'PO-B-002'));

        $this->actingAs($this->user)->get(route('purchasing.orders.index', [
            'status' => 'draft', 'search' => 'GUD',
        ]))->assertInertia(fn (Assert $p) => $p->has('orders.data', 0));
    }

    public function test_csv_export_respects_active_filters_and_sort(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchasing.orders.export', ['search' => 'bosal']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $body = $response->streamedContent();
        $this->assertStringContainsString('PO-B-002', $body);
        $this->assertStringContainsString('Bosal Africa', $body);
        $this->assertStringNotContainsString('PO-A-001', $body);
    }

    public function test_pdf_export_returns_a_pdf_download(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchasing.orders.export', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_every_toolbar_screen_exposes_the_shared_filters_prop(): void
    {
        foreach ([
            'purchasing.orders.index', 'purchasing.grns.index', 'purchasing.invoices.index',
            'purchasing.returns.index', 'purchasing.supplier-credits.index',
            'sales.quotes.index', 'sales.orders.index', 'sales.invoices.index',
            'sales.credit-notes.index', 'sales.laybys.index', 'sales.delivery-notes.index',
            'customers.index', 'suppliers.index',
        ] as $route) {
            $this->actingAs($this->user)->get(route($route, ['sort' => 'x', 'date_from' => '2026-01-01']))
                ->assertInertia(fn (Assert $p) => $p->has('filters.sort')->has('filters.date_from'));
        }
    }
}
