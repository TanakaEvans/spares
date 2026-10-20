<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImport extends Model
{
    protected $fillable = ['entity', 'file_name', 'user_id', 'created_count', 'updated_count', 'skipped_count', 'errors'];

    protected $casts = ['errors' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
