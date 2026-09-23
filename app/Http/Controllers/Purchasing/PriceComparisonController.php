<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\SupplierPriceListItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriceComparisonController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('search', ''));
        $rows = [];

        if (mb_strlen($q) >= 2) {
            $parts = Part::search($q)->active()->limit(15)->get(['id', 'part_number', 'description']);
            foreach ($parts as $part) {
                $offers = SupplierPriceListItem::where('part_id', $part->id)
                    ->whereHas('priceList', fn ($x) => $x->where('status', 'active'))
                    ->with('priceList.supplier:id,name')
                    ->get()
                    ->map(fn (SupplierPriceListItem $i) => [
                        'supplier' => $i->priceList?->supplier?->name,
                        'supplier_id' => $i->priceList?->supplier_id,
                        'cost' => (float) $i->cost_price,
                    ])
                    ->sortBy('cost')->values();
                if ($offers->isEmpty()) {
                    continue;
                }
                $rows[] = [
                    'part_id' => $part->id, 'part_number' => $part->part_number, 'description' => $part->description,
                    'offers' => $offers, 'best' => $offers->first()['cost'], 'best_supplier' => $offers->first()['supplier'],
                ];
            }
        }

        return Inertia::render('Purchasing/PriceComparison/Index', [
            'rows' => $rows,
            'filters' => ['search' => $q],
        ]);
    }
}
