<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartSupersession extends Model
{
    protected $fillable = ['old_part_id', 'new_part_id', 'effective_date', 'reason', 'is_active'];

    protected $casts = ['effective_date' => 'date', 'is_active' => 'boolean'];

    public function oldPart(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'old_part_id');
    }

    public function newPart(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'new_part_id');
    }
}
