<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number', 'document_type', 'status', 'customer_id', 'branch_id',
        'salesperson_id', 'document_date', 'expiry_date', 'parent_id',
        'subtotal_excl', 'discount_amount', 'vat_amount', 'total_incl',
        'gl_journal_id', 'credit_mode', 'reason', 'notes', 'posted_at',
    ];

    protected $casts = [
        'document_date' => 'date',
        'expiry_date' => 'date',
        'subtotal_excl' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_incl' => 'decimal:2',
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

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesDocumentLine::class, 'document_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class, 'document_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isExpired(): bool
    {
        return $this->document_type === 'quotation'
            && $this->expiry_date !== null
            && $this->expiry_date->isPast();
    }
}
