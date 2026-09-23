<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabourCode extends Model
{
    protected $fillable = [
        'code', 'description', 'category', 'rate_type', 'standard_hours', 'default_rate', 'is_active',
    ];

    protected $casts = [
        'standard_hours' => 'decimal:2',
        'default_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(LabourCodeRate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Flat rate for a make/model, most specific first, else the default. */
    public function resolveRate(?int $makeId = null, ?int $modelId = null): float
    {
        if ($modelId) {
            $r = $this->rates()->where('model_id', $modelId)->value('flat_rate');
            if ($r !== null) {
                return (float) $r;
            }
        }
        if ($makeId) {
            $r = $this->rates()->where('make_id', $makeId)->whereNull('model_id')->value('flat_rate');
            if ($r !== null) {
                return (float) $r;
            }
        }

        return (float) $this->default_rate;
    }
}
