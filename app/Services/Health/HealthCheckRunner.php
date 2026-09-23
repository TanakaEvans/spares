<?php

namespace App\Services\Health;

use App\Models\Customer;
use App\Models\GlJournal;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Services\GlPostingService;
use App\Services\LedgerReportService;
use App\Services\OpeningBalanceService;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;

/**
 * The System Health runner (operations/system-health.md ★). Runs every
 * integrity invariant the ERP relies on and returns a grouped, at-a-glance
 * verdict. New invariants are added as a method here and listed in checks().
 */
class HealthCheckRunner
{
    public function __construct(
        private readonly GlPostingService $gl,
        private readonly LedgerReportService $ledger,
        private readonly StockLedgerService $stock,
        private readonly OpeningBalanceService $opening,
    ) {
    }

    /** @return array{groups: array, summary: array} */
    public function run(): array
    {
        $checks = [
            fn () => $this->journalsBalanced(),
            fn () => $this->trialBalance(),
            fn () => $this->arControl(),
            fn () => $this->apControl(),
            fn () => $this->stockValueGl(),
            fn () => $this->openPeriod(),
            fn () => $this->stockIntegrity(),
            fn () => $this->negativeStock(),
            fn () => $this->staleDraftPos(),
        ];

        $results = [];
        foreach ($checks as $check) {
            try {
                $results[] = $check();
            } catch (\Throwable $e) {
                $results[] = new HealthResult('error', 'System', 'Check failed to run', 'fail', $e->getMessage());
            }
        }

        $groups = [];
        foreach ($results as $r) {
            $groups[$r->group][] = $r->toArray();
        }

        $summary = [
            'ok' => count(array_filter($results, fn ($r) => $r->status === 'ok')),
            'warn' => count(array_filter($results, fn ($r) => $r->status === 'warn')),
            'fail' => count(array_filter($results, fn ($r) => $r->status === 'fail')),
            'overall' => 'ok',
        ];
        $summary['overall'] = $summary['fail'] > 0 ? 'fail' : ($summary['warn'] > 0 ? 'warn' : 'ok');

        return ['groups' => $groups, 'summary' => $summary];
    }

    // ── Financial ────────────────────────────────────────────────────────

    private function journalsBalanced(): HealthResult
    {
        $unbalanced = GlJournal::where('status', 'posted')
            ->whereRaw('(SELECT COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) FROM gl_journal_lines WHERE gl_journal_lines.journal_id = gl_journals.id) != 0')
            ->count();
        $total = GlJournal::where('status', 'posted')->count();

        return $unbalanced === 0
            ? new HealthResult('journals_balanced', 'Financial', 'Every posted journal balances', 'ok', "All {$total} journals balanced.")
            : new HealthResult('journals_balanced', 'Financial', 'Every posted journal balances', 'fail', "{$unbalanced} unbalanced journal(s).", link: 'finance.journals.index');
    }

    private function trialBalance(): HealthResult
    {
        $tb = $this->ledger->trialBalance(now()->toDateString());

        return $tb['balanced']
            ? new HealthResult('trial_balance', 'Financial', 'Trial balance balanced', 'ok', 'Debits = credits.')
            : new HealthResult('trial_balance', 'Financial', 'Trial balance balanced', 'fail',
                sprintf('Out by %.2f.', abs($tb['total_debit'] - $tb['total_credit'])), link: 'finance.reports.trial-balance');
    }

    private function arControl(): HealthResult
    {
        $sub = round(Customer::where('is_walk_in', false)->get()->sum(fn (Customer $c) => $c->arBalance()), 2);
        $ctrl = round($this->gl->accountBalance('1210'), 2);

        return abs($sub - $ctrl) < 0.01
            ? new HealthResult('ar_control', 'Financial', 'AR sub-ledger = Debtors control (1210)', 'ok', sprintf('Both %.2f.', $ctrl))
            : new HealthResult('ar_control', 'Financial', 'AR sub-ledger = Debtors control (1210)', 'fail',
                sprintf('Sub-ledger %.2f vs control %.2f.', $sub, $ctrl), link: 'finance.receipts.index');
    }

    private function apControl(): HealthResult
    {
        $sub = round(Supplier::all()->sum(fn (Supplier $s) => $s->apBalance()), 2);
        $ctrl = round($this->gl->accountBalance('2110'), 2);

        return abs($sub - $ctrl) < 0.01
            ? new HealthResult('ap_control', 'Financial', 'AP sub-ledger = Creditors control (2110)', 'ok', sprintf('Both %.2f.', $ctrl))
            : new HealthResult('ap_control', 'Financial', 'AP sub-ledger = Creditors control (2110)', 'fail',
                sprintf('Sub-ledger %.2f vs control %.2f.', $sub, $ctrl), link: 'finance.payments.index');
    }

    private function stockValueGl(): HealthResult
    {
        $value = $this->opening->stockValue();
        $gl = round($this->gl->accountBalance('1310'), 2);

        return abs($value - $gl) < 0.01
            ? new HealthResult('stock_gl', 'Financial', 'Stock value = Inventory control (1310)', 'ok', sprintf('Both %.2f.', $gl))
            : new HealthResult('stock_gl', 'Financial', 'Stock value = Inventory control (1310)', 'fail',
                sprintf('Stock %.2f vs GL %.2f — likely un-journalised opening stock.', $value, $gl), fix: 'admin.health.post-opening-stock');
    }

    private function openPeriod(): HealthResult
    {
        try {
            $period = $this->gl->openPeriodFor(now());

            return new HealthResult('open_period', 'Financial', 'Current period is open', 'ok', "Open: {$period->name}.");
        } catch (\Throwable $e) {
            return new HealthResult('open_period', 'Financial', 'Current period is open', 'fail', $e->getMessage(), link: 'finance.periods.index');
        }
    }

    // ── Stock ────────────────────────────────────────────────────────────

    private function stockIntegrity(): HealthResult
    {
        $mismatches = $this->stock->verifyIntegrity();

        return $mismatches->isEmpty()
            ? new HealthResult('stock_integrity', 'Stock', 'Stock levels = ledger sums', 'ok', 'Every level matches its ledger.')
            : new HealthResult('stock_integrity', 'Stock', 'Stock levels = ledger sums', 'fail',
                "{$mismatches->count()} part/branch level(s) drifted from the ledger.", link: 'inventory.stock.index');
    }

    private function negativeStock(): HealthResult
    {
        $negatives = StockLevel::where('qty_on_hand', '<', 0)->count();

        return $negatives === 0
            ? new HealthResult('negative_stock', 'Stock', 'No negative stock', 'ok', 'All levels ≥ 0.')
            : new HealthResult('negative_stock', 'Stock', 'No negative stock', 'warn',
                "{$negatives} level(s) below zero.", link: 'inventory.stock.index');
    }

    // ── Hygiene ──────────────────────────────────────────────────────────

    private function staleDraftPos(): HealthResult
    {
        $cutoff = now()->subDays(7);
        $stale = PurchaseOrder::where('status', 'draft')->where('created_at', '<', $cutoff)->count();

        return $stale === 0
            ? new HealthResult('stale_draft_pos', 'Hygiene', 'No purchase orders stuck in draft', 'ok', 'No draft PO older than 7 days.')
            : new HealthResult('stale_draft_pos', 'Hygiene', 'No purchase orders stuck in draft', 'warn',
                "{$stale} draft PO(s) older than 7 days.", link: 'purchasing.orders.index');
    }
}
