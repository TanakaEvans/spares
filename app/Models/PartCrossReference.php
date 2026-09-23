<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartCrossReference extends Model
{
    protected $fillable = ['part_id', 'reference_number', 'brand_id', 'type', 'notes'];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(PartBrand::class, 'brand_id');
    }
}
