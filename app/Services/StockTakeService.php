<?php

namespace App\Services;

use App\Models\StockLevel;
use App\Models\StockTake;
use App\Models\StockTakeLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stock takes (Module 1.4): freeze a system snapshot, count, review the
 * variance, then post the difference as one adjustment through
 * StockMovementService — the ledger stays the single source of truth.
 */
class StockTakeService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly StockMovementService $movements,
    ) {
    }

    /**
     * Open a take and snapshot system quantities. For a full take, every part
     * with a stock level at the branch is included; a spot take snapshots only
     * the given parts.
     *
     * @param  int[]  $partIds  spot takes only
     */
    public function start(int $branchId, string $type = 'full', array $partIds = [], ?int $userId = null): StockTake
    {
        if (! in_array($type, ['full', 'spot'], true)) {
            throw new InvalidArgumentException('Stock take type must be full or spot.');
        }
        if ($type === 'spot' && $partIds === []) {
            throw new InvalidArgumentException('A spot take needs at least one part.');
        }

        return DB::transaction(function () use ($branchId, $type, $partIds, $userId) {
            $take = StockTake::create([
                'take_number' => $this->sequences->next('stock_take', $branchId),
                'branch_id' => $branchId,
                'type' => $type,
                'status' => 'counting',
                'started_by' => $userId,
            ]);

            $levels = StockLevel::where('branch_id', $branchId)
                ->when($type === 'spot', fn ($q) => $q->whereIn('part_id', $partIds))
                ->get();

            foreach ($levels as $level) {
                $take->lines()->create([
                    'part_id' => $level->part_id,
                    'system_qty' => (float) $level->qty_on_hand,
                    'unit_cost' => (float) $level->average_cost,
                ]);
            }

            return $take->fresh('lines');
        });
    }

    public function recordCount(StockTakeLine $line, float $countedQty): StockTakeLine
    {
        if ($line->take->status !== 'counting') {
            throw new InvalidArgumentException('Counts can only be entered while the take is counting.');
        }
        if ($countedQty < 0) {
            throw new InvalidArgumentException('Counted quantity cannot be negative.');
        }

        $line->update(['counted_qty' => $countedQty]);

        return $line;
    }

    public function moveToReview(StockTake $take): StockTake
    {
        if ($take->status !== 'counting') {
            throw new InvalidArgumentException("Take {$take->take_number} is not counting.");
        }

        $take->update(['status' => 'review']);

        return $take;
    }

    /**
     * Post the variance. Uncounted lines are treated as counted at system qty
     * (no change). The net difference becomes a single stock adjustment.
     */
    public function post(StockTake $take, ?int $userId = null): StockTake
    {
        if (! in_array($take->status, ['counting', 'review'], true)) {
            throw new InvalidArgumentException("Take {$take->take_number} is already {$take->status}.");
        }

        return DB::transaction(function () use ($take, $userId) {
            $adjustmentLines = [];

            foreach ($take->lines as $line) {
                if ($line->counted_qty === null) {
                    continue; // never counted → no variance
                }

                $variance = round((float) $line->counted_qty - (float) $line->system_qty, 2);
                if (abs($variance) < 0.005) {
                    continue;
                }

                $adjustmentLines[] = [
                    'part_id' => $line->part_id,
                    'direction' => $variance > 0 ? 'in' : 'out',
                    'qty' => abs($variance),
                    'unit_cost' => (float) $line->unit_cost,
                ];
            }

            if ($adjustmentLines !== []) {
                $adjustment = $this->movements->postAdjustment(
                    $take->branch_id,
                    'STOCKTAKE',
                    $adjustmentLines,
                    "Stock take {$take->take_number}",
                    $userId,
                );
                $take->adjustment_id = $adjustment->id;
            }

            $take->update([
                'status' => 'posted',
                'adjustment_id' => $take->adjustment_id,
                'posted_at' => now(),
            ]);

            return $take->fresh('lines');
        });
    }
}
