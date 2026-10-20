<?php

namespace Tests\Feature;

use App\Models\HealthCheckRun;
use App\Services\BackupService;
use App\Services\Health\HealthRunService;
use App\Services\Health\OperationsChecks;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Phase 8.5 — the system's heartbeat: a backup file is actually produced, a
 * health run persists, and the operations checks flag a stale scheduler.
 */
class OperationsHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_run_writes_a_dump_file_and_applies_retention(): void
    {
        $backups = app(BackupService::class);
        // Clean slate.
        foreach (File::glob($backups->directory().'/backup-*') as $f) {
            File::delete($f);
        }

        $file = $backups->run();

        $this->assertFileExists($file);
        $this->assertGreaterThan(0, filesize($file));
        $this->assertNotEmpty($backups->list());
        $this->assertNotNull($backups->latestAt());
    }

    public function test_health_run_is_persisted_with_a_summary(): void
    {
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $run = app(HealthRunService::class)->runAndPersist('scheduled');

        $this->assertDatabaseCount('health_check_runs', 1);
        $this->assertSame('scheduled', $run->trigger);
        $this->assertContains($run->overall, ['ok', 'warn', 'fail']);
        $this->assertGreaterThan(0, $run->ok_count + $run->warn_count + $run->fail_count);
        $this->assertIsArray($run->results);
    }

    public function test_scheduler_check_warns_without_a_heartbeat_and_passes_with_a_fresh_one(): void
    {
        $ops = app(OperationsChecks::class);

        Cache::forget('scheduler.heartbeat');
        $scheduler = collect($ops->operations())->firstWhere('key', 'scheduler');
        $this->assertSame('warn', $scheduler->status);

        Cache::put('scheduler.heartbeat', now()->toIso8601String());
        $scheduler = collect($ops->operations())->firstWhere('key', 'scheduler');
        $this->assertSame('ok', $scheduler->status);
    }

    public function test_deployment_checks_flag_the_base_currency_and_routes(): void
    {
        (new CurrencySeeder)->run();

        $results = app(OperationsChecks::class)->deployment();
        $byKey = collect($results)->keyBy('key');

        $this->assertSame('ok', $byKey['base_currency']->status);
        // No routes seeded in this test → the matrix check should fail (loudly).
        $this->assertSame('fail', $byKey['routes_registered']->status);
    }

    public function test_run_route_records_a_run(): void
    {
        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $admin = \App\Models\User::factory()->create(['password_changed_at' => now()]);
        $role = \App\Models\Role::create(['name' => 'Superuser', 'description' => 'All']);
        $admin->roles()->attach($role->id);

        $this->actingAs($admin)->post(route('admin.health.run'))->assertRedirect();
        $this->assertSame(1, HealthCheckRun::count());
    }
}
