<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = app(SettingsService::class);
    }

    public function test_returns_declared_default_when_nothing_stored(): void
    {
        $this->assertSame(10.0, $this->settings->get('sales.max_discount_without_approval'));
        $this->assertFalse($this->settings->get('inventory.allow_negative_stock'));
    }

    public function test_global_value_overrides_default(): void
    {
        $this->settings->set('sales.max_discount_without_approval', 15);

        $this->assertSame(15.0, $this->settings->get('sales.max_discount_without_approval'));
    }

    public function test_branch_override_wins_over_global(): void
    {
        $branch = Branch::factory()->create();

        $this->settings->set('sales.max_discount_without_approval', 15);
        $this->settings->set('sales.max_discount_without_approval', 5, $branch);

        $this->assertSame(5.0, $this->settings->get('sales.max_discount_without_approval', $branch));
        $this->assertSame(15.0, $this->settings->get('sales.max_discount_without_approval'));
    }

    public function test_other_branches_still_read_global_value(): void
    {
        $overridden = Branch::factory()->create();
        $other = Branch::factory()->create();

        $this->settings->set('sales.max_discount_without_approval', 15);
        $this->settings->set('sales.max_discount_without_approval', 5, $overridden);

        $this->assertSame(15.0, $this->settings->get('sales.max_discount_without_approval', $other));
    }

    public function test_revert_to_global_removes_branch_override(): void
    {
        $branch = Branch::factory()->create();
        $this->settings->set('sales.max_discount_without_approval', 5, $branch);

        $this->settings->revertToGlobal('sales.max_discount_without_approval', $branch);

        $this->assertSame(10.0, $this->settings->get('sales.max_discount_without_approval', $branch));
        $this->assertDatabaseMissing('settings', [
            'key' => 'sales.max_discount_without_approval',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_unknown_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->settings->get('nonsense.key');
    }

    public function test_branch_override_rejected_for_non_per_branch_setting(): void
    {
        $branch = Branch::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->settings->set('tax.vat_rate_default', 20, $branch);
    }

    public function test_type_casting_per_declared_type(): void
    {
        $this->settings->set('inventory.allow_negative_stock', true);
        $this->settings->set('general.rows_per_page', 50);
        $this->settings->set('documents.invoice_footer_text', 'Custom footer');

        $this->assertTrue($this->settings->get('inventory.allow_negative_stock'));
        $this->assertSame(50, $this->settings->get('general.rows_per_page'));
        $this->assertSame('Custom footer', $this->settings->get('documents.invoice_footer_text'));
    }

    public function test_cache_busts_on_save(): void
    {
        $this->assertSame(10.0, $this->settings->get('sales.max_discount_without_approval'));

        $this->settings->set('sales.max_discount_without_approval', 12);

        $this->assertSame(12.0, $this->settings->get('sales.max_discount_without_approval'));
    }

    public function test_all_reports_override_state_for_branch(): void
    {
        $branch = Branch::factory()->create();
        $this->settings->set('sales.max_discount_without_approval', 5, $branch);

        $all = collect($this->settings->all($branch));
        $entry = $all->firstWhere('key', 'sales.max_discount_without_approval');

        $this->assertTrue($entry['is_overridden']);
        $this->assertSame(5.0, $entry['value']);
        $this->assertSame(10.0, $entry['global_value']);
    }

    public function test_set_records_updating_user(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->settings->set('sales.quote_expiry_days', 45, null, $user->id);

        $this->assertDatabaseHas('settings', [
            'key' => 'sales.quote_expiry_days',
            'updated_by' => $user->id,
        ]);
        $this->assertNull(Setting::where('key', 'sales.quote_expiry_days')->first()->branch_id);
    }
}
