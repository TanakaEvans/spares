<?php

namespace App\Http\Controllers\VehicleRef;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleModelController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('VehicleRef/Models/Index', [
            'models' => VehicleModel::with('make:id,name')->withCount('variants')
                ->when($request->integer('make_id'), fn ($q, $id) => $q->where('make_id', $id))
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'makes' => VehicleMake::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'make_id'),
        ]);
    }

    public function show(VehicleModel $model): Response
    {
        return Inertia::render('VehicleRef/Models/Show', [
            'model' => $model->load('make:id,name', 'variants'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'make_id' => ['required', 'exists:vehicle_makes,id'],
            'name' => ['required', 'string', 'max:100'],
            'body_type' => ['nullable', 'string', 'max:30'],
            'year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:year_from'],
        ]);

        VehicleModel::create($data);

        return back()->with('success', "Model {$data['name']} added.");
    }

    public function storeVariant(Request $request, VehicleModel $model): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'engine_code' => ['nullable', 'string', 'max:30'],
            'engine_size_cc' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,hybrid,electric,lpg'],
            'transmission' => ['nullable', 'in:manual,automatic,cvt,amt'],
            'drive' => ['nullable', 'in:4x2,4x4,awd,fwd,rwd'],
            'year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1950', 'max:2100', 'gte:year_from'],
        ]);

        $model->variants()->create($data);

        return back()->with('success', "Variant {$data['name']} added to {$model->name}.");
    }
}
