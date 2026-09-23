<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SerialNumber extends Model
{
    protected $fillable = ['part_id', 'branch_id', 'serial', 'batch', 'status', 'reference'];
    public function part(): BelongsTo { return $this->belongsTo(Part::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
