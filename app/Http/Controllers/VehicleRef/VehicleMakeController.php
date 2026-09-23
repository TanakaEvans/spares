<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleMakeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Makes/Index', [
            'makes' => VehicleMake::withCount('models')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->orderBy('sort_order')->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:vehicle_makes,name'],
            'code' => ['required', 'string', 'max:10', 'alpha_num', 'unique:vehicle_makes,code'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
        ]);

        $data['code'] = strtoupper($data['code']);
        VehicleMake::create($data);

        return back()->with('success', "Make {$data['name']} added.");
    }

    public function update(Request $request, VehicleMake $make): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:vehicle_makes,name,'.$make->id],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);

        $make->update($data);

        return back()->with('success', "Make {$make->name} updated.");
    }
}
