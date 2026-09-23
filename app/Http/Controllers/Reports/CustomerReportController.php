<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\ArReceiptService;
use App\Services\ReportService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerReportController extends Controller
{
    public function index(Request $request, ReportService $reports, ArReceiptService $ar, SettingsService $settings): Response
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $dormantDays = $request->integer('dormant_days') ?: 90;

        return Inertia::render('Reports/Customers/Index', [
            'ageing' => $ar->ageing(now()->toDateString()),
            'topCustomers' => $reports->topCustomers($from, $to),
            'dormant' => $reports->dormantCustomers($dormantDays),
            'creditReview' => $reports->creditReview(),
            'filters' => ['from' => $from, 'to' => $to, 'dormant_days' => $dormantDays],
        ]);
    }
}
