<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** The equal-and-opposite journal that reversed this one (if any). */
    public function reversingJournal(): BelongsTo
    {
        return $this->belongsTo(GlJournal::class, 'reversing_journal_id');
    }

    /** The original journal this one reverses (if this is a reversal). */
    public function reversalOf(): HasOne
    {
        return $this->hasOne(GlJournal::class, 'reversing_journal_id');
    }
}
