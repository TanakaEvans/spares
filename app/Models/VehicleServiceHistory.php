<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleServiceHistory extends Model
{
    protected $table = 'vehicle_service_history';

    protected $fillable = ['vehicle_id', 'job_card_id', 'service_date', 'odometer', 'summary'];

    protected $casts = ['service_date' => 'date'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }
}
