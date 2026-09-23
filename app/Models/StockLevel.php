<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $fillable = [
        'part_id', 'branch_id', 'bin_location_id', 'qty_on_hand', 'qty_reserved', 'qty_on_order',
        'qty_in_transit', 'average_cost', 'reorder_point', 'reorder_qty',
        'max_level', 'last_movement_at', 'last_counted_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:2',
        'qty_reserved' => 'decimal:2',
        'qty_on_order' => 'decimal:2',
        'qty_in_transit' => 'decimal:2',
        'average_cost' => 'decimal:4',
        'reorder_point' => 'decimal:2',
        'reorder_qty' => 'decimal:2',
        'last_movement_at' => 'datetime',
        'last_counted_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class);
    }

    public function getQtyAvailableAttribute(): float
    {
        return (float) $this->qty_on_hand - (float) $this->qty_reserved;
    }
}
