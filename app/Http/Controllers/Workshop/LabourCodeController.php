<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\LabourCode;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabourCodeController extends Controller
{
    use ListQuery;

    public function index(Request $request): Response
    {
        return Inertia::render('Workshop/Labour/Index', [
            'labourCodes' => $this->listApply(
                LabourCode::with('rates.make:id,name'),
                $request,
                [
                    'searchable' => ['code', 'description', 'category'],
                    'sortable' => [
                        'code' => 'code', 'category' => 'category', 'rate_type' => 'rate_type',
                        'standard_hours' => 'standard_hours', 'default_rate' => 'default_rate',
                    ],
                    'defaultSort' => ['code' => 'asc'],
                ]
            )->paginate(25)->withQueryString()
                ->through(fn (LabourCode $c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'description' => $c->description,
                    'category' => $c->category,
                    'rate_type' => $c->rate_type,
                    'standard_hours' => (float) $c->standard_hours,
                    'default_rate' => (float) $c->default_rate,
                    'is_active' => $c->is_active,
                    'overrides' => $c->rates->map(fn ($r) => [
                        'id' => $r->id, 'make' => $r->make?->name, 'flat_rate' => (float) $r->flat_rate,
                    ]),
                ]),
            'makes' => VehicleMake::orderBy('name')->get(['id', 'name']),
            'filters' => $this->listFilters($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:labour_codes,code'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'rate_type' => ['required', 'in:flat_rate,actual_time,fixed_price'],
            'standard_hours' => ['nullable', 'numeric', 'min:0'],
            'default_rate' => ['required', 'numeric', 'min:0'],
        ]);

        LabourCode::create($data + ['is_active' => true]);

        return back()->with('success', "Labour code {$data['code']} created.");
    }

    public function update(Request $request, LabourCode $labourCode): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:60'],
            'rate_type' => ['required', 'in:flat_rate,actual_time,fixed_price'],
            'standard_hours' => ['nullable', 'numeric', 'min:0'],
            'default_rate' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $labourCode->update($data);

        return back()->with('success', "Labour code {$labourCode->code} updated.");
    }

    public function storeRate(Request $request, LabourCode $labourCode): RedirectResponse
    {
        $data = $request->validate([
            'make_id' => ['required', 'exists:vehicle_makes,id'],
            'flat_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $labourCode->rates()->updateOrCreate(
            ['make_id' => $data['make_id'], 'model_id' => null],
            ['flat_rate' => $data['flat_rate']]
        );

        return back()->with('success', 'Make-specific rate saved.');
    }
}
