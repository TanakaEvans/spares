<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Services\StockMovementService;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SupplierReturnController extends Controller
{
    use ListQuery;

    private function returnsQuery(Request $request): Builder
    {
        return $this->listApply(
            SupplierReturn::with('supplier:id,name', 'branch:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s)),
            $request,
            [
                'searchable' => ['return_number', 'supplier_rma', 'credit_note_ref', 'supplier.name'],
                'sortable' => [
                    'return_number' => 'return_number', 'created_at' => 'created_at',
                    'reason' => 'reason', 'status' => 'status', 'shipped_at' => 'shipped_at',
                ],
                'dateColumn' => 'created_at',
                'defaultSort' => ['id' => 'desc'],
            ]
        );
    }

    /** The filtered returns list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->returnsQuery($request)->limit(5000)->get()->map(fn (SupplierReturn $r) => [
            $r->return_number, $r->supplier?->name, $r->branch?->name,
            ucwords(str_replace('_', ' ', $r->reason)),
            ucwords(str_replace('_', ' ', $r->status)),
            $r->supplier_rma ?? '—', $r->credit_note_ref ?? '—',
            number_format($r->totalValue(), 2, '.', ''),
            $r->credit_total !== null ? number_format((float) $r->credit_total, 2, '.', '') : '—',
        ]);

        return ListExport::respond($request, 'supplier-returns', 'Returns to Supplier',
            ['Return No', 'Supplier', 'Branch', 'Reason', 'Status', 'RMA', 'Credit ref', 'Stock value', 'Credited'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/Returns/Index', [
            'returns' => $this->returnsQuery($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SupplierReturn $r) => [
                    'id' => $r->id,
                    'return_number' => $r->return_number,
                    'supplier_id' => $r->supplier_id,
                    'supplier' => $r->supplier?->name,
                    'branch_id' => $r->branch_id,
                    'branch' => $r->branch?->name,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'supplier_rma' => $r->supplier_rma,
                    'value' => $r->totalValue(),
                    'credit_total' => $r->credit_total !== null ? (float) $r->credit_total : null,
                ]),
            'stats' => [
                'to_ship' => (int) SupplierReturn::where('status', 'draft')->count(),
                'awaiting_credit' => (int) SupplierReturn::where('status', 'shipped')->count(),
                'awaiting_value' => round((float) SupplierReturn::where('status', 'shipped')
                    ->get()->sum(fn ($r) => $r->totalValue()), 2),
            ],
            'filters' => $this->listFilters($request, ['status']),
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

    public function show(SupplierReturn $supplierReturn): Response
    {
        $supplierReturn->load([
            'supplier:id,name', 'branch:id,name', 'glJournal:id,journal_number',
            'lines.part:id,part_number,description',
        ]);

        return Inertia::render('Purchasing/Returns/Show', [
            'supplierReturn' => [
                'id' => $supplierReturn->id,
                'return_number' => $supplierReturn->return_number,
                'status' => $supplierReturn->status,
                'reason' => $supplierReturn->reason,
                'notes' => $supplierReturn->notes,
                'supplier_rma' => $supplierReturn->supplier_rma,
                'credit_note_ref' => $supplierReturn->credit_note_ref,
                'credit_total' => $supplierReturn->credit_total !== null ? (float) $supplierReturn->credit_total : null,
                'shipped_at' => $supplierReturn->shipped_at?->toDateTimeString(),
                'created_at' => $supplierReturn->created_at?->toDateString(),
                'supplier_id' => $supplierReturn->supplier_id,
                'supplier' => $supplierReturn->supplier?->name,
                'branch_id' => $supplierReturn->branch_id,
                'branch' => $supplierReturn->branch?->name,
                'gl_journal_id' => $supplierReturn->gl_journal_id,
                'journal_number' => $supplierReturn->glJournal?->journal_number,
                'value' => $supplierReturn->totalValue(),
                'lines' => $supplierReturn->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'part_id' => $l->part_id,
                    'part_number' => $l->part?->part_number,
                    'description' => $l->part?->description,
                    'qty' => (float) $l->qty,
                    'unit_cost' => (float) $l->unit_cost,
                    'condition' => $l->condition,
                    'line_total' => round((float) $l->qty * (float) $l->unit_cost, 2),
                ]),
            ],
        ]);
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
