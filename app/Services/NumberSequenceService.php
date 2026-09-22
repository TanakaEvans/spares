<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\NumberSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NumberSequenceService
{
    /**
     * Generate the next number for a document type, gaplessly, safe under concurrency.
     * MUST be called inside the same DB transaction as the document insert —
     * if the insert rolls back, the number is released with it.
     *
     * Resolution: branch-specific sequence if configured, else the global one.
     */
    public function next(string $type, Branch|int|null $branch = null): string
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return DB::transaction(function () use ($type, $branchId) {
            $sequence = NumberSequence::where('type', $type)
                ->where(fn ($q) => $branchId === null
                    ? $q->whereNull('branch_id')
                    : $q->where('branch_id', $branchId))
                ->lockForUpdate()
                ->first();

            // Fall back to the global sequence when no branch-specific one exists.
            if ($sequence === null && $branchId !== null) {
                $sequence = NumberSequence::where('type', $type)
                    ->whereNull('branch_id')
                    ->lockForUpdate()
                    ->first();
            }

            if ($sequence === null) {
                throw new InvalidArgumentException("No number sequence configured for [{$type}].");
            }

            $this->applyResetIfDue($sequence);

            $number = $sequence->next_number;
            $sequence->update(['next_number' => $number + 1]);

            return $this->format($sequence, $number);
        });
    }

    /**
     * Preview the next number without consuming it (for display on create screens).
     */
    public function peek(string $type, Branch|int|null $branch = null): string
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        $sequence = NumberSequence::where('type', $type)
            ->where(fn ($q) => $branchId === null
                ? $q->whereNull('branch_id')
                : $q->where('branch_id', $branchId))
            ->first()
            ?? NumberSequence::where('type', $type)->whereNull('branch_id')->first();

        if ($sequence === null) {
            throw new InvalidArgumentException("No number sequence configured for [{$type}].");
        }

        $number = $this->isResetDue($sequence) ? 1 : $sequence->next_number;

        return $this->format($sequence, $number);
    }

    private function format(NumberSequence $sequence, int $number): string
    {
        $parts = [$sequence->prefix];

        if ($sequence->include_date) {
            $parts[] = now()->format($sequence->date_format);
        }

        $parts[] = str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);

        return implode('-', $parts);
    }

    private function applyResetIfDue(NumberSequence $sequence): void
    {
        if ($this->isResetDue($sequence)) {
            $sequence->update([
                'next_number' => 1,
                'last_reset_at' => now()->toDateString(),
            ]);
            $sequence->refresh();
        }
    }

    private function isResetDue(NumberSequence $sequence): bool
    {
        if ($sequence->reset_frequency === 'never') {
            return false;
        }

        $last = $sequence->last_reset_at;

        return match ($sequence->reset_frequency) {
            'yearly' => $last === null || ! $last->isSameYear(Carbon::today()),
            'monthly' => $last === null || ! $last->isSameMonth(Carbon::today()),
            default => false,
        };
    }
}
