<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'rate_date',
        'buy_rate',
        'sell_rate',
        'source',
        'created_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'buy_rate' => 'decimal:6',
        'sell_rate' => 'decimal:6',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
