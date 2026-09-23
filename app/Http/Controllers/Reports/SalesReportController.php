<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        [$from, $to, $branchId] = $this->range($request);

        return Inertia::render('Reports/Sales/Index', [
            'summary' => $reports->salesSummary($from, $to, $branchId),
            'byCategory' => $reports->salesByCategory($from, $to, $branchId),
            'byCustomer' => $reports->salesByCustomer($from, $to, 15, $branchId),
            'topParts' => $reports->topPartsByValue($from, $to, 15, $branchId),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
        ]);
    }

    private function range(Request $request): array
    {
        return [
            $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString(),
            $request->date('to')?->toDateString() ?? now()->toDateString(),
            $request->integer('branch_id') ?: null,
        ];
    }
}
