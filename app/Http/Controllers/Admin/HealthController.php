<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthCheckRunner;
use App\Services\OpeningBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthController extends Controller
{
    public function index(HealthCheckRunner $runner): Response
    {
        $report = $runner->run();

        return Inertia::render('Admin/Health/Index', [
            'groups' => $report['groups'],
            'summary' => $report['summary'],
            'ranAt' => now()->toDateTimeString(),
        ]);
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
