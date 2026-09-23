<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourCodeRate extends Model
{
    protected $fillable = ['labour_code_id', 'make_id', 'model_id', 'flat_rate'];

    protected $casts = ['flat_rate' => 'decimal:2'];

    public function labourCode(): BelongsTo
    {
        return $this->belongsTo(LabourCode::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }
}
