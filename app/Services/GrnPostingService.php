<?php

namespace App\Services;

use App\Exceptions\OverReceiptException;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Receiving engine (Module 3.3, procure-to-pay workflow):
 * posts a draft GRN — stock in at PO cost (AVCO recalculates),
 * putaway bins, PO line/status updates, and the GL accrual
 * DR 1310 Inventory / CR 2120 GRN Accruals. Irreversible once posted.
 */
class GrnPostingService
{
    public function __construct(
        private readonly StockLedgerService $stock,
        private readonly GlPostingService $gl,
        private readonly NumberSequenceService $sequences,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * @param array $lines [['po_line_id'=>, 'qty_received'=>, 'qty_rejected'=>0,
     *                      'rejection_reason'=>null, 'bin_location_id'=>null], …]
     */
    public function receive(
        PurchaseOrder $po,
        array $lines,
        ?string $deliveryNoteNumber = null,
        ?int $userId = null,
        bool $overReceiptApproved = false,
        ?string $notes = null,
    ): GoodsReceivedNote {
        if (! $po->isReceivable()) {
            throw new InvalidArgumentException("PO {$po->po_number} is {$po->status} and cannot be received against.");
        }

        $poLines = $po->lines()->get()->keyBy('id');
        $tolerance = (float) $this->settings->get('purchasing.grn_over_receive_tolerance_pct');

        // Validate every line BEFORE writing anything.
        foreach ($lines as $line) {
            $poLine = $poLines[$line['po_line_id']] ?? null;
            if ($poLine === null) {
                throw new InvalidArgumentException('GRN line references a line not on this PO.');
            }

            $qty = (float) ($line['qty_received'] ?? 0);
            $rejected = (float) ($line['qty_rejected'] ?? 0);

            if ($rejected > 0 && trim((string) ($line['rejection_reason'] ?? '')) === '') {
                throw new InvalidArgumentException(
                    "A rejection reason is required for {$poLine->part->part_number} ({$rejected} rejected)."
                );
            }

            if ($qty < 0 || ($qty === 0.0 && $rejected === 0.0)) {
                continue;
            }

            $newTotal = (float) $poLine->qty_received + $qty;
            $allowed = (float) $poLine->qty_ordered * (1 + $tolerance / 100);

            if ($newTotal > $allowed + 0.001 && ! $overReceiptApproved) {
                throw new OverReceiptException(
                    $poLine->part->part_number,
                    (float) $poLine->qty_ordered,
                    $newTotal,
                    $tolerance
                );
            }
        }

        return DB::transaction(function () use ($po, $lines, $poLines, $deliveryNoteNumber, $userId, $notes) {
            $grn = GoodsReceivedNote::create([
                'grn_number' => $this->sequences->next('grn', $po->branch_id),
                'po_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'branch_id' => $po->branch_id,
                'received_by' => $userId,
                'received_date' => now()->toDateString(),
                'status' => 'posted',
                'delivery_note_number' => $deliveryNoteNumber,
                'notes' => $notes,
                'posted_at' => now(),
            ]);

            $acceptedValue = 0.0;

            foreach ($lines as $line) {
                $poLine = $poLines[$line['po_line_id']];
                $qty = (float) ($line['qty_received'] ?? 0);
                $rejected = (float) ($line['qty_rejected'] ?? 0);

                if ($qty <= 0 && $rejected <= 0) {
                    continue;
                }

                $grn->lines()->create([
                    'po_line_id' => $poLine->id,
                    'part_id' => $poLine->part_id,
                    'qty_received' => $qty,
                    'qty_rejected' => $rejected,
                    'rejection_reason' => $line['rejection_reason'] ?? null,
                    'unit_cost' => $poLine->unit_cost,
                    'bin_location_id' => $line['bin_location_id'] ?? null,
                ]);

                if ($qty > 0) {
                    // Accepted stock in — rejected qty NEVER enters stock.
                    $this->stock->post(
                        $poLine->part_id,
                        $po->branch_id,
                        'PURCHASE_RECEIPT',
                        $qty,
                        (float) $poLine->unit_cost,
                        'GoodsReceivedNote',
                        $grn->id,
                        null,
                        $userId,
                    );

                    if (! empty($line['bin_location_id'])) {
                        StockLevel::where('part_id', $poLine->part_id)
                            ->where('branch_id', $po->branch_id)
                            ->whereNull('bin_location_id')
                            ->update(['bin_location_id' => $line['bin_location_id']]);
                    }

                    $poLine->update(['qty_received' => (float) $poLine->qty_received + $qty]);
                    $acceptedValue += round($qty * (float) $poLine->unit_cost, 2);
                }
            }

            if ($acceptedValue > 0) {
                $journal = $this->gl->post(
                    journalType: 'purchase',
                    date: now(),
                    description: "GRN {$grn->grn_number} — {$po->supplier->name}",
                    lines: [
                        ['account' => '1310', 'debit' => $acceptedValue, 'credit' => 0, 'description' => 'Stock received'],
                        ['account' => '2120', 'debit' => 0, 'credit' => $acceptedValue, 'description' => 'Received not invoiced'],
                    ],
                    reference: $grn->grn_number,
                    sourceType: GoodsReceivedNote::class,
                    sourceId: $grn->id,
                    branch: $po->branch_id,
                    userId: $userId,
                    fromSubLedger: true,
                );

                $grn->update(['gl_journal_id' => $journal->id]);
            }

            $po->refreshReceivingStatus();

            return $grn->fresh('lines');
        });
    }
}
