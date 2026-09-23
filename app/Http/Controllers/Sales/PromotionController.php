<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sales/Promotions/Index', [
            'promotions' => Promotion::with('category:id,name')->latest('id')->get()->map(fn (Promotion $p) => [
                'id' => $p->id, 'name' => $p->name, 'type' => $p->type, 'value' => (float) $p->value, 'applies_to' => $p->applies_to,
                'category' => $p->category?->name, 'starts_at' => $p->starts_at?->toDateString(), 'ends_at' => $p->ends_at?->toDateString(), 'is_active' => $p->is_active,
            ]),
            'categories' => PartCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'], 'applies_to' => ['required', 'in:all,category'],
            'category_id' => ['nullable', 'exists:part_categories,id'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date'],
        ]);
        Promotion::create($data + ['is_active' => true]);

        return back()->with('success', 'Promotion created.');
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return back()->with('success', 'Promotion updated.');
    }
}
