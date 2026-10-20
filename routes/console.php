<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| The system's heartbeat (operations/system-health.md, backup-and-resilience.md)
|--------------------------------------------------------------------------
| Driven by ONE OS job that runs `php artisan schedule:run` every minute
| (cron on Linux, Task Scheduler on Windows — see operations/deployment.md).
*/

// Heartbeat: System Health reads this to prove the scheduler is alive.
Schedule::call(fn () => Cache::put('scheduler.heartbeat', now()->toIso8601String(), now()->addDay()))
    ->everyMinute()->name('scheduler-heartbeat');

// Nightly database backup with retention (storage/app/backups).
Schedule::command('backup:run')->dailyAt('22:00')->name('nightly-backup')->withoutOverlapping();

// Nightly full integrity run, persisted; a red result notifies administrators.
Schedule::command('health:run --trigger=scheduled')->dailyAt('06:00')->name('nightly-health')->withoutOverlapping();

// Housekeeping.
Schedule::command('queue:prune-failed --hours=168')->weekly()->name('prune-failed-jobs');
Schedule::command('auth:clear-resets')->daily();
