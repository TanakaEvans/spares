<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'po_id', 'part_id', 'supplier_part_number', 'description',
        'qty_ordered', 'unit_cost', 'line_total', 'qty_received',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
        'qty_received' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
