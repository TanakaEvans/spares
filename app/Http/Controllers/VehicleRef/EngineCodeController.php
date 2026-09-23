<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\EngineCode;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EngineCodeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Engines/Index', [
            'engines' => EngineCode::with('make:id,name')
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('code', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")
                ))
                ->orderBy('code')
                ->paginate(25)
                ->withQueryString(),
            'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:engine_codes,code'],
            'make_id' => ['nullable', 'exists:vehicle_makes,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'capacity_cc' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,hybrid,electric,lpg'],
            'aspiration' => ['nullable', 'in:naturally_aspirated,turbo,supercharged'],
            'cylinders' => ['nullable', 'integer', 'min:1', 'max:16'],
        ]);

        EngineCode::create($data);

        return back()->with('success', "Engine code {$data['code']} added.");
    }
}
