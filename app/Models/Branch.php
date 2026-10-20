<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use Auditable;
    /** Audited events (rule 10). */
    protected static array $auditEvents = ['created', 'updated', 'deleted'];

    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'logo',
        'bank_name',
        'bank_branch_code',
        'bank_account_name',
        'bank_account_number',
        'is_main_branch',
        'status',
        'head_id',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function head()
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }
}
