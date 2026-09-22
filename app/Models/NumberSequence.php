<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'branch_id',
        'prefix',
        'include_date',
        'date_format',
        'next_number',
        'padding',
        'reset_frequency',
        'last_reset_at',
    ];

    protected $casts = [
        'include_date' => 'boolean',
        'next_number' => 'integer',
        'padding' => 'integer',
        'last_reset_at' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
