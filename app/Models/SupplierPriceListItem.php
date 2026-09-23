<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPriceListItem extends Model
{
    protected $fillable = [
        'price_list_id', 'part_id', 'supplier_part_number', 'description',
        'cost_price', 'minimum_qty',
    ];

    protected $casts = ['cost_price' => 'decimal:4', 'minimum_qty' => 'decimal:2'];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(SupplierPriceList::class, 'price_list_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
