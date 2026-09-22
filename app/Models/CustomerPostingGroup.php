<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPostingGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'receivable_gl_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the GL account associated with this posting group.
     */
    public function receivableGl(): BelongsTo
    {
        return $this->belongsTo(FinanceGL::class, 'receivable_gl_id');
    }

    /**
     * Alias for receivableGl for compatibility with COT pattern.
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceGL::class, 'receivable_gl_id');
    }

    /**
     * Get the students associated with this posting group.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'customer_posting_group_id');
    }

    /**
     * Scope a query to only include active posting groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
