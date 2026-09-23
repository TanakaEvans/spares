<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code', 'name', 'type', 'category', 'is_control_account',
        'control_type', 'normal_balance', 'allow_direct_posting', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_control_account' => 'boolean',
        'allow_direct_posting' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(GlJournalLine::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
