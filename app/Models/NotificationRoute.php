<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRoute extends Model
{
    protected $fillable = [
        'event_key', 'branch_id', 'channel', 'recipient_type', 'recipient_id', 'digest',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
