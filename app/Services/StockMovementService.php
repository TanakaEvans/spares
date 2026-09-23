<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\SupplierReturn;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Adjustments (1.2), inter-branch transfers (1.2) and supplier returns
 * (3.6/3.5) — every path runs through StockLedgerService + GlPostingService.
 */
class StockMovementService
{
    public function __construct(
        private readonly StockLedgerService $stock,
        private readonly GlPostingService $gl,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    // ── Adjustments ──────────────────────────────────────────────────────

    /**
     * @param array $lines [['part_id'=>, 'direction'=>'in|out', 'qty'=>, 'unit_cost'=>?], …]
     */
    public function postAdjustment(
        int $branchId,
        string $reasonCode,
        array $lines,
        ?string $notes = null,
        ?int $userId = null,
    ): StockAdjustment {
        if ($lines === []) {
            throw new InvalidArgumentException('An adjustment needs at least one line.');
        }

        return DB::transaction(function () use ($branchId, $reasonCode, $lines, $notes, $userId) {
            $adjustment = StockAdjustment::create([
                'adjustment_number' => $this->sequences->next('stock_adjustment', $branchId),
                'branch_id' => $branchId,
                'reason_code' => $reasonCode,
                'status' => 'posted',
                'created_by' => $userId,
                'notes' => $notes,
                'posted_at' => now(),
            ]);

            $valueIn = 0.0;
            $valueOut = 0.0;

            foreach ($lines as $line) {
                $direction = $line['direction'];
                $qty = (float) $line['qty'];

                $entry = $this->stock->post(
                    (int) $line['part_id'],
                    $branchId,
                    $direction === 'in' ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT',
                    $qty,
                    $direction === 'in' ? (float) ($line['unit_cost'] ?? 0) : null,
                    StockAdjustment::class,
                    $adjustment->id,
                    $reasonCode,
                    $userId,
                );

                $lineValue = round($qty * (float) $entry->unit_cost, 2);
                $direction === 'in' ? $valueIn += $lineValue : $valueOut += $lineValue;

                $adjustment->lines()->create([
                    'part_id' => $line['part_id'],
                    'direction' => $direction,
                    'qty' => $qty,
                    'unit_cost' => (float) $entry->unit_cost,
                ]);
            }

            // Shortage: DR 5300 Write-offs / CR 1310 Inventory. Surplus: opposite.
            $net = round($valueIn - $valueOut, 2);
            if (abs($net) >= 0.01) {
                $journal = $this->gl->post(
                    journalType: 'adjustment',
                    date: now(),
                    description: "Stock adjustment {$adjustment->adjustment_number} ({$reasonCode})",
                    lines: $net < 0 ? [
                        ['account' => '5300', 'debit' => abs($net), 'credit' => 0],
                        ['account' => '1310', 'debit' => 0, 'credit' => abs($net)],
                    ] : [
                        ['account' => '1310', 'debit' => $net, 'credit' => 0],
                        ['account' => '5300', 'debit' => 0, 'credit' => $net],
                    ],
                    reference: $adjustment->adjustment_number,
                    sourceType: StockAdjustment::class,
                    sourceId: $adjustment->id,
                    branch: $branchId,
                    userId: $userId,
                    fromSubLedger: true,
                );
                $adjustment->update(['gl_journal_id' => $journal->id]);
            }

            return $adjustment->fresh('lines');
        });
    }

    // ── Transfers ────────────────────────────────────────────────────────

    /** @param array $lines [['part_id'=>, 'qty'=>], …] */
    public function createTransfer(int $fromBranchId, int $toBranchId, array $lines, ?string $notes = null, ?int $userId = null): StockTransfer
    {
        if ($fromBranchId === $toBranchId) {
            throw new InvalidArgumentException('Source and destination branch must differ.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('A transfer needs at least one line.');
        }

        return DB::transaction(function () use ($fromBranchId, $toBranchId, $lines, $notes, $userId) {
            $transfer = StockTransfer::create([
                'transfer_number' => $this->sequences->next('stock_transfer', $fromBranchId),
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'status' => 'draft',
                'created_by' => $userId,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $transfer->lines()->create([
                    'part_id' => $line['part_id'],
                    'qty' => (float) $line['qty'],
                ]);
            }

            return $transfer->fresh('lines');
        });
    }

    public function dispatchTransfer(StockTransfer $transfer, ?int $userId = null): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new InvalidArgumentException("Transfer {$transfer->transfer_number} is {$transfer->status}.");
        }

        return DB::transaction(function () use ($transfer, $userId) {
            foreach ($transfer->lines as $line) {
                $entry = $this->stock->post(
                    $line->part_id, $transfer->from_branch_id, 'TRANSFER_OUT',
                    (float) $line->qty, null,
                    StockTransfer::class, $transfer->id, null, $userId,
                );
                // Value moves at the SOURCE branch's AVCO.
                $line->update(['unit_cost' => (float) $entry->unit_cost]);
            }

            $transfer->update(['status' => 'dispatched', 'dispatched_at' => now()]);

            return $transfer;
        });
    }

    public function receiveTransfer(StockTransfer $transfer, ?int $userId = null): StockTransfer
    {
        if ($transfer->status !== 'dispatched') {
            throw new InvalidArgumentException("Transfer {$transfer->transfer_number} is not in transit.");
        }

        return DB::transaction(function () use ($transfer, $userId) {
            foreach ($transfer->lines as $line) {
                $this->stock->post(
                    $line->part_id, $transfer->to_branch_id, 'TRANSFER_IN',
                    (float) $line->qty, (float) $line->unit_cost,
                    StockTransfer::class, $transfer->id, null, $userId,
                );
            }

            $transfer->update(['status' => 'received', 'received_at' => now()]);

            return $transfer;
        });
    }

    // ── Supplier returns ─────────────────────────────────────────────────

    /** @param array $lines [['part_id'=>, 'qty'=>, 'condition'=>], …] */
    public function createSupplierReturn(int $supplierId, int $branchId, string $reason, array $lines, ?string $notes = null, ?int $userId = null): SupplierReturn
    {
        if ($lines === []) {
            throw new InvalidArgumentException('A return needs at least one line.');
        }

        return DB::transaction(function () use ($supplierId, $branchId, $reason, $lines, $notes) {
            $return = SupplierReturn::create([
                'return_number' => $this->sequences->next('supplier_return', $branchId),
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'reason' => $reason,
                'status' => 'draft',
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $avco = (float) (StockLevel::where('part_id', $line['part_id'])
                    ->where('branch_id', $branchId)->value('average_cost') ?? 0);

                $return->lines()->create([
                    'part_id' => $line['part_id'],
                    'qty' => (float) $line['qty'],
                    'unit_cost' => $avco,
                    'condition' => $line['condition'] ?? 'damaged',
                ]);
            }

            return $return->fresh('lines');
        });
    }

    /** Ship requires the supplier's RMA — Module 3.6 rule 3. */
    public function shipSupplierReturn(SupplierReturn $return, string $rma, ?int $userId = null): SupplierReturn
    {
        if ($return->status !== 'draft') {
            throw new InvalidArgumentException("Return {$return->return_number} is {$return->status}.");
        }
        if (trim($rma) === '') {
            throw new InvalidArgumentException('A supplier RMA number is required before shipping.');
        }

        return DB::transaction(function () use ($return, $rma, $userId) {
            foreach ($return->lines as $line) {
                $entry = $this->stock->post(
                    $line->part_id, $return->branch_id, 'RETURN_OUT',
                    (float) $line->qty, null,
                    SupplierReturn::class, $return->id, $return->reason, $userId,
                );
                $line->update(['unit_cost' => (float) $entry->unit_cost]);
            }

            $return->update(['status' => 'shipped', 'supplier_rma' => $rma, 'shipped_at' => now()]);

            return $return;
        });
    }

    /** Capture the supplier's credit note: DR Creditors / CR Inventory (variance → 5300). */
    public function creditSupplierReturn(SupplierReturn $return, string $creditRef, float $creditTotal, ?int $userId = null): SupplierReturn
    {
        if ($return->status !== 'shipped') {
            throw new InvalidArgumentException('Only shipped returns can be credited.');
        }

        return DB::transaction(function () use ($return, $creditRef, $creditTotal, $userId) {
            $stockValue = $return->totalValue();
            $variance = round($creditTotal - $stockValue, 2);

            $lines = [
                ['account' => '2110', 'debit' => $creditTotal, 'credit' => 0, 'description' => 'Supplier credit note'],
                ['account' => '1310', 'debit' => 0, 'credit' => $stockValue, 'description' => 'Stock returned'],
            ];
            if ($variance > 0) {
                $lines[] = ['account' => '5300', 'debit' => 0, 'credit' => $variance, 'description' => 'Credit above stock value'];
            } elseif ($variance < 0) {
                $lines[] = ['account' => '5300', 'debit' => abs($variance), 'credit' => 0, 'description' => 'Credit below stock value'];
            }

            $journal = $this->gl->post(
                journalType: 'purchase',
                date: now(),
                description: "Supplier credit {$creditRef} — return {$return->return_number}",
                lines: $lines,
                reference: $return->return_number,
                sourceType: SupplierReturn::class,
                sourceId: $return->id,
                branch: $return->branch_id,
                userId: $userId,
                fromSubLedger: true,
            );

            $return->update([
                'status' => 'credited',
                'credit_note_ref' => $creditRef,
                'credit_total' => $creditTotal,
                'gl_journal_id' => $journal->id,
            ]);

            return $return;
        });
    }
}
