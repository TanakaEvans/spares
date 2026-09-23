<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTake extends Model
{
    protected $fillable = [
        'take_number', 'branch_id', 'type', 'status', 'started_by',
        'adjustment_id', 'notes', 'posted_at',
    ];

    protected $casts = ['posted_at' => 'datetime'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTakeLine::class, 'take_id');
    }
}
