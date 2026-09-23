<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PriceListController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sales/PriceLists/Index', [
            'priceLists' => PriceList::withCount('items')->orderByDesc('is_default')->orderBy('name')->get()
                ->map(fn (PriceList $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => $p->type,
                    'is_default' => $p->is_default,
                    'is_active' => $p->is_active,
                    'items_count' => $p->items_count,
                ]),
            'unpricedCount' => $this->unpricedPartsQuery()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:price_lists,name'],
            'type' => ['required', 'in:retail,trade,wholesale,custom'],
        ]);

        $list = PriceList::create($data + ['is_active' => true]);

        return redirect()->route('sales.price-lists.show', $list)
            ->with('success', "Price list “{$list->name}” created.");
    }

    public function show(Request $request, PriceList $priceList): Response
    {
        $items = PriceListItem::where('price_list_id', $priceList->id)
            ->with('part:id,part_number,description')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->whereHas(
                'part', fn ($x) => $x->where('part_number', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")
            ))
            ->join('parts', 'parts.id', '=', 'price_list_items.part_id')
            ->orderBy('parts.part_number')
            ->select('price_list_items.*')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (PriceListItem $i) => [
                'id' => $i->id,
                'part_id' => $i->part_id,
                'part_number' => $i->part?->part_number,
                'description' => $i->part?->description,
                'price' => (float) $i->price,
            ]);

        return Inertia::render('Sales/PriceLists/Show', [
            'priceList' => $priceList->only(['id', 'name', 'type', 'is_default', 'is_active']),
            'items' => $items,
            'filters' => $request->only('search'),
            'unpricedParts' => $priceList->is_default
                ? $this->unpricedPartsQuery()->limit(50)->get(['id', 'part_number', 'description'])
                : [],
        ]);
    }

    /** Upsert a single part price (VAT-exclusive). */
    public function storeItem(Request $request, PriceList $priceList): RedirectResponse
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        PriceListItem::updateOrCreate(
            ['price_list_id' => $priceList->id, 'part_id' => $data['part_id']],
            ['price' => $data['price']]
        );

        $part = Part::find($data['part_id']);

        return back()->with('success', "Price for {$part->part_number} set to ".number_format($data['price'], 2).'.');
    }

    public function updateItem(Request $request, PriceList $priceList, PriceListItem $item): RedirectResponse
    {
        abort_unless($item->price_list_id === $priceList->id, 404);

        $data = $request->validate(['price' => ['required', 'numeric', 'min:0']]);
        $item->update($data);

        return back()->with('success', 'Price updated.');
    }

    public function destroyItem(PriceList $priceList, PriceListItem $item): RedirectResponse
    {
        abort_unless($item->price_list_id === $priceList->id, 404);
        $item->delete();

        return back()->with('success', 'Price removed.');
    }

    /** Active, non-discontinued parts that have no row on the default retail list. */
    private function unpricedPartsQuery()
    {
        $defaultId = PriceList::where('is_default', true)->value('id');

        return Part::query()
            ->where('is_active', true)
            ->where('is_discontinued', false)
            ->when($defaultId, fn ($q) => $q->whereNotExists(fn ($sub) => $sub
                ->selectRaw('1')
                ->from('price_list_items')
                ->whereColumn('price_list_items.part_id', 'parts.id')
                ->where('price_list_items.price_list_id', $defaultId)))
            ->orderBy('part_number');
    }
}
