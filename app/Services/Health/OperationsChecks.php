<?php

namespace App\Services\Health;

use App\Models\Company;
use App\Models\Currency;
use App\Models\SystemRoute;
use App\Models\User;
use App\Services\BackupService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Operations + deployment-readiness checks (operations/system-health.md
 * "Operations" group; app:preflight). Everything here is about the server
 * and configuration, not the ledger.
 */
class OperationsChecks
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly BackupService $backups,
    ) {
    }

    /** @return HealthResult[] */
    public function operations(): array
    {
        return array_map(fn ($c) => $this->safe($c), [
            fn () => $this->schedulerHeartbeat(),
            fn () => $this->backupAge(),
            fn () => $this->failedJobs(),
            fn () => $this->diskSpace(),
            fn () => $this->logErrors(),
        ]);
    }

    /** @return HealthResult[] */
    public function deployment(): array
    {
        return array_map(fn ($c) => $this->safe($c), [
            fn () => $this->debugMode(),
            fn () => $this->appKeyAndUrl(),
            fn () => $this->queueAndMail(),
            fn () => $this->defaultAdminPassword(),
            fn () => $this->companyIdentity(),
            fn () => $this->baseCurrency(),
            fn () => $this->routesRegistered(),
            fn () => $this->mysqldumpAvailable(),
            fn () => $this->storageWritable(),
        ]);
    }

    private function safe(callable $check): HealthResult
    {
        try {
            return $check();
        } catch (\Throwable $e) {
            return new HealthResult('ops_error', 'Operations', 'Check failed to run', 'fail', $e->getMessage());
        }
    }

    // ── Operations ───────────────────────────────────────────────────────

    private function schedulerHeartbeat(): HealthResult
    {
        $beat = Cache::get('scheduler.heartbeat');
        $stale = (int) $this->settings->get('ops.scheduler_stale_minutes');
        if (! $beat) {
            return new HealthResult('scheduler', 'Operations', 'Scheduler is running', 'warn',
                'No heartbeat yet — the OS job that runs `php artisan schedule:run` every minute is not set up (see operations/deployment.md).');
        }
        $age = now()->diffInMinutes(\Carbon\Carbon::parse($beat));
        if ($age > $stale) {
            return new HealthResult('scheduler', 'Operations', 'Scheduler is running', 'fail', "Last heartbeat {$age} min ago (stale after {$stale}). Backups and nightly checks are not running.");
        }

        return new HealthResult('scheduler', 'Operations', 'Scheduler is running', 'ok', "Heartbeat {$age} min ago.");
    }

    private function backupAge(): HealthResult
    {
        $latest = $this->backups->latestAt();
        $max = (int) $this->settings->get('backup.max_age_hours');
        if (! $latest) {
            return new HealthResult('backup_age', 'Operations', 'Recent database backup exists', app()->isProduction() ? 'fail' : 'warn',
                'No backup has been taken yet.', link: 'admin.backups.index');
        }
        $hours = round($latest->diffInHours(now()), 1);
        if ($hours > $max) {
            return new HealthResult('backup_age', 'Operations', 'Recent database backup exists', $hours > $max * 3 ? 'fail' : 'warn',
                "Newest backup is {$hours} h old (limit {$max} h).", link: 'admin.backups.index');
        }

        return new HealthResult('backup_age', 'Operations', 'Recent database backup exists', 'ok', "Newest backup {$hours} h ago.");
    }

    private function failedJobs(): HealthResult
    {
        if (! Schema::hasTable('failed_jobs')) {
            return new HealthResult('queue', 'Operations', 'Queue healthy', 'ok', 'No failed-jobs table.');
        }
        $failed = DB::table('failed_jobs')->count();
        $pendingOld = Schema::hasTable('jobs')
            ? DB::table('jobs')->where('created_at', '<', now()->subMinutes(15)->getTimestamp())->count()
            : 0;
        if ($failed > 0) {
            return new HealthResult('queue', 'Operations', 'Queue healthy', 'warn', "{$failed} failed job(s) — emails/notifications did not go out. Retry with `php artisan queue:retry all`.");
        }
        if ($pendingOld > 0) {
            return new HealthResult('queue', 'Operations', 'Queue healthy', 'warn', "{$pendingOld} job(s) waiting > 15 min — is the queue worker running?");
        }

        return new HealthResult('queue', 'Operations', 'Queue healthy', 'ok', '0 failed, nothing stuck.');
    }

    private function diskSpace(): HealthResult
    {
        $path = base_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if (! $free || ! $total) {
            return new HealthResult('disk', 'Operations', 'Disk space', 'warn', 'Could not read disk usage.');
        }
        $pct = round($free / $total * 100, 1);
        $warn = (int) $this->settings->get('ops.disk_warn_free_pct');
        $gb = round($free / 1024 ** 3, 1);
        $status = $pct < max(3, $warn / 3) ? 'fail' : ($pct < $warn ? 'warn' : 'ok');

        return new HealthResult('disk', 'Operations', 'Disk space', $status, "{$gb} GB free ({$pct}%).");
    }

    private function logErrors(): HealthResult
    {
        $file = storage_path('logs/laravel.log');
        if (! is_file($file)) {
            return new HealthResult('log_errors', 'Operations', 'No application errors (24 h)', 'ok', 'No log file.');
        }
        $since = [now()->toDateString(), now()->subDay()->toDateString()];
        $count = 0;
        $handle = fopen($file, 'r');
        // Only scan the tail of large logs.
        $size = filesize($file);
        if ($size > 5 * 1024 * 1024) {
            fseek($handle, -5 * 1024 * 1024, SEEK_END);
        }
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})[^\]]*\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $line, $m) && in_array($m[1], $since, true)) {
                $count++;
            }
        }
        fclose($handle);

        return $count === 0
            ? new HealthResult('log_errors', 'Operations', 'No application errors (24 h)', 'ok', 'Clean log.')
            : new HealthResult('log_errors', 'Operations', 'No application errors (24 h)', 'warn', "{$count} error(s) logged in the last 24 h — see storage/logs/laravel.log.");
    }

    // ── Deployment readiness ─────────────────────────────────────────────

    private function debugMode(): HealthResult
    {
        $debug = (bool) config('app.debug');
        $debugbar = (bool) config('debugbar.enabled', $debug);
        if (app()->isProduction() && ($debug || $debugbar)) {
            return new HealthResult('debug_mode', 'Deployment', 'Debug mode off in production', 'fail', 'APP_DEBUG / DEBUGBAR_ENABLED must be false in production — stack traces and SQL would be shown to users.');
        }
        if (! app()->isProduction()) {
            return new HealthResult('debug_mode', 'Deployment', 'Debug mode off in production', 'warn', 'APP_ENV='.app()->environment().' — set APP_ENV=production, APP_DEBUG=false, DEBUGBAR_ENABLED=false on the live server.');
        }

        return new HealthResult('debug_mode', 'Deployment', 'Debug mode off in production', 'ok', 'Debug and Debugbar off.');
    }

    private function appKeyAndUrl(): HealthResult
    {
        if (! config('app.key')) {
            return new HealthResult('app_key', 'Deployment', 'App key and URL', 'fail', 'APP_KEY is empty — run `php artisan key:generate`.');
        }
        $url = (string) config('app.url');
        if (app()->isProduction() && (str_contains($url, 'localhost') || ! str_starts_with($url, 'https://'))) {
            return new HealthResult('app_key', 'Deployment', 'App key and URL', 'warn', "APP_URL is {$url} — set the real HTTPS address so links and QR verification codes work.");
        }

        return new HealthResult('app_key', 'Deployment', 'App key and URL', 'ok', "APP_URL {$url}.");
    }

    private function queueAndMail(): HealthResult
    {
        $queue = config('queue.default');
        $mail = config('mail.default');
        $issues = [];
        if ($queue === 'sync' && app()->isProduction()) {
            $issues[] = 'QUEUE_CONNECTION=sync makes every email block the request';
        }
        if ($mail === 'log' && app()->isProduction()) {
            $issues[] = 'MAIL_MAILER=log means no email is actually sent';
        }

        return $issues === []
            ? new HealthResult('queue_mail', 'Deployment', 'Queue and mail configured', 'ok', "queue={$queue}, mail={$mail}.")
            : new HealthResult('queue_mail', 'Deployment', 'Queue and mail configured', 'warn', implode('; ', $issues).'.');
    }

    private function defaultAdminPassword(): HealthResult
    {
        $admin = User::where('username', 'admin')->first();
        if ($admin && Hash::check('password123', $admin->password)) {
            return new HealthResult('admin_password', 'Deployment', 'Default admin password changed', app()->isProduction() ? 'fail' : 'warn', 'The seeded admin login still uses the default password.', link: 'auth.users.index');
        }

        return new HealthResult('admin_password', 'Deployment', 'Default admin password changed', 'ok', 'Admin password is not the seeded default.');
    }

    private function companyIdentity(): HealthResult
    {
        $company = Company::query()->first();
        $missing = [];
        foreach (['vat_number' => 'VAT number', 'registration_number' => 'registration number', 'bank_name' => 'banking details'] as $col => $label) {
            if (! $company || blank($company->{$col} ?? null)) {
                $missing[] = $label;
            }
        }

        return $missing === []
            ? new HealthResult('company_identity', 'Deployment', 'Company identity complete', 'ok', 'VAT, registration and banking captured.')
            : new HealthResult('company_identity', 'Deployment', 'Company identity complete', 'warn', 'Missing '.implode(', ', $missing).' — these print on every invoice.', link: 'admin.company.index');
    }

    private function baseCurrency(): HealthResult
    {
        $base = Currency::where('is_base', true)->first();

        return $base
            ? new HealthResult('base_currency', 'Deployment', 'Base currency set', 'ok', "{$base->code} ({$base->symbol}).")
            : new HealthResult('base_currency', 'Deployment', 'Base currency set', 'fail', 'No base currency — run the CurrencySeeder.', link: 'admin.currencies.index');
    }

    private function routesRegistered(): HealthResult
    {
        $registered = SystemRoute::pluck('name')->flip();
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || \App\Http\Middleware\EnforceRoutePermission::isExempt($name) || in_array($name, ['login.submit', 'welcome', 'settings.index'], true)) {
                continue;
            }
            if (! isset($registered[$name]) && ! str_starts_with($name, 'sanctum.')) {
                $missing[] = $name;
            }
        }

        return $missing === []
            ? new HealthResult('routes_registered', 'Deployment', 'Every route registered as a permission', 'ok', count($registered).' routes registered.')
            : new HealthResult('routes_registered', 'Deployment', 'Every route registered as a permission', 'fail', count($missing).' route(s) not in the permission matrix (only Superuser can open them): '.implode(', ', array_slice($missing, 0, 5)).(count($missing) > 5 ? '…' : '').' — run `php artisan db:seed --class=SystemRouteSeeder`.');
    }

    private function mysqldumpAvailable(): HealthResult
    {
        if (config('database.connections.'.config('database.default').'.driver') !== 'mysql') {
            return new HealthResult('mysqldump', 'Deployment', 'mysqldump available', 'ok', 'Not a MySQL connection — PHP dump is used.');
        }
        $bin = $this->backups->mysqldumpBinary();

        return $bin
            ? new HealthResult('mysqldump', 'Deployment', 'mysqldump available', 'ok', $bin)
            : new HealthResult('mysqldump', 'Deployment', 'mysqldump available', 'warn', 'mysqldump not found — backups fall back to the slower PHP dump. Set MYSQLDUMP_PATH in .env.');
    }

    private function storageWritable(): HealthResult
    {
        $bad = array_filter([storage_path('app'), storage_path('logs'), storage_path('framework/cache'), base_path('bootstrap/cache')], fn ($p) => ! is_writable($p));

        return $bad === []
            ? new HealthResult('storage', 'Deployment', 'Storage writable', 'ok', 'storage/ and bootstrap/cache writable.')
            : new HealthResult('storage', 'Deployment', 'Storage writable', 'fail', 'Not writable: '.implode(', ', $bad));
    }
}
