<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianTimeLog extends Model
{
    protected $fillable = ['job_card_id', 'technician_id', 'clock_on', 'clock_off', 'minutes'];

    protected $casts = [
        'clock_on' => 'datetime',
        'clock_off' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
