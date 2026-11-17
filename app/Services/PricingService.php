<?php

namespace App\Services;

use App\Exceptions\UnpricedPartException;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\StockLevel;
use App\Models\SupplierPriceList;

/**
 * Price resolution (Module 2.6): customer's list → group's list →
 * default retail → cost-plus. Prices are stored VAT-exclusive.
 *
 * When a part has no entry on any applicable price list, the selling price is
 * derived automatically from its purchase cost: cost × (1 + markup%), with the
 * markup and the whole behaviour driven by settings (`sales.default_markup_pct`,
 * `sales.auto_price_from_cost`). VAT is added on top by computeLine() as usual,
 * so a 40% markup on cost 100 sells at 140 excl / 161 incl at 15% VAT. An
 * explicit price list entry always wins, so individual parts can be overridden.
 */
class PricingService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * The selling price (VAT-exclusive) for a part. Branch is used to pick the
     * right average cost for the cost-plus fallback; omit it to use the best
     * known cost across branches.
     */
    public function priceFor(Part|int $part, Customer|int|null $customer = null, ?int $branchId = null): float
    {
        $resolved = $this->resolve($part, $customer, $branchId);
        if ($resolved['price'] === null) {
            throw new UnpricedPartException($resolved['part_number']);
        }

        return $resolved['price'];
    }

    /**
     * Resolve a price and say where it came from, without throwing — the till
     * uses this to label an auto-priced line and show the cost it is based on.
     *
     * @return array{price: float|null, source: string, cost: float, markup: float, part_number: string}
     */
    public function resolve(Part|int $part, Customer|int|null $customer = null, ?int $branchId = null): array
    {
        $partId = $part instanceof Part ? $part->id : $part;
        $customer = is_int($customer) ? Customer::find($customer) : $customer;
        $partNumber = $part instanceof Part
            ? $part->part_number
            : Part::find($partId)?->part_number ?? (string) $partId;

        $markup = (float) $this->settings->get('sales.default_markup_pct', $branchId);
        $cost = $this->costFor($partId, $branchId);

        $lists = [];
        if ($customer) {
            $list = $customer->effectivePriceList();
            if ($list) {
                $lists[] = $list->id;
            }
        }
        $default = PriceList::where('is_default', true)->first();
        if ($default && ! in_array($default->id, $lists, true)) {
            $lists[] = $default->id;
        }

        foreach ($lists as $listId) {
            $price = PriceListItem::where('price_list_id', $listId)
                ->where('part_id', $partId)
                ->value('price');
            if ($price !== null) {
                return ['price' => (float) $price, 'source' => 'list', 'cost' => $cost, 'markup' => $markup, 'part_number' => $partNumber];
            }
        }

        // Cost-plus fallback: sell at cost + markup when we know what it cost.
        if ((bool) $this->settings->get('sales.auto_price_from_cost') && $cost > 0) {
            return ['price' => round($cost * (1 + $markup / 100), 2), 'source' => 'auto', 'cost' => $cost, 'markup' => $markup, 'part_number' => $partNumber];
        }

        return ['price' => null, 'source' => 'none', 'cost' => $cost, 'markup' => $markup, 'part_number' => $partNumber];
    }

    /**
     * Purchase cost for a part (VAT-exclusive): its average cost at the branch
     * (or the highest average cost across branches when none is given), falling
     * back to the latest active supplier cost. Zero means no known cost.
     */
    public function costFor(int $partId, ?int $branchId = null): float
    {
        $avco = (float) StockLevel::where('part_id', $partId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->max('average_cost');
        if ($avco > 0) {
            return round($avco, 4);
        }

        $cost = SupplierPriceList::where('status', 'active')
            ->whereHas('items', fn ($q) => $q->where('part_id', $partId))
            ->with(['items' => fn ($q) => $q->where('part_id', $partId)])
            ->latest('effective_date')->first()?->items->first()?->cost_price;

        return $cost ? round((float) $cost, 4) : 0.0;
    }

    public function vatRate(): float
    {
        return (float) $this->settings->get('tax.vat_rate_default');
    }

    /** Line maths shared by every sales document. */
    public function computeLine(float $qty, float $unitPriceExcl, float $discountPct): array
    {
        $gross = $qty * $unitPriceExcl;
        $discount = round($gross * $discountPct / 100, 2);
        $excl = round($gross - $discount, 2);
        $vat = round($excl * $this->vatRate() / 100, 2);

        return [
            'discount_amount' => $discount,
            'line_total_excl' => $excl,
            'vat_rate' => $this->vatRate(),
            'vat_amount' => $vat,
            'line_total_incl' => round($excl + $vat, 2),
        ];
    }
}
