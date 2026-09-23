<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReturnLine extends Model
{
    protected $fillable = ['return_id', 'part_id', 'qty', 'unit_cost', 'condition'];

    protected $casts = ['qty' => 'decimal:2', 'unit_cost' => 'decimal:4'];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
