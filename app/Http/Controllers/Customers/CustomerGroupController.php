<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\PriceList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerGroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Customers/Groups/Index', [
            'groups' => CustomerGroup::withCount('customers')->with('priceList:id,name')->orderBy('name')->get()
                ->map(fn (CustomerGroup $g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'price_list' => $g->priceList?->name,
                    'customers_count' => $g->customers_count,
                ]),
            'priceLists' => PriceList::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:customer_groups,name'],
            'price_list_id' => ['nullable', 'exists:price_lists,id'],
        ]);

        CustomerGroup::create($data);

        return back()->with('success', "Customer group “{$data['name']}” created.");
    }

    public function update(Request $request, CustomerGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:customer_groups,name,'.$group->id],
            'price_list_id' => ['nullable', 'exists:price_lists,id'],
        ]);

        $group->update($data);

        return back()->with('success', "Group “{$group->name}” updated.");
    }
}
