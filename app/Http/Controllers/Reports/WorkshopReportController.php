<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $branchId = $request->integer('branch_id') ?: null;

        return Inertia::render('Reports/Workshop/Index', [
            'productivity' => $reports->workshopProductivity($from, $to, $branchId),
            'profitability' => $reports->jobProfitability($from, $to, $branchId),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
        ]);
    }
}
