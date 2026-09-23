<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\BinLocation;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BinLocationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Bins/Index', [
            'bins' => BinLocation::with('branch:id,name')
                ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                ->orderBy('code')
                ->paginate(50)
                ->withQueryString(),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'filters' => $request->only('branch_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = strtoupper($data['code']);

        $exists = BinLocation::where('branch_id', $data['branch_id'])
            ->where('code', $data['code'])->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'That bin code already exists at this branch.']);
        }

        BinLocation::create($data);

        return back()->with('success', "Bin {$data['code']} added.");
    }
}
