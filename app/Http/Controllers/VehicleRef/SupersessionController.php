<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\PartSupersession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupersessionController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Supersessions/Index', [
            'rows' => $this->listApply(PartSupersession::with('oldPart:id,part_number,description', 'newPart:id,part_number,description')
                ->when($request->string('active')->toString() !== '', fn ($q) => $q->where('is_active', $request->string('active')->toString() === '1')),
                $request,
                [
                    'searchable' => ['oldPart.part_number', 'oldPart.description', 'newPart.part_number', 'newPart.description'],
                    'sortable' => ['reason' => 'reason', 'is_active' => 'is_active', 'created_at' => 'created_at'],
                    'defaultSort' => ['id' => 'desc'],
                ])
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
            'filters' => $this->listFilters($request, ['active']),
        ]);
    }
}
