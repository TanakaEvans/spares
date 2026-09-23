<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Part;
use App\Models\SerialNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SerialNumberController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Serials/Index', [
            'serials' => SerialNumber::with('part:id,part_number', 'branch:id,name')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('serial', 'like', "%{$s}%")->orWhere('batch', 'like', "%{$s}%"))
                ->latest('id')->paginate(25)
                ->withQueryString()
                ->through(fn (SerialNumber $n) => [
                    'id' => $n->id, 'serial' => $n->serial, 'batch' => $n->batch, 'part_number' => $n->part?->part_number,
                    'part_id' => $n->part_id, 'branch' => $n->branch?->name, 'status' => $n->status, 'reference' => $n->reference,
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => $request->only('search'),
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

    public function partLookup(Request $request): \Illuminate\Http\JsonResponse
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
