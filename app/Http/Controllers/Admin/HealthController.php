<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HealthCheckRun;
use App\Services\Health\HealthCheckRunner;
use App\Services\Health\HealthRunService;
use App\Services\Health\OperationsChecks;
use App\Services\OpeningBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthController extends Controller
{
    public function index(HealthCheckRunner $runner, OperationsChecks $ops): Response
    {
        $report = $runner->run();

        // Merge in the operations + deployment-readiness checks (live, not persisted on view).
        foreach (array_merge($ops->operations(), $ops->deployment()) as $result) {
            $report['groups'][$result->group][] = $result->toArray();
            $report['summary'][$result->status === 'ok' ? 'ok' : ($result->status === 'warn' ? 'warn' : 'fail')]++;
        }
        $report['summary']['overall'] = $report['summary']['fail'] > 0 ? 'fail' : ($report['summary']['warn'] > 0 ? 'warn' : 'ok');

        $last = HealthCheckRun::query()->latest('ran_at')->first();

        return Inertia::render('Admin/Health/Index', [
            'groups' => $report['groups'],
            'summary' => $report['summary'],
            'ranAt' => now()->toDateTimeString(),
            'lastScheduled' => $last ? ['trigger' => $last->trigger, 'overall' => $last->overall, 'at' => $last->ran_at->diffForHumans()] : null,
        ]);
    }

    /** Run every check and persist it (the nightly job calls `health:run`). */
    public function run(HealthRunService $health): RedirectResponse
    {
        $run = $health->runAndPersist('manual');

        return back()->with($run->overall === 'fail' ? 'error' : 'success',
            "Health check complete: {$run->ok_count} ok, {$run->warn_count} warnings, {$run->fail_count} failures.");
    }

    /** One-click fix: bring opening stock onto the Inventory GL (migration). */
    public function postOpeningStock(Request $request, OpeningBalanceService $opening): RedirectResponse
    {
        $journal = $opening->postOpeningInventory($request->user()->id);

        return back()->with('success', $journal
            ? "Opening inventory posted as {$journal->journal_number} — Inventory GL now reconciles."
            : 'Inventory already reconciled — nothing to post.');
    }
}
