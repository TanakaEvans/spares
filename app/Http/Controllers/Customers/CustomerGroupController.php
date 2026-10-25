<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\PriceList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerGroupController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        return Inertia::render('Customers/Groups/Index', [
            'groups' => $this->listApply(
                CustomerGroup::withCount('customers')->with('priceList:id,name'),
                $request,
                [
                    'searchable' => ['name'],
                    'sortable' => ['name' => 'name', 'created_at' => 'created_at'],
                    'defaultSort' => ['name' => 'asc'],
                ]
            )->paginate(25)->withQueryString()
                ->through(fn (CustomerGroup $g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'price_list' => $g->priceList?->name,
                    'price_list_id' => $g->price_list_id,
                    'customers_count' => $g->customers_count,
                ]),
            'priceLists' => PriceList::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $this->listFilters($request),
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
