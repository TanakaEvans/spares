<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\SupplierContact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierContactController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Contacts/Index', [
            'contacts' => $this->listApply(
                SupplierContact::with('supplier:id,name'),
                $request,
                [
                    'searchable' => ['name', 'email', 'phone', 'position', 'supplier.name'],
                    'sortable' => [
                        'name' => 'name', 'email' => 'email',
                        'is_primary' => 'is_primary', 'created_at' => 'created_at',
                    ],
                    'defaultSort' => ['is_primary' => 'desc', 'name' => 'asc'],
                ]
            )->paginate(30)
                ->withQueryString()
                ->through(fn (SupplierContact $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'position' => $c->position,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'is_primary' => $c->is_primary,
                    'supplier_id' => $c->supplier_id,
                    'supplier' => $c->supplier?->name,
                ]),
            'filters' => $this->listFilters($request),
        ]);
    }
}
