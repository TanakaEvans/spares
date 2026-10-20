<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ApprovedSupplier;
use App\Models\Branch;
use App\Models\GlJournal;
use App\Models\StockAdjustment;
use App\Models\StockLedgerEntry;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
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
                    'branch_id' => $a->branch_id,
                    'branch' => $a->branch?->name,
                    'reason_code' => $a->reason_code,
                    'status' => $a->status,
                    'posted_at' => $a->posted_at?->toDateTimeString(),
                    'lines' => $a->lines->map(fn ($l) => [
                        'part_id' => $l->part_id,
                        'part_number' => $l->part?->part_number,
                        'direction' => $l->direction,
                        'qty' => (float) $l->qty,
                    ])->values(),
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    /** Read-only detail of a posted adjustment: lines, the write-off journal and who posted it. */
    public function showAdjustment(StockAdjustment $adjustment): Response
    {
        $adjustment->load([
            'branch:id,name', 'createdBy:id,name', 'journal:id,journal_number,journal_date,status',
            'stockTake:id,take_number,adjustment_id', 'lines.part:id,part_number,description',
        ]);

        $lines = $adjustment->lines->map(fn ($l) => [
            'id' => $l->id,
            'part_id' => $l->part_id,
            'part_number' => $l->part?->part_number,
            'description' => $l->part?->description,
            'direction' => $l->direction,
            'qty' => (float) $l->qty,
            'unit_cost' => (float) $l->unit_cost,
            'line_value' => round((float) $l->qty * (float) $l->unit_cost, 2) * ($l->direction === 'out' ? -1 : 1),
        ])->values();

        return Inertia::render('Inventory/Adjustments/Show', [
            'adjustment' => [
                'id' => $adjustment->id,
                'adjustment_number' => $adjustment->adjustment_number,
                'reason_code' => $adjustment->reason_code,
                'status' => $adjustment->status,
                'notes' => $adjustment->notes,
                'posted_at' => $adjustment->posted_at?->toDateTimeString(),
                'created_at' => $adjustment->created_at?->toDateTimeString(),
                'branch_id' => $adjustment->branch_id,
                'branch' => $adjustment->branch?->name,
                'created_by_id' => $adjustment->created_by,
                'created_by' => $adjustment->createdBy?->name,
                'journal_id' => $adjustment->gl_journal_id,
                'journal_number' => $adjustment->journal?->journal_number,
                'stock_take_id' => $adjustment->stockTake?->id,
                'stock_take_number' => $adjustment->stockTake?->take_number,
                'lines' => $lines,
                'net_value' => round((float) $lines->sum('line_value'), 2),
            ],
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
                    'from_branch_id' => $t->from_branch_id,
                    'from' => $t->fromBranch?->name,
                    'to_branch_id' => $t->to_branch_id,
                    'to' => $t->toBranch?->name,
                    'status' => $t->status,
                    'lines' => $t->lines->map(fn ($l) => [
                        'part_id' => $l->part_id,
                        'part_number' => $l->part?->part_number,
                        'qty' => (float) $l->qty,
                    ])->values(),
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    /** Read-only detail of a transfer; dispatch/receive actors come from the ledger entries it wrote. */
    public function showTransfer(StockTransfer $transfer): Response
    {
        $transfer->load([
            'fromBranch:id,name', 'toBranch:id,name', 'createdBy:id,name',
            'lines.part:id,part_number,description',
        ]);

        // The ledger is the audit trail: TRANSFER_OUT = dispatched, TRANSFER_IN = received.
        $actors = StockLedgerEntry::query()
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->whereIn('transaction_type', ['TRANSFER_OUT', 'TRANSFER_IN'])
            ->get(['transaction_type', 'user_id'])
            ->groupBy('transaction_type')
            ->map(fn ($g) => $g->first()->user_id);
        $users = User::whereIn('id', $actors->filter()->values())->get(['id', 'name'])->keyBy('id');

        $journal = GlJournal::where('source_type', StockTransfer::class)
            ->where('source_id', $transfer->id)
            ->latest('id')
            ->first(['id', 'journal_number']);

        $lines = $transfer->lines->map(fn ($l) => [
            'id' => $l->id,
            'part_id' => $l->part_id,
            'part_number' => $l->part?->part_number,
            'description' => $l->part?->description,
            'qty' => (float) $l->qty,
            'unit_cost' => (float) $l->unit_cost,
            'line_value' => round((float) $l->qty * (float) $l->unit_cost, 2),
        ])->values();

        return Inertia::render('Inventory/Transfers/Show', [
            'transfer' => [
                'id' => $transfer->id,
                'transfer_number' => $transfer->transfer_number,
                'status' => $transfer->status,
                'notes' => $transfer->notes,
                'created_at' => $transfer->created_at?->toDateTimeString(),
                'dispatched_at' => $transfer->dispatched_at?->toDateTimeString(),
                'received_at' => $transfer->received_at?->toDateTimeString(),
                'from_branch_id' => $transfer->from_branch_id,
                'from' => $transfer->fromBranch?->name,
                'to_branch_id' => $transfer->to_branch_id,
                'to' => $transfer->toBranch?->name,
                'created_by_id' => $transfer->created_by,
                'created_by' => $transfer->createdBy?->name,
                'dispatched_by_id' => $actors->get('TRANSFER_OUT'),
                'dispatched_by' => $users->get($actors->get('TRANSFER_OUT'))?->name,
                'received_by_id' => $actors->get('TRANSFER_IN'),
                'received_by' => $users->get($actors->get('TRANSFER_IN'))?->name,
                'journal_id' => $journal?->id,
                'journal_number' => $journal?->journal_number,
                'lines' => $lines,
                'total_value' => round((float) $lines->sum('line_value'), 2),
            ],
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
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
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

        ApprovedSupplier::where('part_id', $data['part_id'])->update(['is_preferred' => false]);
        ApprovedSupplier::updateOrCreate(
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
