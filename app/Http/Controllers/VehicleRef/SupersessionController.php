<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\PartSupersession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupersessionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Supersessions/Index', [
            'rows' => PartSupersession::with('oldPart:id,part_number,description', 'newPart:id,part_number,description')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->whereHas(
                    'oldPart', fn ($x) => $x->where('part_number', 'like', "%{$s}%")
                )->orWhereHas('newPart', fn ($x) => $x->where('part_number', 'like', "%{$s}%")))
                ->latest('id')
                ->paginate(30)
                ->withQueryString()
                ->through(fn (PartSupersession $r) => [
                    'id' => $r->id,
                    'old_part_id' => $r->old_part_id,
                    'old_number' => $r->oldPart?->part_number,
                    'old_description' => $r->oldPart?->description,
                    'new_part_id' => $r->new_part_id,
                    'new_number' => $r->newPart?->part_number,
                    'new_description' => $r->newPart?->description,
                    'reason' => $r->reason,
                    'is_active' => $r->is_active,
                ]),
            'filters' => $request->only('search'),
        ]);
    }
}
