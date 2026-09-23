<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\GlAccount;
use App\Services\LedgerReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountsController extends Controller
{
    public function index(Request $request, LedgerReportService $reports): Response
    {
        $balances = $reports->balancesByAccount(null, now()->toDateString());

        $accounts = GlAccount::orderBy('account_code')->get()->map(function (GlAccount $a) use ($balances, $reports) {
            $bal = $balances[$a->id] ?? ['debit' => 0, 'credit' => 0];

            return [
                'id' => $a->id,
                'account_code' => $a->account_code,
                'name' => $a->name,
                'type' => $a->type,
                'category' => $a->category,
                'is_control_account' => $a->is_control_account,
                'allow_direct_posting' => $a->allow_direct_posting,
                'is_active' => $a->is_active,
                'balance' => $reports->naturalBalance($a, (float) $bal['debit'], (float) $bal['credit']),
            ];
        });

        return Inertia::render('Finance/COA/Index', [
            'accounts' => $accounts,
            'groups' => ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses'],
        ]);
    }
}
