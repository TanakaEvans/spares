<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Part;
use App\Models\StockTake;
use App\Models\StockTakeLine;
use App\Services\SettingsService;
use App\Services\StockTakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTakeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/StockTakes/Index', [
            'takes' => StockTake::with('branch:id,name', 'startedBy:id,name')
                ->withCount('lines')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (StockTake $t) => [
                    'id' => $t->id,
                    'take_number' => $t->take_number,
                    'branch' => $t->branch?->name,
                    'type' => $t->type,
                    'status' => $t->status,
                    'lines_count' => $t->lines_count,
                    'started_by' => $t->startedBy?->name,
                    'created_at' => $t->created_at->toDateString(),
                ]),
            'filters' => $request->only('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/StockTakes/Create', [
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, StockTakeService $service): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'type' => ['required', 'in:full,spot'],
            'part_ids' => ['nullable', 'array'],
            'part_ids.*' => ['integer', 'exists:parts,id'],
        ]);

        try {
            $take = $service->start(
                $data['branch_id'], $data['type'], $data['part_ids'] ?? [], $request->user()->id
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['type' => $e->getMessage()]);
        }

        if ($take->lines->isEmpty()) {
            return back()->withErrors(['branch_id' => 'No stock at this branch to count.']);
        }

        return redirect()->route('inventory.stock-takes.show', $take)
            ->with('success', "Stock take {$take->take_number} started — {$take->lines->count()} lines to count.");
    }

    public function show(StockTake $stockTake, SettingsService $settings): Response
    {
        $stockTake->load('branch:id,name', 'lines.part:id,part_number,description');
        $recountValue = (float) $settings->get('inventory.stock_take_variance_recount_value', $stockTake->branch_id);

        return Inertia::render('Inventory/StockTakes/Show', [
            'take' => [
                'id' => $stockTake->id,
                'take_number' => $stockTake->take_number,
                'branch' => $stockTake->branch?->name,
                'type' => $stockTake->type,
                'status' => $stockTake->status,
                'adjustment_id' => $stockTake->adjustment_id,
                'lines' => $stockTake->lines->map(fn (StockTakeLine $l) => [
                    'id' => $l->id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->part?->description,
                    'system_qty' => (float) $l->system_qty,
                    'counted_qty' => $l->counted_qty !== null ? (float) $l->counted_qty : null,
                    'unit_cost' => (float) $l->unit_cost,
                    'variance' => $l->counted_qty !== null ? round((float) $l->counted_qty - (float) $l->system_qty, 2) : null,
                    'variance_value' => $l->counted_qty !== null
                        ? round(((float) $l->counted_qty - (float) $l->system_qty) * (float) $l->unit_cost, 2)
                        : null,
                ]),
            ],
            'recountValue' => $recountValue,
        ]);
    }

    public function recordCount(Request $request, StockTake $stockTake, StockTakeLine $line, StockTakeService $service): RedirectResponse
    {
        abort_unless($line->take_id === $stockTake->id, 404);
        $data = $request->validate(['counted_qty' => ['required', 'numeric', 'min:0']]);

        try {
            $service->recordCount($line, $data['counted_qty']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['counted_qty' => $e->getMessage()]);
        }

        return back();
    }

    public function review(StockTake $stockTake, StockTakeService $service): RedirectResponse
    {
        try {
            $service->moveToReview($stockTake);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Counts locked — review the variance before posting.');
    }

    public function post(Request $request, StockTake $stockTake, StockTakeService $service): RedirectResponse
    {
        try {
            $service->post($stockTake, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('inventory.stock-takes.show', $stockTake)
            ->with('success', "Stock take {$stockTake->take_number} posted — the ledger now matches the count.");
    }
}
