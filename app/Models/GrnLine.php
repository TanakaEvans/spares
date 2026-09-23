<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnLine extends Model
{
    protected $fillable = [
        'grn_id', 'po_line_id', 'part_id', 'qty_received', 'qty_rejected',
        'rejection_reason', 'unit_cost', 'bin_location_id',
    ];

    protected $casts = [
        'qty_received' => 'decimal:2',
        'qty_rejected' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function poLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'po_line_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
