<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPayment extends Model
{
    protected $fillable = [
        'document_id', 'method', 'amount', 'tendered', 'change_given', 'reference', 'received_by',
    ];

    protected $casts = ['amount' => 'decimal:2', 'tendered' => 'decimal:2', 'change_given' => 'decimal:2'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'document_id');
    }
}
