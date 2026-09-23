<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceivedNote extends Model
{
    protected $fillable = [
        'grn_number', 'po_id', 'supplier_id', 'branch_id', 'received_by',
        'received_date', 'status', 'delivery_note_number', 'gl_journal_id',
        'notes', 'posted_at',
    ];

    protected $casts = ['received_date' => 'date', 'posted_at' => 'datetime'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'grn_id');
    }

    public function acceptedValue(): float
    {
        return round($this->lines->sum(fn ($l) => (float) $l->qty_received * (float) $l->unit_cost), 2);
    }
}
