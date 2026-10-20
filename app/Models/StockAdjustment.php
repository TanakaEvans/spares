<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(GlJournal::class, 'gl_journal_id');
    }

    /** The stock take that produced this adjustment, if any. */
    public function stockTake(): HasOne
    {
        return $this->hasOne(StockTake::class, 'adjustment_id');
    }
}
