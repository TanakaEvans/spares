<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Services\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierReturnController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Purchasing/Returns/Index', [
            'returns' => SupplierReturn::with('supplier:id,name', 'branch:id,name')
                ->latest()
                ->paginate(25)
                ->through(fn (SupplierReturn $r) => [
                    'id' => $r->id,
                    'return_number' => $r->return_number,
                    'supplier' => $r->supplier?->name,
                    'branch' => $r->branch?->name,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'supplier_rma' => $r->supplier_rma,
                    'value' => $r->totalValue(),
                    'credit_total' => $r->credit_total !== null ? (float) $r->credit_total : null,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchasing/Returns/Create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'reason' => ['required', 'in:damaged,incorrect_part,excess_stock,warranty'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.part_id' => ['required', 'exists:parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.condition' => ['required', 'in:new,damaged,used'],
        ]);

        $return = $service->createSupplierReturn(
            (int) $data['supplier_id'],
            (int) $data['branch_id'],
            $data['reason'],
            $data['lines'],
            $data['notes'] ?? null,
            $request->user()->id,
        );

        return redirect()->route('purchasing.returns.index')
            ->with('success', "Return {$return->return_number} created — request an RMA from the supplier, then ship.");
    }

    public function ship(Request $request, SupplierReturn $return, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate(['supplier_rma' => ['required', 'string', 'max:100']]);

        $service->shipSupplierReturn($return, $data['supplier_rma'], $request->user()->id);

        return back()->with('success', "Return {$return->return_number} shipped — stock reduced, awaiting supplier credit.");
    }

    public function credit(Request $request, SupplierReturn $return, StockMovementService $service): RedirectResponse
    {
        $data = $request->validate([
            'credit_note_ref' => ['required', 'string', 'max:100'],
            'credit_total' => ['required', 'numeric', 'gt:0'],
        ]);

        $service->creditSupplierReturn($return, $data['credit_note_ref'], (float) $data['credit_total'], $request->user()->id);

        return back()->with('success', "Credit {$data['credit_note_ref']} captured — creditors reduced.");
    }
}
