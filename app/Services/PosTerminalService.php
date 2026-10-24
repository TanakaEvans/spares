<?php

namespace App\Services;

use App\Exceptions\UnpricedPartException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\SalesDocument;
use App\Models\StockLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side of the till (Module 2.1): everything the counter needs to find,
 * price and fetch a part in one round trip — price for this customer, stock
 * available here, the bin to walk to, category and supersession notice —
 * plus the idle-screen helpers (top sellers, category browse, today's takings).
 * Posting stays in SalesPostingService.
 */
class PosTerminalService
{
    public function __construct(private readonly PricingService $pricing) {}

    /** Free-text / exact / category search, in-stock parts first. */
    public function lookup(Branch $branch, ?Customer $customer, string $q = '', bool $exact = false, ?int $categoryId = null, int $limit = 20): Collection
    {
        $query = Part::query()->active()->with(['category:id,name', 'supersededBy.newPart:id,part_number']);

        if ($exact) {
            $query->where(fn ($x) => $x->where('part_number', $q)->orWhere('barcode_ean', $q)->orWhere('barcode_code128', $q))->limit(1);
        } else {
            if ($categoryId) {
                $ids = PartCategory::where('id', $categoryId)->orWhere('parent_id', $categoryId)->pluck('id');
                $ids = $ids->merge(PartCategory::whereIn('parent_id', $ids)->pluck('id'))->unique();
                $query->whereIn('category_id', $ids);
            }
            if ($q !== '') {
                $query->search($q);
            }
            $query->orderByRaw('(SELECT CASE WHEN COALESCE(MAX(sl.qty_on_hand), 0) > 0 THEN 0 ELSE 1 END FROM stock_levels sl WHERE sl.part_id = parts.id AND sl.branch_id = ?)', [$branch->id])
                ->orderBy('part_number')
                ->limit($limit);
        }

        return $this->present($query->get(), $branch, $customer);
    }

    /** Price + stock + bin rows for the till, in the given order. */
    public function present(Collection $parts, Branch $branch, ?Customer $customer): Collection
    {
        $levels = StockLevel::with('binLocation:id,code')
            ->where('branch_id', $branch->id)->whereIn('part_id', $parts->pluck('id'))
            ->get()->keyBy('part_id');

        return $parts->map(function (Part $p) use ($levels, $customer) {
            try {
                $price = $this->pricing->priceFor($p, $customer);
                $priced = true;
            } catch (UnpricedPartException) {
                $price = 0.0;
                $priced = false;
            }
            $level = $levels->get($p->id);

            return [
                'id' => $p->id,
                'part_number' => $p->part_number,
                'description' => $p->description,
                'oem_number' => $p->oem_number,
                'category' => $p->category?->name,
                'price' => round($price, 2),
                'priced' => $priced,
                'on_hand' => (float) ($level->qty_on_hand ?? 0),
                'available' => $level ? round($level->qty_available, 2) : 0.0,
                'reorder_point' => (float) ($level->reorder_point ?? 0),
                'bin' => $level?->binLocation?->code,
                'superseded_by' => $p->supersededBy?->newPart?->part_number,
                'discontinued' => (bool) $p->is_discontinued,
            ];
        })->values();
    }

    /** Best sellers at this branch over the last N days — the idle screen's quick tiles. */
    public function topSellers(Branch $branch, int $days = 30, int $limit = 12): Collection
    {
        $ids = DB::table('sales_document_lines as l')
            ->join('sales_documents as d', 'd.id', '=', 'l.document_id')
            ->where('d.document_type', 'invoice')->where('d.branch_id', $branch->id)
            ->where('d.document_date', '>=', now()->subDays($days)->toDateString())
            ->groupBy('l.part_id')->orderByRaw('SUM(l.qty) DESC')->limit($limit)
            ->pluck('l.part_id');

        if ($ids->isEmpty()) {
            return collect();
        }
        $parts = Part::active()->with(['category:id,name', 'supersededBy.newPart:id,part_number'])->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => $ids->search($p->id))->values();

        return $this->present($parts, $branch, null);
    }

    /** Top-level categories that actually hold active parts, with counts (sub-trees rolled up). */
    public function categories(): Collection
    {
        $counts = Part::active()->groupBy('category_id')->selectRaw('category_id, COUNT(*) n')->pluck('n', 'category_id');
        $all = PartCategory::where('is_active', true)->get(['id', 'name', 'parent_id', 'sort_order']);
        $byId = $all->keyBy('id');
        $root = function ($c) use ($byId) {
            for ($i = 0; $c->parent_id && $i < 5; $i++) {
                $c = $byId[$c->parent_id] ?? $c;
            }

            return $c;
        };

        $totals = [];
        foreach ($all as $c) {
            $r = $root($c);
            $totals[$r->id] = ($totals[$r->id] ?? 0) + (int) ($counts[$c->id] ?? 0);
        }

        return $all->whereNull('parent_id')->filter(fn ($c) => ($totals[$c->id] ?? 0) > 0)
            ->sortBy('sort_order')
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'count' => $totals[$c->id]])->values();
    }

    /** This cashier's posted sales today at this branch (header strip). */
    public function todayFor(int $userId, Branch $branch): array
    {
        $row = SalesDocument::where('document_type', 'invoice')->where('branch_id', $branch->id)
            ->where('salesperson_id', $userId)->whereDate('document_date', now()->toDateString())
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total_incl), 0) total')->first();

        return ['count' => (int) $row->n, 'total' => round((float) $row->total, 2)];
    }

    /** What the till shows the moment a sale posts. */
    public function completedSummary(SalesDocument $invoice): array
    {
        $invoice->loadMissing('payments', 'customer:id,name', 'lines:id,document_id');

        return [
            'id' => $invoice->id,
            'number' => $invoice->document_number,
            'total' => (float) $invoice->total_incl,
            'customer' => $invoice->customer?->name,
            'lines' => $invoice->lines->count(),
            'change' => round((float) $invoice->payments->sum('change_given'), 2),
            'methods' => $invoice->payments->map(fn ($p) => ['method' => $p->method, 'amount' => (float) $p->amount])->values()->all(),
        ];
    }
}
