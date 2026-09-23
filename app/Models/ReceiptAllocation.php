<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptAllocation extends Model
{
    protected $fillable = ['receipt_id', 'document_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(CustomerReceipt::class, 'receipt_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'document_id');
    }
}
