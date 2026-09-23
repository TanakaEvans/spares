<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryReportController extends Controller
{
    public function index(Request $request, ReportService $reports): Response
    {
        $branchId = $request->integer('branch_id') ?: null;
        $filter = $request->string('filter')->toString() ?: null;

        return Inertia::render('Reports/Inventory/Index', [
            'stockValue' => $reports->stockValue($branchId),
            'onHand' => $reports->stockOnHand($branchId, $filter),
            'ageing' => $reports->stockAgeing($branchId),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => ['branch_id' => $branchId, 'filter' => $filter],
        ]);
    }
}
