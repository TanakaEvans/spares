<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Layby;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * UI-13 detail pages for the lighter sales documents: a lay-by and a delivery
 * note each get their own screen, and the customer profile still renders with
 * its "Connected to" card.
 */
class SalesDetailPagesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Customer $customer;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->branch = Branch::factory()->create();

        $this->customer = Customer::create([
            'customer_number' => 'CUST-0001', 'type' => 'business', 'name' => 'Highway Motors',
            'payment_terms_days' => 30, 'credit_limit' => 1000,
        ]);

        $role = Role::create(['name' => 'Superuser', 'description' => 'Superuser']);
        $this->user = User::factory()->create(['password_changed_at' => now()]);
        $this->user->roles()->attach($role->id, ['created_at' => now(), 'updated_at' => now()]);
    }

    public function test_layby_show_page_renders_with_payments(): void
    {
        $layby = Layby::create([
            'layby_number' => 'LB-0001', 'customer_id' => $this->customer->id, 'branch_id' => $this->branch->id,
            'total' => 500, 'deposit_paid' => 150, 'status' => 'active',
        ]);
        $layby->payments()->create(['amount' => 100, 'method' => 'cash', 'paid_on' => now()->toDateString()]);
        $layby->payments()->create(['amount' => 50, 'method' => 'card', 'paid_on' => now()->toDateString()]);

        $this->actingAs($this->user)
            ->get(route('sales.laybys.show', $layby))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Laybys/Show')
                ->where('layby.layby_number', 'LB-0001')
                ->where('layby.customer.id', $this->customer->id)
                ->where('layby.balance', fn ($v) => (float) $v === 350.0)
                ->has('layby.payments', 2)
            );
    }

    public function test_delivery_note_show_page_renders(): void
    {
        $note = DeliveryNote::create([
            'delivery_number' => 'DN-0001', 'customer_id' => $this->customer->id, 'branch_id' => $this->branch->id,
            'delivery_date' => now()->toDateString(), 'driver' => 'T. Mokone', 'vehicle_reg' => 'ABC 123 L', 'status' => 'dispatched',
        ]);

        $this->actingAs($this->user)
            ->get(route('sales.delivery-notes.show', $note))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/DeliveryNotes/Show')
                ->where('note.delivery_number', 'DN-0001')
                ->where('note.customer.id', $this->customer->id)
                ->where('note.driver', 'T. Mokone')
                ->where('note.document', null)
            );
    }

    public function test_customer_show_page_still_renders_with_related_links(): void
    {
        Layby::create([
            'layby_number' => 'LB-0002', 'customer_id' => $this->customer->id, 'branch_id' => $this->branch->id,
            'total' => 200, 'deposit_paid' => 50, 'status' => 'active',
        ]);
        DeliveryNote::create([
            'delivery_number' => 'DN-0002', 'customer_id' => $this->customer->id, 'branch_id' => $this->branch->id,
            'delivery_date' => now()->toDateString(), 'status' => 'dispatched',
        ]);

        $this->actingAs($this->user)
            ->get(route('customers.show', $this->customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->where('customer.id', $this->customer->id)
                ->has('related.laybys', 1)
                ->has('related.delivery_notes', 1)
                ->has('related.vehicles', 0)
                ->where('related.comms_count', 0)
            );
    }
}
