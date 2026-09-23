<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovedSupplier extends Model
{
    protected $fillable = ['part_id', 'supplier_id', 'is_preferred', 'lead_time_days', 'notes'];

    protected $casts = ['is_preferred' => 'boolean'];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
