<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\BinLocation;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BinLocationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Bins/Index', [
            'bins' => BinLocation::with('branch:id,name')
                ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                ->orderBy('code')
                ->paginate(50)
                ->withQueryString(),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => $request->only('branch_id'),
        ]);
    }

    /** Everything stored in one bin: every stock level pointing at it, with value at AVCO. */
    public function show(BinLocation $bin): Response
    {
        $bin->load('branch:id,name,code');

        $levels = $bin->stockLevels()
            ->with('part:id,part_number,description')
            ->get()
            ->sortBy(fn ($l) => $l->part?->part_number)
            ->values()
            ->map(fn ($l) => [
                'id' => $l->id,
                'part_id' => $l->part_id,
                'part_number' => $l->part?->part_number,
                'description' => $l->part?->description,
                'qty_on_hand' => (float) $l->qty_on_hand,
                'qty_reserved' => (float) $l->qty_reserved,
                'qty_available' => (float) $l->qty_on_hand - (float) $l->qty_reserved,
                'average_cost' => (float) $l->average_cost,
                'stock_value' => round((float) $l->qty_on_hand * (float) $l->average_cost, 2),
                'last_movement_at' => $l->last_movement_at?->toDateTimeString(),
            ]);

        return Inertia::render('Inventory/Bins/Show', [
            'bin' => [
                'id' => $bin->id,
                'code' => $bin->code,
                'notes' => $bin->notes,
                'is_active' => $bin->is_active,
                'branch_id' => $bin->branch_id,
                'branch' => $bin->branch?->name,
                'branch_code' => $bin->branch?->code,
            ],
            'levels' => $levels,
            'totals' => [
                'parts' => $levels->count(),
                'qty_on_hand' => (float) $levels->sum('qty_on_hand'),
                'stock_value' => round((float) $levels->sum('stock_value'), 2),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = strtoupper($data['code']);

        $exists = BinLocation::where('branch_id', $data['branch_id'])
            ->where('code', $data['code'])->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'That bin code already exists at this branch.']);
        }

        BinLocation::create($data);

        return back()->with('success', "Bin {$data['code']} added.");
    }
}
