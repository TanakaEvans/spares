<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduledReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Scheduled/Index', [
            'reports' => ScheduledReport::latest('id')->get()->map(fn (ScheduledReport $r) => [
                'id' => $r->id, 'name' => $r->name, 'report_key' => $r->report_key, 'frequency' => $r->frequency,
                'run_time' => $r->run_time, 'recipients' => $r->recipients, 'format' => $r->format,
                'is_active' => $r->is_active, 'last_run_at' => $r->last_run_at?->toDateTimeString(),
            ]),
            'reportKeys' => [
                'sales_summary' => 'Daily Sales Summary', 'ar_ageing' => 'AR Ageing', 'ap_ageing' => 'AP Ageing',
                'stock_reorder' => 'Stock Reorder', 'income_statement' => 'Income Statement', 'top_customers' => 'Top Customers',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'report_key' => ['required', 'string', 'max:60'],
            'frequency' => ['required', 'in:daily,weekly,monthly'], 'run_time' => ['required', 'string', 'max:5'],
            'recipients' => ['nullable', 'string', 'max:500'], 'format' => ['required', 'in:pdf,excel'],
        ]);
        ScheduledReport::create($data + ['is_active' => true]);

        return back()->with('success', 'Scheduled report created. (Delivery runs once the scheduler is enabled — operations/backup-and-resilience.md.)');
    }

    public function toggle(ScheduledReport $scheduledReport): RedirectResponse
    {
        $scheduledReport->update(['is_active' => ! $scheduledReport->is_active]);

        return back();
    }
}
