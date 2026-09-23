<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_number', 'name', 'trading_name', 'type', 'tax_number', 'vat_number',
        'currency_id', 'payment_terms_days', 'credit_limit', 'lead_time_days',
        'minimum_order_value', 'email', 'phone', 'address', 'city', 'country',
        'bank_name', 'bank_branch_code', 'bank_account_name', 'bank_account_number',
        'is_active', 'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'minimum_order_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function priceLists(): HasMany
    {
        return $this->hasMany(SupplierPriceList::class);
    }

    public function activePriceList(): HasOne
    {
        return $this->hasOne(SupplierPriceList::class)->where('status', 'active');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Posted, unsettled AP balance: invoices − credited returns − payments. */
    public function apBalance(): float
    {
        return round((float) $this->invoices()->where('status', 'posted')->sum('total')
            - (float) SupplierReturn::where('supplier_id', $this->id)
                ->where('status', 'credited')->sum('credit_total')
            - (float) SupplierPayment::where('supplier_id', $this->id)->sum('amount'), 2);
    }
}
