<?php

namespace App\Services;

use App\Models\StockLevel;
use Illuminate\Support\Facades\DB;

/**
 * Go-live data migration (operations/data-migration.md): bring opening balances
 * that were loaded into the sub-ledgers (e.g. opening stock via
 * StockLedgerService) onto the GL, so control accounts reconcile from day one.
 * The contra is Opening Balance Equity (3300).
 */
class OpeningBalanceService
{
    public function __construct(private readonly GlPostingService $gl)
    {
    }

    /** Current physical stock value at AVCO. */
    public function stockValue(?int $branchId = null): float
    {
        return round(StockLevel::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()->sum(fn (StockLevel $s) => (float) $s->qty_on_hand * (float) $s->average_cost), 2);
    }

    /** The un-journalised portion of inventory = stock value − current 1310 balance. */
    public function inventoryGlGap(): float
    {
        return round($this->stockValue() - $this->gl->accountBalance('1310'), 2);
    }

    /**
     * Post the opening inventory journal so GL 1310 equals physical stock value.
     * DR/CR Inventory (1310) ↔ Opening Balance Equity (3300). Idempotent-safe:
     * only posts when there's a gap to close.
     */
    public function postOpeningInventory(?int $userId = null): ?\App\Models\GlJournal
    {
        $gap = $this->inventoryGlGap();
        if (abs($gap) < 0.01) {
            return null;
        }

        return DB::transaction(function () use ($gap, $userId) {
            $lines = $gap > 0
                ? [
                    ['account' => '1310', 'debit' => $gap, 'credit' => 0, 'description' => 'Opening inventory'],
                    ['account' => '3300', 'debit' => 0, 'credit' => $gap, 'description' => 'Opening balance equity'],
                ]
                : [
                    ['account' => '3300', 'debit' => abs($gap), 'credit' => 0, 'description' => 'Opening balance equity'],
                    ['account' => '1310', 'debit' => 0, 'credit' => abs($gap), 'description' => 'Opening inventory'],
                ];

            return $this->gl->post(
                journalType: 'opening',
                date: now(),
                description: 'Opening inventory balance (migration)',
                lines: $lines,
                reference: 'OPENING-STOCK',
                userId: $userId,
                fromSubLedger: true, // touches the inventory control account
            );
        });
    }
}
