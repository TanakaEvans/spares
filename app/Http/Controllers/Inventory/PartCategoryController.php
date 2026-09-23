<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inventory/Categories/Index', [
            'categories' => PartCategory::withCount(['children'])
                ->orderBy('sort_order')->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'parent_id' => $c->parent_id,
                    'is_active' => $c->is_active,
                    'children_count' => $c->children_count,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:part_categories,code'],
            'parent_id' => ['nullable', 'exists:part_categories,id'],
        ]);

        $data['code'] = strtoupper($data['code']);
        PartCategory::create($data);

        return back()->with('success', "Category {$data['name']} added.");
    }

    public function update(Request $request, PartCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);

        $category->update($data);

        return back()->with('success', "Category {$category->name} updated.");
    }
}
