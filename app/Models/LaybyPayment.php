<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LaybyPayment extends Model
{
    protected $fillable = ['layby_id', 'amount', 'method', 'paid_on'];
    protected $casts = ['amount' => 'decimal:2', 'paid_on' => 'date'];
    public function layby(): BelongsTo { return $this->belongsTo(Layby::class); }
}
