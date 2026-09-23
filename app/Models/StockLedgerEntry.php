<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedgerEntry extends Model
{
    protected $table = 'stock_ledger';

    protected $fillable = [
        'part_id', 'branch_id', 'transaction_type', 'qty', 'unit_cost',
        'running_balance', 'reference_type', 'reference_id', 'notes', 'user_id',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'running_balance' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
