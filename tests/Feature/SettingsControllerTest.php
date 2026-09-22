<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
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

    public function test_settings_page_renders_with_registry(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Index')
                ->has('settings')
                ->has('branches')
            );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_saving_a_global_setting(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'values' => ['sales.quote_expiry_days' => 45],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(45, app(SettingsService::class)->get('sales.quote_expiry_days'));
    }

    public function test_saving_a_branch_override(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'branch_id' => $branch->id,
                'values' => ['sales.max_discount_without_approval' => 5],
            ])
            ->assertRedirect();

        $settings = app(SettingsService::class);
        $this->assertSame(5.0, $settings->get('sales.max_discount_without_approval', $branch));
        $this->assertSame(10.0, $settings->get('sales.max_discount_without_approval'));
    }

    public function test_unknown_key_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'values' => ['bogus.key' => 1],
            ])
            ->assertSessionHasErrors('values.bogus.key');
    }

    public function test_declared_rules_are_enforced(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'values' => ['sales.max_discount_without_approval' => 500],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(10.0, app(SettingsService::class)->get('sales.max_discount_without_approval'));
    }

    public function test_branch_override_of_global_only_setting_is_rejected(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'branch_id' => $branch->id,
                'values' => ['tax.vat_rate_default' => 20],
            ])
            ->assertSessionHasErrors();
    }

    public function test_revert_removes_branch_override(): void
    {
        $branch = Branch::factory()->create();
        $settings = app(SettingsService::class);
        $settings->set('sales.max_discount_without_approval', 5, $branch);

        $this->actingAs($this->admin)
            ->delete(route('admin.settings.revert'), [
                'branch_id' => $branch->id,
                'key' => 'sales.max_discount_without_approval',
            ])
            ->assertRedirect();

        $this->assertSame(10.0, $settings->get('sales.max_discount_without_approval', $branch));
    }
}
