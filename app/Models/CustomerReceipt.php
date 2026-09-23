<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'customer_id', 'branch_id', 'receipt_date', 'method',
        'amount', 'allocated', 'reference', 'gl_journal_id', 'notes',
        'received_by', 'posted_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'allocated' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ReceiptAllocation::class, 'receipt_id');
    }

    public function unallocated(): float
    {
        return round((float) $this->amount - (float) $this->allocated, 2);
    }
}
