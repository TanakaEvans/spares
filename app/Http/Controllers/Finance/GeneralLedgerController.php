<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\GlAccount;
use App\Services\JournalSourceResolver;
use App\Services\LedgerReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    public function index(Request $request, LedgerReportService $reports, JournalSourceResolver $sources): Response
    {
        $accounts = GlAccount::orderBy('account_code')->get(['id', 'account_code', 'name']);

        // `account=<code>` is the shared EntityLink convention (gl_account → GL enquiry);
        // `account_id` is kept for the page's own selector.
        $accountId = $request->integer('account_id')
            ?: ($request->filled('account') ? $accounts->firstWhere('account_code', $request->string('account')->toString())?->id : null)
            ?: $accounts->first()?->id;
        $from = $request->date('from')?->toDateString() ?? now()->startOfYear()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $account = $accountId ? GlAccount::find($accountId) : null;
        $movements = $account ? $reports->accountMovements($account, $from, $to) : ['opening' => 0, 'closing' => 0, 'rows' => []];

        $links = $sources->resolveMany(collect($movements['rows'])->map(fn ($r) => [$r['source_type'] ?? null, $r['source_id'] ?? null]));
        $movements['rows'] = array_map(function (array $r) use ($links) {
            $r['source'] = $links[($r['source_type'] ?? '').':'.($r['source_id'] ?? '')] ?? null;
            unset($r['source_type'], $r['source_id']);

            return $r;
        }, $movements['rows']);

        return Inertia::render('Finance/GL/Index', [
            'accounts' => $accounts,
            'account' => $account?->only(['id', 'account_code', 'name', 'type', 'normal_balance']),
            'movements' => $movements,
            'filters' => ['account_id' => $accountId, 'from' => $from, 'to' => $to],
        ]);
    }
}
