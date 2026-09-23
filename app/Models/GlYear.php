<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'status'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function periods(): HasMany
    {
        return $this->hasMany(GlPeriod::class, 'year_id');
    }
}
