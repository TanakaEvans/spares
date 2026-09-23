<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Layby extends Model
{
    protected $fillable = ['layby_number', 'customer_id', 'branch_id', 'total', 'deposit_paid', 'status', 'notes'];
    protected $casts = ['total' => 'decimal:2', 'deposit_paid' => 'decimal:2'];
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function payments(): HasMany { return $this->hasMany(LaybyPayment::class); }
    public function balance(): float { return round((float) $this->total - (float) $this->deposit_paid, 2); }
}
