<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentLine extends Model
{
    protected $fillable = ['adjustment_id', 'part_id', 'direction', 'qty', 'unit_cost'];

    protected $casts = ['qty' => 'decimal:2', 'unit_cost' => 'decimal:4'];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
