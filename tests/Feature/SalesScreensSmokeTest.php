<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Role;
use App\Models\User;
use App\Services\SalesPostingService;
use App\Services\SalesQuoteService;
use App\Services\StockLedgerService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Every Sales-hub screen renders with the props its page expects — guards the
 * stat chips, pickers and detail payloads added in the sales UX pass.
 */
class SalesScreensSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Customer $customer;

    private User $user;

    private Part $part;

    private $invoice;

    private $order;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();
        $this->part = Part::factory()->create(['part_number' => 'BI106', 'description' => 'Brake Pads']);
        app(StockLedgerService::class)->post($this->part->id, $this->branch, 'OPENING_BALANCE', 10, 22.50, 'Test', 1);

        $retail = PriceList::create(['name' => 'Standard Retail', 'type' => 'retail', 'is_default' => true]);
        PriceListItem::create(['price_list_id' => $retail->id, 'part_id' => $this->part->id, 'price' => 45.00]);

        $this->customer = Customer::create([
            'customer_number' => 'TRADE-001', 'type' => 'business', 'name' => 'Highway Motors',
            'price_list_id' => $retail->id, 'payment_terms_days' => 30, 'credit_limit' => 1000,
        ]);
        Customer::create(['customer_number' => 'WI-1', 'type' => 'walk_in', 'name' => 'Cash Customer', 'is_walk_in' => true]);

        $role = Role::create(['name' => 'Superuser', 'description' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);

        $this->order = app(SalesQuoteService::class)->createOrder(
            $this->customer, $this->branch->id, [['part_id' => $this->part->id, 'qty' => 2]],
        );

        $this->invoice = app(SalesPostingService::class)->postInvoice(
            $this->customer, $this->branch->id,
            [['part_id' => $this->part->id, 'qty' => 1, 'unit_price' => 45.00]],
            [['method' => 'cash', 'amount' => 51.75, 'tendered' => 60]],
        );
    }

    public function test_quotes_index_and_create_and_show(): void
    {
        $quote = app(SalesQuoteService::class)->createQuote(
            $this->customer, $this->branch->id, [['part_id' => $this->part->id, 'qty' => 1]],
        );

        $this->actingAs($this->user)->get(route('sales.quotes.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Quotes/Index')->where('stats.open', 1));

        $this->actingAs($this->user)->get(route('sales.quotes.create'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Quotes/Create')->has('vatRate')->has('expiryDays'));

        $this->actingAs($this->user)->get(route('sales.quotes.show', $quote))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Quotes/Show')->has('quote.lines.0.id'));
    }

    public function test_orders_index_create_and_show(): void
    {
        $this->actingAs($this->user)->get(route('sales.orders.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Orders/Index')->where('stats.awaiting', 1));

        $this->actingAs($this->user)->get(route('sales.orders.create'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Orders/Create')->has('vatRate')->has('walkIn'));

        $this->actingAs($this->user)->get(route('sales.orders.show', $this->order))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Orders/Show')
                ->has('order.customer.available_credit')
                ->has('order.lines.0.bin')
                ->has('order.lines.0.on_hand'));
    }

    public function test_order_store_creates_confirmed_order(): void
    {
        $this->actingAs($this->user)->post(route('sales.orders.store'), [
            'customer_id' => $this->customer->id,
            'lines' => [['part_id' => $this->part->id, 'qty' => 1]],
        ])->assertRedirect();

        $this->assertSame(2, \App\Models\SalesDocument::where('document_type', 'order')->count());
    }

    public function test_invoices_index_and_show(): void
    {
        $this->actingAs($this->user)->get(route('sales.invoices.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Invoices/Index')
                ->where('stats.today_count', 1)
                ->where('invoices.data.0.on_account', false));

        $this->actingAs($this->user)->get(route('sales.invoices.show', $this->invoice))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Invoices/Show'));
    }

    public function test_credit_notes_index_and_create(): void
    {
        $this->actingAs($this->user)->get(route('sales.credit-notes.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/CreditNotes/Index')->has('stats.month_count'));

        $this->actingAs($this->user)->get(route('sales.credit-notes.create', ['invoice' => $this->invoice->id]))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/CreditNotes/Create')
                ->has('invoice.lines.0.vat_rate')
                ->has('invoice.payments.0')
                ->where('invoice.same_day', true));
    }

    public function test_price_lists_laybys_promotions_delivery_notes(): void
    {
        $list = PriceList::first();

        $this->actingAs($this->user)->get(route('sales.price-lists.index'))->assertOk();
        $this->actingAs($this->user)->get(route('sales.price-lists.show', $list))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/PriceLists/Show')->has('vatRate'));

        $this->actingAs($this->user)->get(route('sales.promotions.index'))->assertOk();

        $this->actingAs($this->user)->get(route('sales.laybys.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Sales/Laybys/Index')->has('stats.outstanding'));

        $this->actingAs($this->user)->get(route('sales.delivery-notes.index'))->assertOk();
    }
}
