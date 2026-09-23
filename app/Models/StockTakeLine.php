<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTakeLine extends Model
{
    protected $fillable = ['take_id', 'part_id', 'system_qty', 'counted_qty', 'unit_cost'];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'counted_qty' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
