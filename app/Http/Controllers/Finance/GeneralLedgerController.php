<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\GlAccount;
use App\Services\LedgerReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    public function index(Request $request, LedgerReportService $reports): Response
    {
        $accounts = GlAccount::orderBy('account_code')->get(['id', 'account_code', 'name']);
        $accountId = $request->integer('account_id') ?: $accounts->first()?->id;
        $from = $request->date('from')?->toDateString() ?? now()->startOfYear()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $account = $accountId ? GlAccount::find($accountId) : null;
        $movements = $account ? $reports->accountMovements($account, $from, $to) : ['opening' => 0, 'closing' => 0, 'rows' => []];

        return Inertia::render('Finance/GL/Index', [
            'accounts' => $accounts,
            'account' => $account?->only(['id', 'account_code', 'name', 'type', 'normal_balance']),
            'movements' => $movements,
            'filters' => ['account_id' => $accountId, 'from' => $from, 'to' => $to],
        ]);
    }
}
