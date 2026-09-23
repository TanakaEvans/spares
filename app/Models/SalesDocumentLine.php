<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDocumentLine extends Model
{
    protected $fillable = [
        'document_id', 'part_id', 'description', 'qty', 'unit_price',
        'discount_pct', 'vat_rate', 'vat_amount', 'line_total_excl',
        'line_total_incl', 'unit_cost', 'qty_credited', 'source_line_id',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total_excl' => 'decimal:2',
        'line_total_incl' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'qty_credited' => 'decimal:2',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'document_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
