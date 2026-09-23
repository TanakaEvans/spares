<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\SupplierContact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierContactController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Contacts/Index', [
            'contacts' => SupplierContact::with('supplier:id,name')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
                        ->orWhereHas('supplier', fn ($y) => $y->where('name', 'like', "%{$s}%"))
                ))
                ->orderByDesc('is_primary')->orderBy('name')
                ->paginate(30)
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
            'filters' => $request->only('search'),
        ]);
    }
}
