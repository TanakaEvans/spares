<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\SupplierReturn;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supplier credit notes (Module 3.5) — the credits captured against returns to
 * supplier. Every credited return is a supplier credit note that reduced AP.
 */
class SupplierCreditController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Purchasing/SupplierCredits/Index', [
            'credits' => SupplierReturn::query()
                ->where('status', 'credited')
                ->with('supplier:id,name', 'branch:id,name')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('return_number', 'like', "%{$s}%")
                        ->orWhere('credit_note_ref', 'like', "%{$s}%")
                        ->orWhereHas('supplier', fn ($y) => $y->where('name', 'like', "%{$s}%"))
                ))
                ->latest('id')
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
            'filters' => $request->only('search'),
        ]);
    }
}
