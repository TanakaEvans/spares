<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VatReturn extends Model
{
    protected $fillable = [
        'reference', 'period_start', 'period_end', 'output_vat', 'input_vat',
        'net_payable', 'status', 'prepared_by', 'submitted_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'output_vat' => 'decimal:2',
        'input_vat' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
