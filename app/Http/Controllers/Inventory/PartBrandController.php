<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PartBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartBrandController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventory/Brands/Index', [
            'brands' => PartBrand::query()
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:part_brands,name'],
            'code' => ['required', 'string', 'max:20', 'alpha_num', 'unique:part_brands,code'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'is_oem_brand' => ['required', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        PartBrand::create($data);

        return back()->with('success', "Brand {$data['name']} added.");
    }
}
