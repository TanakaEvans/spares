<?php

namespace App\Services\Health;

use App\Models\HealthCheckRun;
use App\Services\NotificationRouterService;
use Illuminate\Support\Facades\Log;

/**
 * Runs the full check set, persists the outcome (so the screen has history
 * and the nightly run is provable) and raises the mandatory admin
 * notification when anything is red — system-health.md §Behaviour 1–3.
 */
class HealthRunService
{
    public function __construct(
        private readonly HealthCheckRunner $runner,
        private readonly NotificationRouterService $notifications,
    ) {
    }

    public function runAndPersist(string $trigger = 'manual'): HealthCheckRun
    {
        $report = $this->runner->run();

        $run = HealthCheckRun::create([
            'trigger' => $trigger,
            'overall' => $report['summary']['overall'],
            'ok_count' => $report['summary']['ok'],
            'warn_count' => $report['summary']['warn'],
            'fail_count' => $report['summary']['fail'],
            'results' => $report['groups'],
            'ran_at' => now(),
        ]);

        Log::info('health.run', ['trigger' => $trigger, 'overall' => $run->overall, 'fail' => $run->fail_count, 'warn' => $run->warn_count]);

        if ($run->overall === 'fail') {
            $failing = collect($report['groups'])->flatten(1)->where('status', 'fail')->pluck('label')->implode('; ');
            try {
                $this->notifications->fire('system.health_check_red', [
                    'title' => 'System Health is RED',
                    'message' => $failing,
                    'url' => 'admin.health',
                ]);
            } catch (\Throwable $e) {
                Log::error('health.notify_failed', ['error' => $e->getMessage()]);
            }
        }

        return $run;
    }
}
