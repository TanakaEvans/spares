<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Part;
use App\Models\SerialNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SerialNumberController extends Controller
{
    use ListQuery;

    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Serials/Index', [
            'serials' => $this->listApply(
                SerialNumber::with('part:id,part_number', 'branch:id,name')
                    ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
                    ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id)),
                $request,
                [
                    'searchable' => ['serial', 'batch', 'reference', 'part.part_number'],
                    'sortable' => [
                        'serial' => 'serial', 'batch' => 'batch',
                        'status' => 'status', 'created_at' => 'created_at',
                    ],
                    'defaultSort' => ['id' => 'desc'],
                ]
            )->paginate(25)
                ->withQueryString()
                ->through(fn (SerialNumber $n) => [
                    'id' => $n->id, 'serial' => $n->serial, 'batch' => $n->batch, 'part_number' => $n->part?->part_number,
                    'part_id' => $n->part_id, 'branch_id' => $n->branch_id, 'branch' => $n->branch?->name, 'status' => $n->status, 'reference' => $n->reference,
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => $this->listFilters($request, ['status', 'branch_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'], 'branch_id' => ['nullable', 'exists:branches,id'],
            'serial' => ['required', 'string', 'max:80'], 'batch' => ['nullable', 'string', 'max:60'], 'reference' => ['nullable', 'string', 'max:60'],
        ]);
        SerialNumber::create($data + ['status' => 'in_stock']);

        return back()->with('success', 'Serial recorded.');
    }

    public function partLookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['parts' => []]);
        }

        return response()->json([
            'parts' => Part::search($q)->active()->limit(8)->get(['id', 'part_number', 'description'])
                ->map(fn ($p) => ['id' => $p->id, 'part_number' => $p->part_number, 'description' => $p->description]),
        ]);
    }
}
