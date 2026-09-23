<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TechnicalBulletin extends Model
{
    protected $fillable = ['title', 'make_id', 'category', 'body', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function make(): BelongsTo { return $this->belongsTo(VehicleMake::class, 'make_id'); }
}
