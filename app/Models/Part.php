<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'part_number', 'oem_number', 'description', 'short_description',
        'category_id', 'brand_id', 'unit_id', 'barcode_ean', 'barcode_code128',
        'weight_kg', 'is_oem', 'is_active', 'is_discontinued', 'notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'is_oem' => 'boolean',
        'is_active' => 'boolean',
        'is_discontinued' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(PartBrand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function crossReferences(): HasMany
    {
        return $this->hasMany(PartCrossReference::class);
    }

    public function fitments(): HasMany
    {
        return $this->hasMany(PartFitment::class);
    }

    /** The active supersession pointing AWAY from this (old) part. */
    public function supersededBy(): HasOne
    {
        return $this->hasOne(PartSupersession::class, 'old_part_id')->where('is_active', true);
    }

    public function supersedes(): HasMany
    {
        return $this->hasMany(PartSupersession::class, 'new_part_id')->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The one part search (ui-rules §6): part number, OEM number, barcodes,
     * description AND cross-reference numbers.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('part_number', 'like', $like)
            ->orWhere('oem_number', 'like', $like)
            ->orWhere('description', 'like', $like)
            ->orWhere('barcode_ean', 'like', $like)
            ->orWhere('barcode_code128', 'like', $like)
            ->orWhereHas('crossReferences', fn (Builder $x) => $x->where('reference_number', 'like', $like))
        );
    }

    /**
     * Follow the supersession chain to the current part (A → B → C returns C).
     * Guards against accidental cycles.
     */
    public function resolveCurrent(int $maxHops = 5): self
    {
        $part = $this;
        $seen = [$part->id];

        while ($maxHops-- > 0 && ($link = $part->supersededBy()->with('newPart')->first())) {
            $next = $link->newPart;
            if ($next === null || in_array($next->id, $seen, true)) {
                break;
            }
            $part = $next;
            $seen[] = $part->id;
        }

        return $part;
    }
}
