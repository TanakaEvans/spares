<?php

namespace App\Console\Commands;

use App\Services\Health\OperationsChecks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Go-live gate (operations/deployment.md): is this server configured safely?
 * Exit code 1 on any failure so it can gate a deploy script.
 */
class Preflight extends Command
{
    protected $signature = 'app:preflight';

    protected $description = 'Production readiness checklist — environment, security, backups, scheduler, identity';

    public function handle(OperationsChecks $checks): int
    {
        $this->line('<comment>SparesPro pre-flight — '.app()->environment().' — '.now()->toDateTimeString().'</comment>');
        $this->newLine();

        $results = array_merge($checks->deployment(), $checks->operations());

        // Pending migrations are a deployment matter too.
        Artisan::call('migrate:status', ['--pending' => true]);
        $pending = trim(Artisan::output());
        $results[] = new \App\Services\Health\HealthResult('migrations', 'Deployment', 'Migrations up to date',
            str_contains($pending, 'Pending') ? 'fail' : 'ok',
            str_contains($pending, 'Pending') ? 'Pending migrations — run `php artisan migrate --force`.' : 'Nothing pending.');

        $fail = 0;
        $warn = 0;
        foreach ($results as $r) {
            $mark = ['ok' => '<info>PASS</info>', 'warn' => '<comment>WARN</comment>', 'fail' => '<error>FAIL</error>'][$r->status];
            $this->line(sprintf('%s  %-42s %s', $mark, $r->label, $r->message));
            $fail += $r->status === 'fail' ? 1 : 0;
            $warn += $r->status === 'warn' ? 1 : 0;
        }

        $this->newLine();
        $this->line($fail > 0
            ? "<error>NOT READY — {$fail} blocker(s), {$warn} warning(s).</error>"
            : ($warn > 0 ? "<comment>READY WITH WARNINGS — {$warn} warning(s).</comment>" : '<info>READY — all checks passed.</info>'));

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
