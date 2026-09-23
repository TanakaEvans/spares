<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class BankAccount extends Model
{
    protected $fillable = ['name', 'gl_account_id', 'account_number', 'bank_name', 'currency', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function glAccount(): BelongsTo { return $this->belongsTo(GlAccount::class, 'gl_account_id'); }
    public function statementLines(): HasMany { return $this->hasMany(BankStatementLine::class); }
}
