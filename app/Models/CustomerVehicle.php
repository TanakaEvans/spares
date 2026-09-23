<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerVehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'make_id', 'model_id', 'variant_id', 'branch_id',
        'registration', 'vin', 'year', 'engine_code', 'colour', 'notes', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariant::class, 'variant_id');
    }

    public function serviceHistory(): HasMany
    {
        return $this->hasMany(VehicleServiceHistory::class, 'vehicle_id');
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'vehicle_id');
    }

    public function label(): string
    {
        return trim(($this->make?->name ?? '').' '.($this->model?->name ?? '')).' — '.$this->registration;
    }
}
