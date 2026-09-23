<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id', 'name', 'engine_code', 'engine_size_cc', 'fuel_type',
        'power_kw', 'transmission', 'drive', 'year_from', 'year_to',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }
}
