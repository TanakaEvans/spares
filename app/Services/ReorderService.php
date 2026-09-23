<?php

namespace App\Services;

use App\Models\ApprovedSupplier;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\SupplierPriceListItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Reorder management (Module 1.5): what is below its reorder point,
 * and one-click draft POs to each part's preferred supplier.
 */
class ReorderService
{
    public function __construct(private readonly NumberSequenceService $sequences)
    {
    }

    /** Parts at/below reorder point: available (on hand − reserved) ≤ reorder_point. */
    public function belowReorder(?int $branchId = null): Collection
    {
        return StockLevel::with(['part:id,part_number,description', 'branch:id,name'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('reorder_point', '>', 0)
            ->whereRaw('(qty_on_hand - qty_reserved) <= reorder_point')
            ->get()
            ->map(function (StockLevel $level) {
                $preferred = ApprovedSupplier::with('supplier:id,name')
                    ->where('part_id', $level->part_id)
                    ->orderByDesc('is_preferred')
                    ->first();

                $available = (float) $level->qty_on_hand - (float) $level->qty_reserved;
                $suggested = (float) $level->reorder_qty > 0
                    ? (float) $level->reorder_qty
                    : max((float) $level->reorder_point * 2 - $available, 1);

                return [
                    'stock_level_id' => $level->id,
                    'part_id' => $level->part_id,
                    'part_number' => $level->part->part_number,
                    'description' => $level->part->description,
                    'branch_id' => $level->branch_id,
                    'branch' => $level->branch->name,
                    'available' => $available,
                    'reorder_point' => (float) $level->reorder_point,
                    'suggested_qty' => round($suggested, 2),
                    'preferred_supplier_id' => $preferred?->supplier_id,
                    'preferred_supplier' => $preferred?->supplier?->name,
                ];
            });
    }

    /**
     * Create draft POs from below-reorder rows, grouped by preferred supplier.
     * Cost: active supplier price list → fallback current AVCO.
     *
     * @param  array  $rows  [['part_id'=>, 'qty'=>, 'supplier_id'=>], …]
     * @return Collection<PurchaseOrder>
     */
    public function createDraftPos(int $branchId, array $rows, ?int $userId = null): Collection
    {
        $bySupplier = collect($rows)->groupBy('supplier_id');

        if ($bySupplier->has('') || $bySupplier->has(null)) {
            throw new InvalidArgumentException('Every line needs a supplier — set an approved supplier for parts without one.');
        }

        return DB::transaction(function () use ($bySupplier, $branchId, $userId) {
            return $bySupplier->map(function (Collection $lines, $supplierId) use ($branchId, $userId) {
                $po = PurchaseOrder::create([
                    'po_number' => $this->sequences->next('purchase_order', $branchId),
                    'supplier_id' => (int) $supplierId,
                    'branch_id' => $branchId,
                    'buyer_id' => $userId,
                    'status' => 'draft',
                    'order_date' => now()->toDateString(),
                    'notes' => 'Auto-created from reorder report',
                ]);

                $subtotal = 0.0;

                foreach ($lines as $line) {
                    $cost = $this->bestCost((int) $line['part_id'], (int) $supplierId, $branchId);
                    $qty = (float) $line['qty'];
                    $part = \App\Models\Part::findOrFail($line['part_id']);

                    $po->lines()->create([
                        'part_id' => $part->id,
                        'description' => $part->description,
                        'qty_ordered' => $qty,
                        'unit_cost' => $cost,
                        'line_total' => round($qty * $cost, 2),
                    ]);

                    $subtotal += round($qty * $cost, 2);
                }

                $po->update(['subtotal' => $subtotal, 'total' => $subtotal]);

                return $po->fresh('lines');
            })->values();
        });
    }

    private function bestCost(int $partId, int $supplierId, int $branchId): float
    {
        $priceListCost = SupplierPriceListItem::where('part_id', $partId)
            ->whereHas('priceList', fn ($q) => $q->where('supplier_id', $supplierId)->where('status', 'active'))
            ->value('cost_price');

        if ($priceListCost !== null) {
            return (float) $priceListCost;
        }

        return (float) (StockLevel::where('part_id', $partId)
            ->where('branch_id', $branchId)->value('average_cost') ?? 0);
    }
}
