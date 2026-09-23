<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Services\ReorderService;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Adjustments, transfers and the reorder report (Modules 1.2/1.5).
 */
class StockMovementController extends Controller
{
    // ── Adjustments ──────────────────────────────────────────────────────

    public function adjustments(): Response
    {
        return Inertia::render('Inventory/Adjustments/Index', [
            'adjustments' => StockAdjustment::with('branch:id,name', 'lines.part:id,part_number')
                ->latest()
                ->paginate(25)
                ->through(fn (StockAdjustment $a) => [
                    'id' => $a->id,
                    'adjustment_number' => $a->adjustment_number,
                    'branch' => $a->branch?->name,
                    'reason_code' => $a->reason_code,
                    'status' => $a->status,
                    'posted_at' => $a->posted_at?->toDateTimeString(),
                    'lines' => $a->lines->map(fn ($l) => "{$l->part?->part_number} {$l->direction} {$l->qty}")->implode(', '),
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function storeAdjustment(Request $request, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'reason_code' => ['required', 'in:damage,write_off,correction,found,theft,expiry'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.part_id' => ['required', 'exists:parts,id'],
            'lines.*.direction' => ['required', 'in:in,out'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'gte:0', 'required_if:lines.*.direction,in'],
        ]);

        $adjustment = $service->postAdjustment(
            (int) $data['branch_id'],
            $data['reason_code'],
            $data['lines'],
            $data['notes'] ?? null,
            $request->user()->id,
        );

        return redirect()->route('inventory.adjustments.index')
            ->with('success', "Adjustment {$adjustment->adjustment_number} posted — stock and GL updated.");
    }

    // ── Transfers ────────────────────────────────────────────────────────

    public function transfers(): Response
    {
        return Inertia::render('Inventory/Transfers/Index', [
            'transfers' => StockTransfer::with('fromBranch:id,name', 'toBranch:id,name', 'lines.part:id,part_number')
                ->latest()
                ->paginate(25)
                ->through(fn (StockTransfer $t) => [
                    'id' => $t->id,
                    'transfer_number' => $t->transfer_number,
                    'from' => $t->fromBranch?->name,
                    'to' => $t->toBranch?->name,
                    'status' => $t->status,
                    'lines' => $t->lines->map(fn ($l) => "{$l->part?->part_number} ×{$l->qty}")->implode(', '),
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function storeTransfer(Request $request, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate([
            'from_branch_id' => ['required', 'exists:branches,id'],
            'to_branch_id' => ['required', 'exists:branches,id', 'different:from_branch_id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.part_id' => ['required', 'exists:parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $transfer = $service->createTransfer(
            (int) $data['from_branch_id'],
            (int) $data['to_branch_id'],
            $data['lines'],
            $data['notes'] ?? null,
            $request->user()->id,
        );

        return redirect()->route('inventory.transfers.index')
            ->with('success', "Transfer {$transfer->transfer_number} created — dispatch when the goods leave.");
    }

    public function dispatchTransfer(Request $request, StockTransfer $transfer, StockMovementService $service): RedirectResponse
    {
        $service->dispatchTransfer($transfer, $request->user()->id);

        return back()->with('success', "Transfer {$transfer->transfer_number} dispatched — source stock reduced.");
    }

    public function receiveTransfer(Request $request, StockTransfer $transfer, StockMovementService $service): RedirectResponse
    {
        $service->receiveTransfer($transfer, $request->user()->id);

        return back()->with('success', "Transfer {$transfer->transfer_number} received — destination stock updated.");
    }

    // ── Reorder ──────────────────────────────────────────────────────────

    public function reorder(Request $request, ReorderService $reorder): Response
    {
        $branchId = $request->integer('branch_id') ?: null;

        return Inertia::render('Inventory/Reorder/Index', [
            'rows' => $reorder->belowReorder($branchId),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'suppliers' => \App\Models\Supplier::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('branch_id'),
        ]);
    }

    public function createReorderPos(Request $request, ReorderService $reorder): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.part_id' => ['required', 'exists:parts,id'],
            'rows.*.qty' => ['required', 'numeric', 'gt:0'],
            'rows.*.supplier_id' => ['required', 'exists:suppliers,id'],
        ]);

        $pos = $reorder->createDraftPos((int) $data['branch_id'], $data['rows'], $request->user()->id);

        return redirect()->route('purchasing.orders.index')
            ->with('success', $pos->count().' draft purchase order(s) created: '.$pos->pluck('po_number')->implode(', '));
    }

    /** Set/replace the preferred supplier for a part (approved-suppliers list). */
    public function setPreferredSupplier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
        ]);

        \App\Models\ApprovedSupplier::where('part_id', $data['part_id'])->update(['is_preferred' => false]);
        \App\Models\ApprovedSupplier::updateOrCreate(
            ['part_id' => $data['part_id'], 'supplier_id' => $data['supplier_id']],
            ['is_preferred' => true]
        );

        return back()->with('success', 'Preferred supplier set.');
    }

    /** Set reorder point/qty inline from the reorder or stock screens. */
    public function updateReorderLevels(Request $request, StockLevel $level): RedirectResponse
    {
        $data = $request->validate([
            'reorder_point' => ['required', 'numeric', 'gte:0'],
            'reorder_qty' => ['required', 'numeric', 'gte:0'],
        ]);

        $level->update($data);

        return back()->with('success', 'Reorder levels updated.');
    }
}
