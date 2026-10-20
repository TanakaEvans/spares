<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StockLevel;
use App\Support\ListExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StockLevelController extends Controller
{
    /** Stock-on-hand valuation as a CSV or PDF export (honours branch/search/reorder filters). */
    public function export(Request $request): HttpResponse
    {
        $rows = StockLevel::with(['branch:id,name', 'binLocation:id,code'])
            ->join('parts', 'parts.id', '=', 'stock_levels.part_id')
            ->select('stock_levels.*', 'parts.part_number', 'parts.description')
            ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($x) => $x->where('parts.part_number', 'like', "%{$s}%")
                    ->orWhere('parts.description', 'like', "%{$s}%")
            ))
            ->when($request->boolean('below_reorder'), fn ($q) => $q
                ->whereColumn('qty_on_hand', '<=', 'reorder_point')->where('reorder_point', '>', 0))
            ->orderBy('parts.part_number')->get()
            ->map(fn ($l) => [
                $l->part_number, $l->description, $l->branch?->name, $l->binLocation?->code,
                number_format((float) $l->qty_on_hand, 2, '.', ''),
                number_format((float) $l->qty_reserved, 2, '.', ''),
                number_format((float) $l->qty_on_hand - (float) $l->qty_reserved, 2, '.', ''),
                number_format((float) $l->average_cost, 2, '.', ''),
                number_format((float) $l->qty_on_hand * (float) $l->average_cost, 2, '.', ''),
                number_format((float) $l->reorder_point, 2, '.', ''),
            ]);

        return ListExport::respond($request, 'stock-on-hand', 'Stock on Hand', [
            'Part #', 'Description', 'Branch', 'Bin', 'On hand', 'Reserved', 'Available', 'Avg cost', 'Stock value', 'Reorder point',
        ], $rows);
    }

    public function index(Request $request): Response
    {
        $levels = StockLevel::with(['branch:id,name', 'binLocation:id,code'])
            ->join('parts', 'parts.id', '=', 'stock_levels.part_id')
            ->select('stock_levels.*', 'parts.part_number', 'parts.description')
            ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($x) => $x->where('parts.part_number', 'like', "%{$s}%")
                    ->orWhere('parts.description', 'like', "%{$s}%")
            ))
            ->when($request->boolean('below_reorder'), fn ($q) => $q
                ->whereColumn('qty_on_hand', '<=', 'reorder_point')
                ->where('reorder_point', '>', 0))
            ->orderBy('parts.part_number')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id,
                'part_id' => $l->part_id,
                'part_number' => $l->part_number,
                'description' => $l->description,
                'branch_id' => $l->branch_id,
                'branch' => $l->branch?->name,
                'bin_id' => $l->bin_location_id,
                'bin' => $l->binLocation?->code,
                'qty_on_hand' => (float) $l->qty_on_hand,
                'qty_reserved' => (float) $l->qty_reserved,
                'qty_available' => (float) $l->qty_on_hand - (float) $l->qty_reserved,
                'average_cost' => (float) $l->average_cost,
                'stock_value' => round((float) $l->qty_on_hand * (float) $l->average_cost, 2),
                'reorder_point' => (float) $l->reorder_point,
            ]);

        return Inertia::render('Inventory/Stock/Index', [
            'levels' => $levels,
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => $request->only('search', 'branch_id', 'below_reorder'),
            'totalValue' => round((float) StockLevel::selectRaw('SUM(qty_on_hand * average_cost) as v')->value('v'), 2),
        ]);
    }
}
