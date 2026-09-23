<?php

namespace Tests\Unit;

use App\Exceptions\ClosedPeriodException;
use App\Exceptions\ControlAccountPostingException;
use App\Exceptions\NoOpenPeriodException;
use App\Exceptions\UnbalancedJournalException;
use App\Models\GlJournal;
use App\Models\GlPeriod;
use App\Services\GlPostingService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialPeriodSeeder;
use Database\Seeders\NumberSequenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    private GlPostingService $gl;

    protected function setUp(): void
    {
        parent::setUp();

        (new ChartOfAccountsSeeder)->run();
        (new FinancialPeriodSeeder)->run();
        (new NumberSequenceSeeder)->run();

        $this->gl = app(GlPostingService::class);
    }

    private function cashSaleLines(float $amount = 115.0): array
    {
        return [
            ['account' => '1120', 'debit' => $amount, 'credit' => 0, 'description' => 'Bank'],
            ['account' => '4100', 'debit' => 0, 'credit' => round($amount / 1.15, 2), 'description' => 'Sales'],
            ['account' => '2210', 'debit' => 0, 'credit' => round($amount - $amount / 1.15, 2), 'description' => 'VAT'],
        ];
    }

    public function test_posts_a_balanced_journal_with_sequenced_number(): void
    {
        $journal = $this->gl->post('sales', now(), 'Cash sale', $this->cashSaleLines(), fromSubLedger: true);

        $this->assertSame('posted', $journal->status);
        $this->assertStringStartsWith('JNL-', $journal->journal_number);
        $this->assertCount(3, $journal->lines);

        $debits = (float) $journal->lines()->sum('debit');
        $credits = (float) $journal->lines()->sum('credit');
        $this->assertSame($debits, $credits);
    }

    public function test_unbalanced_journal_is_rejected_and_nothing_is_written(): void
    {
        try {
            $this->gl->post('general', now(), 'Broken', [
                ['account' => '1120', 'debit' => 100, 'credit' => 0],
                ['account' => '6000', 'debit' => 0, 'credit' => 90],
            ]);
            $this->fail('Expected UnbalancedJournalException');
        } catch (UnbalancedJournalException) {
            // expected
        }

        $this->assertSame(0, GlJournal::count());
    }

    public function test_direct_posting_to_control_account_is_blocked(): void
    {
        $this->expectException(ControlAccountPostingException::class);

        $this->gl->post('general', now(), 'Manual to debtors', [
            ['account' => '1210', 'debit' => 100, 'credit' => 0],
            ['account' => '4100', 'debit' => 0, 'credit' => 100],
        ]);
    }

    public function test_sub_ledger_may_post_to_control_accounts(): void
    {
        $journal = $this->gl->post('sales', now(), 'Credit sale', [
            ['account' => '1210', 'debit' => 115, 'credit' => 0],
            ['account' => '4100', 'debit' => 0, 'credit' => 100],
            ['account' => '2210', 'debit' => 0, 'credit' => 15],
        ], fromSubLedger: true);

        $this->assertSame('posted', $journal->status);
    }

    public function test_posting_into_closed_period_is_rejected(): void
    {
        GlPeriod::where('period_number', now()->month)->update(['status' => 'closed']);

        $this->expectException(ClosedPeriodException::class);

        $this->gl->post('general', now(), 'Late entry', [
            ['account' => '1120', 'debit' => 10, 'credit' => 0],
            ['account' => '6000', 'debit' => 0, 'credit' => 10],
        ]);
    }

    public function test_date_outside_any_period_is_rejected(): void
    {
        $this->expectException(NoOpenPeriodException::class);

        $this->gl->post('general', now()->addYears(3), 'Future entry', [
            ['account' => '1120', 'debit' => 10, 'credit' => 0],
            ['account' => '6000', 'debit' => 0, 'credit' => 10],
        ]);
    }

    public function test_line_with_both_sides_or_negative_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->gl->post('general', now(), 'Bad line', [
            ['account' => '1120', 'debit' => 10, 'credit' => 10],
            ['account' => '6000', 'debit' => 0, 'credit' => 0],
        ]);
    }

    public function test_reversal_creates_opposite_journal_and_flags_original(): void
    {
        $original = $this->gl->post('sales', now(), 'Cash sale', $this->cashSaleLines(), fromSubLedger: true);

        $reversal = $this->gl->reverse($original, 'posted in error', null);

        $original->refresh();
        $this->assertSame('reversed', $original->status);
        $this->assertSame($reversal->id, $original->reversing_journal_id);

        // Opposite sides.
        $origBank = $original->lines()->whereHas('account', fn ($q) => $q->where('account_code', '1120'))->first();
        $revBank = $reversal->lines()->whereHas('account', fn ($q) => $q->where('account_code', '1120'))->first();
        $this->assertSame((float) $origBank->debit, (float) $revBank->credit);

        // Net effect on the account is zero.
        $this->assertSame(0.0, $this->gl->accountBalance('1120'));
    }

    public function test_account_balance_respects_normal_balance_side(): void
    {
        $this->gl->post('sales', now(), 'Cash sale', $this->cashSaleLines(115), fromSubLedger: true);

        $this->assertSame(115.0, $this->gl->accountBalance('1120'));   // debit-normal asset
        $this->assertSame(100.0, $this->gl->accountBalance('4100'));   // credit-normal revenue
        $this->assertSame(15.0, $this->gl->accountBalance('2210'));    // credit-normal VAT output
    }
}
