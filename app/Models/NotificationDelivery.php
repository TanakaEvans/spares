<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'event_key', 'channel', 'recipient', 'subject', 'status', 'error', 'attempts', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];
}
