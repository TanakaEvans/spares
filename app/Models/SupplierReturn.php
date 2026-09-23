<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReturn extends Model
{
    protected $fillable = [
        'return_number', 'supplier_id', 'branch_id', 'reason', 'status',
        'supplier_rma', 'credit_note_ref', 'credit_total', 'gl_journal_id',
        'notes', 'shipped_at',
    ];

    protected $casts = ['credit_total' => 'decimal:2', 'shipped_at' => 'datetime'];

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
        return $this->hasMany(SupplierReturnLine::class, 'return_id');
    }

    public function totalValue(): float
    {
        return round($this->lines->sum(fn ($l) => (float) $l->qty * (float) $l->unit_cost), 2);
    }
}
