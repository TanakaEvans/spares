<?php

namespace App\Console\Commands;

use App\Services\Health\HealthRunService;
use Illuminate\Console\Command;

class RunHealthChecks extends Command
{
    protected $signature = 'health:run {--trigger=manual : scheduled | manual}';

    protected $description = 'Run every System Health check, persist the result and notify administrators if anything is red';

    public function handle(HealthRunService $health): int
    {
        $run = $health->runAndPersist((string) $this->option('trigger'));

        foreach ($run->results as $group => $checks) {
            $this->line("<comment>{$group}</comment>");
            foreach ($checks as $c) {
                $mark = ['ok' => '<info>✓</info>', 'warn' => '<comment>!</comment>', 'fail' => '<error>✗</error>'][$c['status']] ?? '?';
                $this->line("  {$mark} {$c['label']} — {$c['message']}");
            }
        }
        $this->newLine();
        $this->line(strtoupper($run->overall).": {$run->ok_count} ok · {$run->warn_count} warnings · {$run->fail_count} failures");

        return $run->overall === 'fail' ? self::FAILURE : self::SUCCESS;
    }
}
