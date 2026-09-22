<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Currency $usd;

    private Currency $zwg;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);

        $this->usd = Currency::factory()->base()->create(['code' => 'USD']);
        $this->zwg = Currency::factory()->create(['code' => 'ZWG']);
    }

    public function test_page_renders_with_currencies_and_history(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.currencies.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Currencies/Index')
                ->has('currencies', 2)
                ->has('rateHistory')
            );
    }

    public function test_capturing_a_rate(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.currencies.rates.store'), [
                'currency_id' => $this->zwg->id,
                'buy_rate' => 26.0,
                'sell_rate' => 26.5,
                'rate_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('exchange_rates', [
            'currency_id' => $this->zwg->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_sell_rate_must_be_at_least_buy_rate(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.currencies.rates.store'), [
                'currency_id' => $this->zwg->id,
                'buy_rate' => 27.0,
                'sell_rate' => 26.0,
                'rate_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('sell_rate');
    }

    public function test_base_currency_rejects_rates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.currencies.rates.store'), [
                'currency_id' => $this->usd->id,
                'buy_rate' => 1,
                'sell_rate' => 1,
                'rate_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('currency_id');
    }

    public function test_future_dated_rates_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.currencies.rates.store'), [
                'currency_id' => $this->zwg->id,
                'buy_rate' => 26,
                'sell_rate' => 26.5,
                'rate_date' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors('rate_date');
    }

    public function test_toggle_deactivates_non_base_currency(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.currencies.toggle', $this->zwg))
            ->assertRedirect();

        $this->assertFalse($this->zwg->fresh()->is_active);
    }

    public function test_base_currency_cannot_be_deactivated(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.currencies.toggle', $this->usd))
            ->assertSessionHasErrors();

        $this->assertTrue($this->usd->fresh()->is_active);
    }
}
