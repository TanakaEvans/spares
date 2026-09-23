<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartFitment extends Model
{
    protected $fillable = [
        'part_id', 'make_id', 'model_id', 'variant_id', 'year_from', 'year_to',
        'engine_code', 'notes', 'source', 'confirmed',
    ];

    protected $casts = ['confirmed' => 'boolean'];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
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

    /**
     * Fitments applicable to a vehicle selection. Null model/variant on a
     * fitment means "all"; a year matches when inside the fitment's range.
     */
    public function scopeForVehicle(Builder $q, int $makeId, ?int $modelId = null, ?int $variantId = null, ?int $year = null): Builder
    {
        return $q->where('make_id', $makeId)
            ->when($modelId, fn (Builder $x) => $x->where(fn (Builder $y) => $y->whereNull('model_id')->orWhere('model_id', $modelId)))
            ->when($variantId, fn (Builder $x) => $x->where(fn (Builder $y) => $y->whereNull('variant_id')->orWhere('variant_id', $variantId)))
            ->when($year, fn (Builder $x) => $x
                ->where(fn (Builder $y) => $y->whereNull('year_from')->orWhere('year_from', '<=', $year))
                ->where(fn (Builder $y) => $y->whereNull('year_to')->orWhere('year_to', '>=', $year)));
    }
}
