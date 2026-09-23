<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlJournal extends Model
{
    protected $fillable = [
        'journal_number', 'journal_type', 'period_id', 'branch_id', 'journal_date',
        'description', 'reference', 'source_type', 'source_id', 'status',
        'posted_by', 'posted_at', 'reversed_by', 'reversing_journal_id',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(GlJournalLine::class, 'journal_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(GlPeriod::class, 'period_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
