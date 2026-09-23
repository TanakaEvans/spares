<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\CashupService;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Daily cash-up (Module 2.4 receipting). A read-only X/Z read of a branch's
 * takings by payment method and cashier, viewable on screen and printable.
 */
class CashupController extends Controller
{
    public function index(Request $request, CashupService $cashup): Response
    {
        [$date, $branchId] = $this->params($request);

        return Inertia::render('Reports/Cashup/Index', [
            'cashup' => $cashup->forDay($date, $branchId),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'filters' => ['date' => $date, 'branch_id' => $branchId],
        ]);
    }

    public function print(Request $request, CashupService $cashup, DocumentPdfService $pdf): HttpResponse
    {
        [$date, $branchId] = $this->params($request);
        $branch = $branchId ? Branch::find($branchId) : null;

        return $pdf->render('print.cashup', [
            'cashup' => $cashup->forDay($date, $branchId),
            'branchName' => $branch?->name ?? 'All branches',
        ], $branchId)->stream("cashup-{$date}.pdf");
    }

    /** @return array{0:string,1:?int} */
    private function params(Request $request): array
    {
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $branchId = $request->integer('branch_id') ?: (Branch::where('is_main_branch', true)->value('id') ?? Branch::min('id'));

        return [$date, $branchId];
    }
}
