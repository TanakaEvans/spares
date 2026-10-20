<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Services\NotificationRouterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Dump the database to storage/app/backups (gzipped) and apply retention';

    public function handle(BackupService $backups, NotificationRouterService $notifications): int
    {
        try {
            $file = $backups->run();
            $this->info('Backup written: '.basename($file).' ('.round(filesize($file) / 1024).' KB)');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('backup.failed', ['error' => $e->getMessage()]);
            $this->error('Backup failed: '.$e->getMessage());
            try {
                $notifications->fire('system.backup_failed', [
                    'title' => 'Database backup failed',
                    'message' => $e->getMessage(),
                    'url' => 'admin.backups.index',
                ]);
            } catch (\Throwable) {
                // notification plumbing must never mask the backup failure
            }

            return self::FAILURE;
        }
    }
}
