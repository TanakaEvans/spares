<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Superuser', 'description' => 'All access']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->roles()->attach($role->id);
    }

    public function test_finds_pages_by_label_and_keywords(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/search?q=exchange');

        $response->assertOk();
        $labels = collect($response->json('groups'))
            ->firstWhere('group', 'Pages')['items'] ?? [];

        $this->assertContains('Currencies & Rates', array_column($labels, 'label'));
    }

    public function test_superuser_finds_users(): void
    {
        User::factory()->create(['name' => 'Tendai Moyo', 'username' => 'tmoyo']);

        $response = $this->actingAs($this->admin)->getJson('/search?q=tendai');

        $users = collect($response->json('groups'))->firstWhere('group', 'Users')['items'] ?? [];
        $this->assertContains('Tendai Moyo', array_column($users, 'label'));
    }

    public function test_non_superuser_gets_no_user_results(): void
    {
        $role = Role::create(['name' => 'Cashier', 'description' => 'Till']);
        $cashier = User::factory()->create(['password_changed_at' => now()]);
        $cashier->roles()->attach($role->id);
        User::factory()->create(['name' => 'Tendai Moyo']);

        $response = $this->actingAs($cashier)->getJson('/search?q=tendai');

        $this->assertNull(collect($response->json('groups'))->firstWhere('group', 'Users'));
    }

    public function test_pages_scope_operator(): void
    {
        User::factory()->create(['name' => 'Currencies McUser']);

        $response = $this->actingAs($this->admin)->getJson('/search?q='.urlencode('> currencies'));

        $groups = collect($response->json('groups'));
        $this->assertNotNull($groups->firstWhere('group', 'Pages'));
        $this->assertNull($groups->firstWhere('group', 'Users'));
    }

    public function test_guests_cannot_search(): void
    {
        $this->getJson('/search?q=x')->assertUnauthorized();
    }
}
