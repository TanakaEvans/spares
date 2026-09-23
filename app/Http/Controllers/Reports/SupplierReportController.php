<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\ApPaymentService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierReportController extends Controller
{
    public function index(Request $request, ReportService $reports, ApPaymentService $ap): Response
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        return Inertia::render('Reports/Suppliers/Index', [
            'spend' => $reports->supplierSpend($from, $to),
            'ageing' => $ap->ageing(now()->toDateString()),
            'openPos' => $reports->openPurchaseOrders(),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }
}
