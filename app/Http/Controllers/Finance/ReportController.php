<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\LedgerReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly LedgerReportService $reports)
    {
    }

    public function trialBalance(Request $request): Response
    {
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $branchId = $request->integer('branch_id') ?: null;

        return Inertia::render('Finance/Reports/TrialBalance', [
            'report' => $this->reports->trialBalance($to, $branchId),
            'filters' => ['to' => $to, 'branch_id' => $branchId],
            'branches' => $this->branches(),
        ]);
    }

    public function incomeStatement(Request $request): Response
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfYear()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $branchId = $request->integer('branch_id') ?: null;

        return Inertia::render('Finance/Reports/IncomeStatement', [
            'report' => $this->reports->incomeStatement($from, $to, $branchId),
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'branches' => $this->branches(),
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $branchId = $request->integer('branch_id') ?: null;

        return Inertia::render('Finance/Reports/BalanceSheet', [
            'report' => $this->reports->balanceSheet($to, $branchId),
            'filters' => ['to' => $to, 'branch_id' => $branchId],
            'branches' => $this->branches(),
        ]);
    }

    private function branches()
    {
        return Branch::where('status', 'active')->get(['id', 'name']);
    }
}
