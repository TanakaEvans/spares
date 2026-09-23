<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardPart extends Model
{
    protected $fillable = [
        'job_card_id', 'part_id', 'description', 'qty', 'unit_price', 'unit_cost',
        'status', 'is_warranty', 'issued_by', 'issued_at',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'is_warranty' => 'boolean',
        'issued_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
