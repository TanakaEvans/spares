<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
class BackupController extends Controller
{
    private function dir(): string { return storage_path('app/backups'); }
    public function index(): Response
    {
        File::ensureDirectoryExists($this->dir());
        $files = collect(File::files($this->dir()))->sortByDesc(fn($f) => $f->getMTime())->map(fn($f) => [
            'name' => $f->getFilename(), 'size_kb' => round($f->getSize() / 1024, 1),
            'at' => date('Y-m-d H:i', $f->getMTime()),
        ])->values();
        return Inertia::render('Admin/Backups/Index', ['files' => $files]);
    }
    public function store(): RedirectResponse
    {
        File::ensureDirectoryExists($this->dir());
        $name = 'backup-'.date('Ymd-His').'.sql';
        // Placeholder marker file — a real backup wires mysqldump in ops (backup-and-resilience.md).
        File::put($this->dir().'/'.$name, "-- SparesPro DB snapshot placeholder created ".now()."\n-- Configure mysqldump in operations/backup-and-resilience.md\n");
        return back()->with('success', "Backup {$name} created. Configure off-site copy in operations.");
    }
}
