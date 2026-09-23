<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankStatementLine;
use App\Models\GlAccount;
use App\Services\GlPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    public function index(Request $request, GlPostingService $gl): Response
    {
        $accounts = BankAccount::with('glAccount:id,account_code,name')->get()->map(fn (BankAccount $a) => [
            'id' => $a->id, 'name' => $a->name, 'bank_name' => $a->bank_name, 'account_number' => $a->account_number,
            'currency' => $a->currency, 'gl_account' => $a->glAccount?->account_code,
            'gl_balance' => $a->glAccount ? round($gl->accountBalance($a->glAccount->account_code), 2) : null,
            'unreconciled' => BankStatementLine::where('bank_account_id', $a->id)->where('reconciled', false)->count(),
        ]);

        $selected = $request->integer('account_id') ?: $accounts->first()['id'] ?? null;
        $lines = $selected ? BankStatementLine::where('bank_account_id', $selected)->orderByDesc('txn_date')->limit(100)->get()
            ->map(fn (BankStatementLine $l) => ['id' => $l->id, 'txn_date' => $l->txn_date->toDateString(), 'description' => $l->description, 'amount' => (float) $l->amount, 'reconciled' => $l->reconciled]) : [];

        return Inertia::render('Finance/Bank/Index', [
            'accounts' => $accounts,
            'bankGlAccounts' => GlAccount::whereIn('account_code', ['1110', '1120', '1130'])->get(['id', 'account_code', 'name']),
            'selectedId' => $selected,
            'lines' => $lines,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:40'], 'gl_account_id' => ['nullable', 'exists:gl_accounts,id'], 'currency' => ['required', 'string', 'size:3'],
        ]);
        BankAccount::create($data + ['is_active' => true]);

        return back()->with('success', 'Bank account added.');
    }

    public function addLine(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $request->validate([
            'txn_date' => ['required', 'date'], 'description' => ['required', 'string', 'max:200'], 'amount' => ['required', 'numeric'],
        ]);
        $bankAccount->statementLines()->create($data + ['reconciled' => false]);

        return back()->with('success', 'Statement line added.');
    }

    public function reconcile(BankStatementLine $line): RedirectResponse
    {
        $line->update(['reconciled' => ! $line->reconciled]);

        return back();
    }
}
