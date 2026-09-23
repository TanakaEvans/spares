<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $fillable = [
        'adjustment_number', 'branch_id', 'reason_code', 'status',
        'created_by', 'gl_journal_id', 'notes', 'posted_at',
    ];

    protected $casts = ['posted_at' => 'datetime'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id');
    }
}
