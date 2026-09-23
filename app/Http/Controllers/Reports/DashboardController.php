<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        $branchId = $request->integer('branch_id') ?: null;
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        return Inertia::render('Reports/Dashboard', [
            'kpis' => $reports->kpis($branchId),
            'salesTrend' => $reports->salesTrend($branchId),
            'arAgeing' => $reports->arAgeingProfile(),
            'topParts' => $reports->topPartsByValue($from, $to, 8, $branchId),
            'salesByCategory' => $reports->salesByCategory($from, $to, $branchId),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => ['branch_id' => $branchId],
        ]);
    }
}
