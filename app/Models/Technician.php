<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technician extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'name', 'skill_level', 'specialisations', 'cost_rate', 'is_active',
    ];

    protected $casts = [
        'cost_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
