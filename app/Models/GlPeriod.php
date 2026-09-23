<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlPeriod extends Model
{
    protected $fillable = [
        'year_id', 'period_number', 'name', 'start_date', 'end_date',
        'status', 'closed_by', 'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function year(): BelongsTo
    {
        return $this->belongsTo(GlYear::class, 'year_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
