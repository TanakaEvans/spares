<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WarrantyClaim extends Model
{
    protected $fillable = ['claim_number', 'job_card_id', 'supplier_id', 'part_id', 'status', 'fault', 'claim_amount', 'credit_amount', 'supplier_ref'];
    protected $casts = ['claim_amount' => 'decimal:2', 'credit_amount' => 'decimal:2'];
    public function jobCard(): BelongsTo { return $this->belongsTo(JobCard::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function part(): BelongsTo { return $this->belongsTo(Part::class); }
}
