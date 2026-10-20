<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'invoice_number', 'supplier_ref', 'supplier_id', 'grn_id',
        'invoice_date', 'due_date', 'subtotal', 'vat_amount', 'total',
        'status', 'dispute_reason', 'gl_journal_id', 'posted_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function glJournal(): BelongsTo
    {
        return $this->belongsTo(GlJournal::class, 'gl_journal_id');
    }

    /** Supplier payments allocated against this invoice. */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'supplier_invoice_id');
    }

    /** Amount settled by allocated payments. */
    public function paidAmount(): float
    {
        return round((float) $this->allocations->sum('amount'), 2);
    }
}
