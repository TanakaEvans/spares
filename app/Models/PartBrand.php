<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartBrand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'country_of_origin', 'is_oem_brand', 'is_active'];

    protected $casts = ['is_oem_brand' => 'boolean', 'is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
