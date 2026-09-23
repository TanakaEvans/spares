<?php

namespace App\Services;

use App\Exceptions\UnpricedPartException;
use App\Models\Customer;
use App\Models\Part;
use App\Models\PriceList;
use App\Models\PriceListItem;

/**
 * Price resolution (Module 2.6): customer's list → group's list →
 * default retail. Prices are stored VAT-exclusive.
 */
class PricingService
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function priceFor(Part|int $part, Customer|int|null $customer = null): float
    {
        $partId = $part instanceof Part ? $part->id : $part;
        $customer = is_int($customer) ? Customer::find($customer) : $customer;

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
                return (float) $price;
            }
        }

        $partNumber = $part instanceof Part
            ? $part->part_number
            : Part::find($partId)?->part_number ?? (string) $partId;

        throw new UnpricedPartException($partNumber);
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
