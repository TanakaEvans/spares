<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DeliveryNote extends Model
{
    protected $fillable = ['delivery_number', 'document_id', 'customer_id', 'branch_id', 'delivery_date', 'driver', 'vehicle_reg', 'status', 'address'];
    protected $casts = ['delivery_date' => 'date'];
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function document(): BelongsTo { return $this->belongsTo(SalesDocument::class, 'document_id'); }
}
