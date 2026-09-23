<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPriceList extends Model
{
    protected $fillable = [
        'supplier_id', 'name', 'effective_date', 'currency_id', 'status',
        'imported_by', 'matched_count', 'unmatched_count', 'notes',
    ];

    protected $casts = ['effective_date' => 'date'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPriceListItem::class, 'price_list_id');
    }
}
