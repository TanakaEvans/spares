<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrossReferenceController extends Controller
{
    /**
     * Cross-reference search (Module 8.3): a customer arrives with ANY
     * number — OEM, aftermarket, competitor — and we find our part.
     * Superseded parts resolve to their replacement with a notice.
     */
    public function __invoke(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $results = null;

        if ($query !== '') {
            $results = Part::search($query)
                ->with(['brand:id,name', 'category:id,name', 'crossReferences.brand:id,name',
                    'supersededBy.newPart:id,part_number,description', 'stockLevels'])
                ->limit(25)
                ->get()
                ->map(fn (Part $p) => [
                    'id' => $p->id,
                    'part_number' => $p->part_number,
                    'oem_number' => $p->oem_number,
                    'description' => $p->description,
                    'brand' => $p->brand?->name,
                    'category' => $p->category?->name,
                    'is_active' => $p->is_active,
                    'qty_available' => (float) $p->stockLevels->sum(
                        fn ($l) => (float) $l->qty_on_hand - (float) $l->qty_reserved
                    ),
                    'references' => $p->crossReferences->map(fn ($x) => [
                        'number' => $x->reference_number,
                        'brand' => $x->brand?->name,
                        'type' => $x->type,
                    ]),
                    'superseded_by' => $p->supersededBy?->newPart ? [
                        'id' => $p->supersededBy->newPart->id,
                        'part_number' => $p->supersededBy->newPart->part_number,
                        'description' => $p->supersededBy->newPart->description,
                    ] : null,
                ]);
        }

        return Inertia::render('VehicleRef/CrossRef/Index', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
