<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\StockLedgerEntry;
use App\Models\StockLevel;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockLedgerService
{
    public const TYPES_IN = [
        'PURCHASE_RECEIPT', 'ADJUSTMENT_IN', 'TRANSFER_IN', 'OPENING_BALANCE', 'RETURN_IN',
    ];

    public const TYPES_OUT = [
        'SALE', 'ADJUSTMENT_OUT', 'TRANSFER_OUT', 'RETURN_OUT', 'JOB_CARD_OUT',
    ];

    public function __construct(private readonly SettingsService $settings)
    {
    }

    /**
     * Post a stock movement. The ONLY write path into stock.
     * Appends an immutable ledger entry and updates the stock_levels cache
     * (qty + AVCO) under a row lock, all inside one transaction.
     *
     * @param  float  $qty  positive quantity; direction comes from the type
     * @param  float|null  $unitCost  required for IN movements; ignored for OUT
     *                                (OUT always moves at current AVCO)
     */
    public function post(
        int $partId,
        Branch|int $branch,
        string $type,
        float $qty,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $userId = null,
    ): StockLedgerEntry {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be positive; the type determines direction.');
        }

        $isIn = in_array($type, self::TYPES_IN, true);
        $isOut = in_array($type, self::TYPES_OUT, true);

        if (! $isIn && ! $isOut) {
            throw new InvalidArgumentException("Unknown stock transaction type [{$type}].");
        }

        if ($isIn && $unitCost === null) {
            throw new InvalidArgumentException("Unit cost is required for {$type} movements.");
        }

        return DB::transaction(function () use ($partId, $branchId, $type, $qty, $unitCost, $referenceType, $referenceId, $notes, $userId, $isIn) {
            $level = StockLevel::lockForUpdate()
                ->firstOrCreate(
                    ['part_id' => $partId, 'branch_id' => $branchId],
                    ['qty_on_hand' => 0, 'average_cost' => 0]
                );

            $onHand = (float) $level->qty_on_hand;
            $avgCost = (float) $level->average_cost;

            if ($isIn) {
                $newOnHand = $onHand + $qty;
                // AVCO: weighted average of existing value + receipt value.
                $newAvg = $newOnHand > 0
                    ? round((($onHand * $avgCost) + ($qty * $unitCost)) / $newOnHand, 4)
                    : $avgCost;
                $movementCost = round($unitCost, 4);
                $signedQty = $qty;
            } else {
                $newOnHand = $onHand - $qty;

                if ($newOnHand < 0 && ! $this->settings->get('inventory.allow_negative_stock', $branchId)) {
                    throw new InsufficientStockException($partId, $branchId, $qty, $onHand);
                }

                $newAvg = $avgCost; // issues never change AVCO
                $movementCost = round($avgCost, 4);
                $signedQty = -$qty;
            }

            $entry = StockLedgerEntry::create([
                'part_id' => $partId,
                'branch_id' => $branchId,
                'transaction_type' => $type,
                'qty' => $signedQty,
                'unit_cost' => $movementCost,
                'running_balance' => $newOnHand,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            $level->update([
                'qty_on_hand' => $newOnHand,
                'average_cost' => $newAvg,
                'last_movement_at' => now(),
            ]);

            return $entry;
        });
    }

    /**
     * Reserve stock for an order (reduces availability without moving qty_on_hand).
     */
    public function reserve(int $partId, Branch|int $branch, float $qty): void
    {
        $this->adjustReservation($partId, $branch, $qty);
    }

    public function releaseReservation(int $partId, Branch|int $branch, float $qty): void
    {
        $this->adjustReservation($partId, $branch, -$qty);
    }

    /**
     * Integrity check: the levels cache must equal the ledger sum.
     * Returns mismatches; empty collection = healthy.
     */
    public function verifyIntegrity(?int $branchId = null): \Illuminate\Support\Collection
    {
        $levels = StockLevel::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();

        return $levels->map(function (StockLevel $level) {
            $ledgerSum = (float) StockLedgerEntry::where('part_id', $level->part_id)
                ->where('branch_id', $level->branch_id)
                ->sum('qty');

            return abs($ledgerSum - (float) $level->qty_on_hand) < 0.005 ? null : [
                'part_id' => $level->part_id,
                'branch_id' => $level->branch_id,
                'cached' => (float) $level->qty_on_hand,
                'ledger' => $ledgerSum,
            ];
        })->filter()->values();
    }

    private function adjustReservation(int $partId, Branch|int $branch, float $delta): void
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        DB::transaction(function () use ($partId, $branchId, $delta) {
            $level = StockLevel::lockForUpdate()
                ->firstOrCreate(
                    ['part_id' => $partId, 'branch_id' => $branchId],
                    ['qty_on_hand' => 0, 'average_cost' => 0]
                );

            $available = (float) $level->qty_on_hand - (float) $level->qty_reserved;

            if ($delta > 0 && $delta > $available) {
                throw new InsufficientStockException($partId, $branchId, $delta, $available);
            }

            $level->update([
                'qty_reserved' => max(0, (float) $level->qty_reserved + $delta),
            ]);
        });
    }
}
