<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number', 'supplier_id', 'branch_id', 'buyer_id', 'status',
        'order_date', 'expected_date', 'currency_id', 'exchange_rate',
        'subtotal', 'vat_amount', 'total', 'supplier_ref', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'po_id');
    }

    public function grns(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class, 'po_id');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, ['submitted', 'confirmed', 'partial'], true);
    }

    public function refreshReceivingStatus(): void
    {
        $lines = $this->lines()->get();
        $anyReceived = $lines->sum('qty_received') > 0;
        $fullyReceived = $lines->every(fn ($l) => (float) $l->qty_received >= (float) $l->qty_ordered);

        $this->update(['status' => $fullyReceived ? 'received' : ($anyReceived ? 'partial' : $this->status)]);
    }
}
