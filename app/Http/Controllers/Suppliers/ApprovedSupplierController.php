<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\ApprovedSupplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovedSupplierController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Approved/Index', [
            'rows' => ApprovedSupplier::with('part:id,part_number,description', 'supplier:id,name')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->whereHas(
                    'part', fn ($x) => $x->where('part_number', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")
                ))
                ->orderByDesc('is_preferred')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (ApprovedSupplier $a) => [
                    'id' => $a->id,
                    'part_id' => $a->part_id,
                    'part_number' => $a->part?->part_number,
                    'description' => $a->part?->description,
                    'supplier_id' => $a->supplier_id,
                    'supplier' => $a->supplier?->name,
                    'is_preferred' => $a->is_preferred,
                    'lead_time_days' => $a->lead_time_days,
                ]),
            'filters' => $request->only('search'),
        ]);
    }
}
