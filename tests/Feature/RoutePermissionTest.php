<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemRoute;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemRouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 8.1 — the role→route matrix is enforced on every request, with
 * sensible defaults seeded per role and the core plumbing routes exempt.
 */
class RoutePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        foreach (['Superuser', 'Cashier', 'Accountant', 'Manager'] as $name) {
            Role::create(['name' => $name, 'description' => $name]);
        }
        (new SystemRouteSeeder)->run();
        (new RolePermissionSeeder)->run();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->first()->id);

        return $user;
    }

    public function test_role_defaults_are_seeded_and_additive(): void
    {
        $cashier = Role::where('name', 'Cashier')->first();
        $this->assertTrue($cashier->hasRouteAccess('sales.pos'));
        $this->assertTrue($cashier->hasRouteAccess('finance.receipts.index'));
        $this->assertFalse($cashier->hasRouteAccess('finance.journals.index'));

        // An admin-added grant survives a re-run of the seeder.
        $extra = SystemRoute::where('name', 'finance.journals.index')->first();
        $cashier->systemRoutes()->attach($extra->id);
        (new RolePermissionSeeder)->run();
        $this->assertTrue($cashier->fresh()->hasRouteAccess('finance.journals.index'));
    }

    public function test_cashier_can_open_granted_screens_but_not_finance_journals(): void
    {
        $cashier = $this->userWithRole('Cashier');

        $this->actingAs($cashier)->get(route('customers.index'))->assertOk();
        $this->actingAs($cashier)->get(route('dashboard'))->assertOk();

        $response = $this->actingAs($cashier)->get(route('finance.journals.index'));
        $response->assertStatus(403);
        $response->assertSee('Unauthorized', false);
    }

    public function test_accountant_reaches_finance_and_superuser_reaches_everything(): void
    {
        $this->actingAs($this->userWithRole('Accountant'))->get(route('finance.journals.index'))->assertOk();
        $this->actingAs($this->userWithRole('Accountant'))->get(route('sales.pos'))->assertStatus(403);

        $super = $this->userWithRole('Superuser');
        $this->actingAs($super)->get(route('finance.journals.index'))->assertOk();
        $this->actingAs($super)->get(route('auth.roles.index'))->assertOk();
    }

    public function test_manager_granted_an_admin_route_passes_the_admin_guard(): void
    {
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get(route('admin.health'))->assertOk();
        $this->actingAs($manager)->get(route('auth.roles.index'))->assertStatus(403);
    }

    public function test_shared_props_list_accessible_routes(): void
    {
        $cashier = $this->userWithRole('Cashier');

        $this->actingAs($cashier)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.routes', fn ($routes) => in_array('sales.pos', $routes->toArray(), true)
                    && ! in_array('finance.journals.index', $routes->toArray(), true))
                ->where('currency.symbol', 'R'));
    }
}
