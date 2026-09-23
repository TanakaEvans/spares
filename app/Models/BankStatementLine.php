<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BankStatementLine extends Model
{
    protected $fillable = ['bank_account_id', 'txn_date', 'description', 'amount', 'reconciled'];
    protected $casts = ['txn_date' => 'date', 'amount' => 'decimal:2', 'reconciled' => 'boolean'];
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
}
