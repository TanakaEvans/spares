<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\ApprovedSupplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovedSupplierController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Approved/Index', [
            'rows' => $this->listApply(
                ApprovedSupplier::with('part:id,part_number,description', 'supplier:id,name')
                    ->when($request->string('preferred')->toString() === '1', fn ($q) => $q->where('is_preferred', true)),
                $request,
                [
                    'searchable' => ['part.part_number', 'part.description', 'supplier.name', 'supplier_part_number'],
                    'sortable' => [
                        'is_preferred' => 'is_preferred', 'lead_time_days' => 'lead_time_days',
                        'created_at' => 'created_at',
                    ],
                    'defaultSort' => ['is_preferred' => 'desc', 'id' => 'asc'],
                ]
            )->paginate(30)
                ->withQueryString()
                ->through(fn (ApprovedSupplier $a) => [
                    'id' => $a->id,
                    'part_id' => $a->part_id,
                    'part_number' => $a->part?->part_number,
                    'description' => $a->part?->description,
                    'supplier_id' => $a->supplier_id,
                    'supplier' => $a->supplier?->name,
                    'supplier_part_number' => $a->supplier_part_number,
                    'is_preferred' => $a->is_preferred,
                    'lead_time_days' => $a->lead_time_days,
                ]),
            'filters' => $this->listFilters($request, ['preferred']),
        ]);
    }
}
