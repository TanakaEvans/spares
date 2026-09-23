<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardLabour extends Model
{
    protected $fillable = [
        'job_card_id', 'labour_code_id', 'technician_id', 'description',
        'hours', 'rate', 'line_total', 'cost', 'is_warranty',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_warranty' => 'boolean',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function labourCode(): BelongsTo
    {
        return $this->belongsTo(LabourCode::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
