<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_number', 'type', 'name', 'trading_name', 'vat_number',
        'email', 'phone', 'address', 'city', 'customer_group_id',
        'price_list_id', 'payment_terms_days', 'credit_limit', 'on_hold',
        'hold_reason', 'is_walk_in', 'is_active', 'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'on_hold' => 'boolean',
        'is_walk_in' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SalesDocument::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CustomerReceipt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function walkIn(): self
    {
        return static::where('is_walk_in', true)->firstOrFail();
    }

    /**
     * Outstanding account balance = on-account sale portions − account-mode
     * credit notes − receipts. Mirrors this customer's slice of the Trade
     * Debtors control (1210).
     */
    public function arBalance(): float
    {
        $charged = (float) SalesPayment::where('method', 'account')
            ->whereHas('document', fn ($q) => $q
                ->where('customer_id', $this->id)
                ->where('document_type', 'invoice')
                ->where('status', 'posted'))
            ->sum('amount');

        $credited = (float) SalesDocument::where('customer_id', $this->id)
            ->where('document_type', 'credit_note')
            ->where('status', 'posted')
            ->where('credit_mode', 'account')
            ->sum('total_incl');

        $received = (float) CustomerReceipt::where('customer_id', $this->id)->sum('amount');

        return round($charged - $credited - $received, 2);
    }

    /** Resolve the customer's effective price list: own → group's → default retail. */
    public function effectivePriceList(): ?PriceList
    {
        return $this->priceList
            ?? $this->group?->priceList
            ?? PriceList::where('is_default', true)->first();
    }
}
