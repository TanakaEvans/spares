<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    protected $fillable = ['name', 'type', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
