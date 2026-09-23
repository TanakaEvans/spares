<?php

namespace App\Services;

use App\Exceptions\ClosedPeriodException;
use App\Exceptions\ControlAccountPostingException;
use App\Exceptions\NoOpenPeriodException;
use App\Exceptions\UnbalancedJournalException;
use App\Models\Branch;
use App\Models\GlAccount;
use App\Models\GlJournal;
use App\Models\GlPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GlPostingService
{
    public function __construct(private readonly NumberSequenceService $sequences)
    {
    }

    /**
     * Post a balanced journal atomically. The ONLY write path into the GL.
     *
     * $lines: [['account' => 'code'|GlAccount|id, 'debit' => x, 'credit' => y,
     *           'description' => ?, 'reference' => ?], …]
     *
     * @param  bool  $fromSubLedger  true when posting on behalf of a sub-ledger
     *                               (AR/AP/inventory) — control accounts allowed.
     */
    public function post(
        string $journalType,
        CarbonInterface|string $date,
        string $description,
        array $lines,
        ?string $reference = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        Branch|int|null $branch = null,
        ?int $userId = null,
        bool $fromSubLedger = false,
    ): GlJournal {
        $date = Carbon::parse($date);
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        $period = $this->openPeriodFor($date);
        $resolved = $this->resolveLines($lines, $fromSubLedger);
        $this->assertBalanced($resolved);

        return DB::transaction(function () use ($journalType, $date, $description, $reference, $sourceType, $sourceId, $branchId, $userId, $period, $resolved) {
            $journal = GlJournal::create([
                'journal_number' => $this->sequences->next('journal', $branchId),
                'journal_type' => $journalType,
                'period_id' => $period->id,
                'branch_id' => $branchId,
                'journal_date' => $date->toDateString(),
                'description' => $description,
                'reference' => $reference,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            foreach ($resolved as $line) {
                $journal->lines()->create($line);
            }

            return $journal;
        });
    }

    /**
     * Reverse a posted journal by creating an equal-and-opposite journal.
     * Never edits or deletes the original.
     */
    public function reverse(GlJournal $journal, ?string $reason = null, ?int $userId = null): GlJournal
    {
        if ($journal->status === 'reversed') {
            throw new InvalidArgumentException("Journal {$journal->journal_number} is already reversed.");
        }

        return DB::transaction(function () use ($journal, $reason, $userId) {
            $reversal = $this->post(
                journalType: $journal->journal_type,
                date: now(),
                description: 'REVERSAL: '.($reason ?: $journal->description),
                lines: $journal->lines->map(fn ($l) => [
                    'account' => $l->account_id,
                    'debit' => (float) $l->credit,
                    'credit' => (float) $l->debit,
                    'description' => $l->description,
                ])->all(),
                reference: $journal->journal_number,
                sourceType: $journal->source_type,
                sourceId: $journal->source_id,
                branch: $journal->branch_id,
                userId: $userId,
                fromSubLedger: true, // reversal mirrors original lines incl. control accounts
            );

            $journal->update([
                'status' => 'reversed',
                'reversed_by' => $userId,
                'reversing_journal_id' => $reversal->id,
            ]);

            return $reversal;
        });
    }

    public function accountBalance(GlAccount|string $account): float
    {
        $account = $this->resolveAccount($account);

        $sums = $account->journalLines()
            ->whereHas('journal', fn ($q) => $q->where('status', '!=', 'void'))
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        $net = (float) $sums->d - (float) $sums->c;

        return $account->normal_balance === 'debit' ? $net : -$net;
    }

    public function openPeriodFor(CarbonInterface|string $date): GlPeriod
    {
        $date = Carbon::parse($date);

        $period = GlPeriod::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($period === null) {
            throw new NoOpenPeriodException($date->toDateString());
        }

        if (! $period->isOpen()) {
            throw new ClosedPeriodException($period->name, $date->toDateString());
        }

        return $period;
    }

    private function resolveLines(array $lines, bool $fromSubLedger): array
    {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('A journal needs at least two lines.');
        }

        return array_map(function (array $line) use ($fromSubLedger) {
            $account = $this->resolveAccount($line['account']);

            if (! $account->allow_direct_posting && ! $fromSubLedger) {
                throw new ControlAccountPostingException($account->account_code);
            }

            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException('Journal amounts cannot be negative — swap the side instead.');
            }
            if (($debit > 0) === ($credit > 0)) {
                throw new InvalidArgumentException('Each journal line must be either a debit or a credit.');
            }

            return [
                'account_id' => $account->id,
                'description' => $line['description'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
                'reference' => $line['reference'] ?? null,
            ];
        }, $lines);
    }

    private function assertBalanced(array $resolved): void
    {
        $debits = round(array_sum(array_column($resolved, 'debit')), 2);
        $credits = round(array_sum(array_column($resolved, 'credit')), 2);

        if ($debits !== $credits) {
            throw new UnbalancedJournalException($debits, $credits);
        }
    }

    private function resolveAccount(GlAccount|string|int $account): GlAccount
    {
        if ($account instanceof GlAccount) {
            return $account;
        }

        $model = is_int($account)
            ? GlAccount::find($account)
            : GlAccount::where('account_code', $account)->first();

        if ($model === null) {
            throw new InvalidArgumentException("Unknown GL account [{$account}].");
        }

        return $model;
    }
}
