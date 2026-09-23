<?php

namespace App\Services;

use App\Models\GlAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only financial reporting over the posted ledger (Modules 7.2/7.7).
 * Never caches balances — every figure is derived from gl_journal_lines at
 * query time, so the reports can't drift from the books.
 */
class LedgerReportService
{
    /**
     * Debit/credit totals per account between two dates (inclusive), excluding
     * voided/reversed journals. Returns [account_id => ['debit'=>, 'credit'=>]].
     */
    public function balancesByAccount(?string $from, string $to, ?int $branchId = null): array
    {
        $rows = DB::table('gl_journal_lines as l')
            ->join('gl_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('j.status', 'posted')
            ->whereDate('j.journal_date', '<=', $to)
            ->when($from, fn ($q) => $q->whereDate('j.journal_date', '>=', $from))
            ->when($branchId, fn ($q) => $q->where('j.branch_id', $branchId))
            ->groupBy('l.account_id')
            ->selectRaw('l.account_id, SUM(l.debit) as debit, SUM(l.credit) as credit')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->account_id] = ['debit' => (float) $r->debit, 'credit' => (float) $r->credit];
        }

        return $out;
    }

    /** Signed balance in the account's natural direction (positive = normal side). */
    public function naturalBalance(GlAccount $account, float $debit, float $credit): float
    {
        $net = $debit - $credit;

        return $account->normal_balance === 'debit' ? round($net, 2) : round(-$net, 2);
    }

    /**
     * Balance in the SECTION's convention (not the account's own), so contra
     * accounts net correctly on statements: assets/expenses are debit-positive,
     * liabilities/equity/revenue are credit-positive.
     */
    private function sectionBalance(string $type, float $debit, float $credit): float
    {
        return in_array($type, ['asset', 'expense'], true)
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }

    /** Trial balance as at $to: every account with a movement, debit/credit columns. */
    public function trialBalance(string $to, ?int $branchId = null): array
    {
        $balances = $this->balancesByAccount(null, $to, $branchId);
        $accounts = GlAccount::orderBy('account_code')->get();

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $bal = $balances[$account->id] ?? null;
            if ($bal === null) {
                continue;
            }
            $net = round($bal['debit'] - $bal['credit'], 2);
            if (abs($net) < 0.005) {
                continue;
            }
            $debit = $net > 0 ? $net : 0.0;
            $credit = $net < 0 ? -$net : 0.0;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'account_code' => $account->account_code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return [
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /** Income statement for a date range: revenue − expenses = net profit. */
    public function incomeStatement(string $from, string $to, ?int $branchId = null): array
    {
        $balances = $this->balancesByAccount($from, $to, $branchId);
        $accounts = GlAccount::whereIn('type', ['revenue', 'expense'])->orderBy('account_code')->get();

        $revenue = [];
        $expenses = [];
        $totalRevenue = 0.0;
        $totalExpense = 0.0;

        foreach ($accounts as $account) {
            $bal = $balances[$account->id] ?? null;
            if ($bal === null) {
                continue;
            }
            $amount = $this->sectionBalance($account->type, $bal['debit'], $bal['credit']);
            if (abs($amount) < 0.005) {
                continue;
            }
            $line = ['account_code' => $account->account_code, 'name' => $account->name, 'category' => $account->category, 'amount' => $amount];

            if ($account->type === 'revenue') {
                $revenue[] = $line;
                $totalRevenue += $amount;
            } else {
                $expenses[] = $line;
                $totalExpense += $amount;
            }
        }

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => round($totalRevenue, 2),
            'total_expense' => round($totalExpense, 2),
            'net_profit' => round($totalRevenue - $totalExpense, 2),
        ];
    }

    /** Balance sheet as at $to. Retained earnings absorbs the YTD P&L movement. */
    public function balanceSheet(string $to, ?int $branchId = null): array
    {
        $balances = $this->balancesByAccount(null, $to, $branchId);
        $accounts = GlAccount::orderBy('account_code')->get()->keyBy('id');

        $assets = [];
        $liabilities = [];
        $equity = [];
        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $totalEquity = 0.0;
        $netProfit = 0.0;

        foreach ($accounts as $account) {
            $bal = $balances[$account->id] ?? null;
            if ($bal === null) {
                continue;
            }
            $amount = $this->sectionBalance($account->type, $bal['debit'], $bal['credit']);

            if (in_array($account->type, ['revenue', 'expense'], true)) {
                // Roll P&L into retained earnings (revenue − expenses).
                $netProfit += $account->type === 'revenue' ? $amount : -$amount;
                continue;
            }
            if (abs($amount) < 0.005) {
                continue;
            }
            $line = ['account_code' => $account->account_code, 'name' => $account->name, 'category' => $account->category, 'amount' => $amount];

            match ($account->type) {
                'asset' => [$assets[] = $line, $totalAssets += $amount],
                'liability' => [$liabilities[] = $line, $totalLiabilities += $amount],
                'equity' => [$equity[] = $line, $totalEquity += $amount],
                default => null,
            };
        }

        $netProfit = round($netProfit, 2);
        $totalEquity = round($totalEquity + $netProfit, 2);

        return [
            'as_at' => $to,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retained_current_year' => $netProfit,
            'total_assets' => round($totalAssets, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            'total_equity' => $totalEquity,
            'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Ledger enquiry: every posted line on an account in a date range with a
     * running balance. Opening balance is the natural balance before $from.
     */
    public function accountMovements(GlAccount $account, string $from, string $to, ?int $branchId = null): array
    {
        $opening = 0.0;
        $priorRows = DB::table('gl_journal_lines as l')
            ->join('gl_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('j.status', 'posted')
            ->where('l.account_id', $account->id)
            ->whereDate('j.journal_date', '<', $from)
            ->when($branchId, fn ($q) => $q->where('j.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(l.debit),0) d, COALESCE(SUM(l.credit),0) c')
            ->first();
        $opening = $this->naturalBalance($account, (float) $priorRows->d, (float) $priorRows->c);

        $lines = DB::table('gl_journal_lines as l')
            ->join('gl_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('j.status', 'posted')
            ->where('l.account_id', $account->id)
            ->whereDate('j.journal_date', '>=', $from)
            ->whereDate('j.journal_date', '<=', $to)
            ->when($branchId, fn ($q) => $q->where('j.branch_id', $branchId))
            ->orderBy('j.journal_date')->orderBy('j.id')->orderBy('l.id')
            ->get(['j.journal_number', 'j.journal_date', 'j.description as jdesc', 'j.reference',
                'l.description', 'l.debit', 'l.credit']);

        $running = $opening;
        $sign = $account->normal_balance === 'debit' ? 1 : -1;
        $rows = [];
        foreach ($lines as $l) {
            $running = round($running + $sign * ((float) $l->debit - (float) $l->credit), 2);
            $rows[] = [
                'journal_number' => $l->journal_number,
                'date' => Carbon::parse($l->journal_date)->toDateString(),
                'description' => $l->description ?: $l->jdesc,
                'reference' => $l->reference,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
                'balance' => $running,
            ];
        }

        return [
            'opening' => round($opening, 2),
            'closing' => round($running, 2),
            'rows' => $rows,
        ];
    }
}
