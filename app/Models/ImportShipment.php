<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ImportShipment extends Model
{
    protected $fillable = ['shipment_ref', 'supplier_id', 'origin_country', 'status', 'eta', 'goods_value', 'freight', 'duty', 'notes'];
    protected $casts = ['eta' => 'date', 'goods_value' => 'decimal:2', 'freight' => 'decimal:2', 'duty' => 'decimal:2'];
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function landedTotal(): float { return round((float) $this->goods_value + (float) $this->freight + (float) $this->duty, 2); }
}
