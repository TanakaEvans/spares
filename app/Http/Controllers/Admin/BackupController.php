<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\BackupService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Backup Management (operations/backup-and-resilience.md): take a real
 * database dump now, review the nightly ones, download for off-site copy.
 */
class BackupController extends Controller
{
    public function index(BackupService $backups, SettingsService $settings): Response
    {
        $files = $backups->list();

        return Inertia::render('Admin/Backups/Index', [
            'files' => $files,
            'latest' => $files[0] ?? null,
            'retentionDays' => (int) $settings->get('backup.retention_days'),
            'maxAgeHours' => (int) $settings->get('backup.max_age_hours'),
            'method' => $backups->mysqldumpBinary() ? 'mysqldump' : 'PHP dump',
            'directory' => $backups->directory(),
        ]);
    }

    public function store(BackupService $backups, AuditLogService $audit): RedirectResponse
    {
        try {
            $file = $backups->run();
        } catch (\Throwable $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }

        $audit->record('backup.taken', null, null, ['file' => basename($file), 'bytes' => filesize($file)], 'Manual backup');

        return back()->with('success', 'Backup '.basename($file).' written ('.round(filesize($file) / 1024).' KB). Copy it off-site.');
    }

    public function download(string $file, BackupService $backups, AuditLogService $audit): BinaryFileResponse
    {
        $path = $backups->path($file);
        abort_unless($path, 404);

        $audit->record('backup.downloaded', null, null, ['file' => $file]);

        return response()->download($path);
    }
}
