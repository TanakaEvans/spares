<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Promotion extends Model
{
    protected $fillable = ['name', 'type', 'value', 'applies_to', 'category_id', 'starts_at', 'ends_at', 'is_active'];
    protected $casts = ['value' => 'decimal:2', 'starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];
    public function category(): BelongsTo { return $this->belongsTo(PartCategory::class); }
}
