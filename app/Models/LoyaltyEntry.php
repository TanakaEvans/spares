<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LoyaltyEntry extends Model
{
    protected $fillable = ['customer_id', 'document_id', 'points', 'reason'];
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
