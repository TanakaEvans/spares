<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineCode extends Model
{
    protected $fillable = [
        'code', 'make_id', 'description', 'capacity_cc', 'fuel_type', 'aspiration', 'cylinders',
    ];

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }
}
