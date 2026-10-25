<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\SupplierReturn;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Supplier credit notes (Module 3.5) — the credits captured against returns to
 * supplier. Every credited return is a supplier credit note that reduced AP.
 */
class SupplierCreditController extends Controller
{
    use ListQuery;

    private function creditsQuery(Request $request): Builder
    {
        return $this->listApply(
            SupplierReturn::query()
                ->where('status', 'credited')
                ->with('supplier:id,name', 'branch:id,name'),
            $request,
            [
                'searchable' => ['return_number', 'credit_note_ref', 'supplier_rma', 'supplier.name'],
                'sortable' => [
                    'return_number' => 'return_number', 'credit_note_ref' => 'credit_note_ref',
                    'credit_total' => 'credit_total', 'updated_at' => 'updated_at',
                ],
                'dateColumn' => 'updated_at',
                'defaultSort' => ['id' => 'desc'],
            ]
        );
    }

    /** The filtered credits list as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->creditsQuery($request)->limit(5000)->get()->map(fn (SupplierReturn $r) => [
            $r->credit_note_ref, $r->return_number, $r->supplier?->name, $r->branch?->name,
            ucwords(str_replace('_', ' ', $r->reason)),
            number_format((float) $r->credit_total, 2, '.', ''),
            $r->updated_at?->toDateString(),
        ]);

        return ListExport::respond($request, 'supplier-credits', 'Supplier Credit Notes',
            ['Credit ref', 'Return No', 'Supplier', 'Branch', 'Reason', 'Amount', 'Captured'], $rows);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/SupplierCredits/Index', [
            'credits' => $this->creditsQuery($request)
                ->paginate(25)
                ->withQueryString()
                ->through(fn (SupplierReturn $r) => [
                    'id' => $r->id,
                    'return_number' => $r->return_number,
                    'credit_note_ref' => $r->credit_note_ref,
                    'supplier_id' => $r->supplier_id,
                    'supplier' => $r->supplier?->name,
                    'branch' => $r->branch?->name,
                    'reason' => $r->reason,
                    'credit_total' => (float) $r->credit_total,
                ]),
            'stats' => [
                'count' => (int) SupplierReturn::where('status', 'credited')->count(),
                'total' => round((float) SupplierReturn::where('status', 'credited')->sum('credit_total'), 2),
            ],
            'filters' => $this->listFilters($request),
        ]);
    }
}
