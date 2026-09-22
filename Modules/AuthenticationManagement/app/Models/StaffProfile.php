<?php

namespace Modules\AuthenticationManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    use HasFactory;

    protected $table = 'auth_staff_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'phone_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'department',
        'position',
        'job_title',
        'hire_date',
        'employment_type',
        'salary',
        'status',
        'responsibilities',
        'supervisor_id',
        'photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'responsibilities' => 'array',
        ];
    }

    /**
     * Get the user that owns the staff profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the supervisor (another staff member)
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    /**
     * Get staff members supervised by this staff
     */
    public function supervisees()
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        $name .= ' ' . $this->last_name;
        return $name;
    }

    /**
     * Get age
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->diffInYears(now());
    }

    /**
     * Get years of service
     */
    public function getYearsOfServiceAttribute(): int
    {
        return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
    }

    /**
     * Check if staff has a specific responsibility
     */
    public function hasResponsibility(string $responsibility): bool
    {
        $responsibilities = $this->responsibilities ?? [];
        return in_array($responsibility, $responsibilities);
    }

    /**
     * Check if staff is a supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->supervisees()->exists();
    }

    /**
     * Get count of supervisees
     */
    public function getSuperviseesCount(): int
    {
        return $this->supervisees()->count();
    }

    /**
     * Scope for active staff
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for staff by department
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope for staff by employment type
     */
    public function scopeByEmploymentType($query, string $employmentType)
    {
        return $query->where('employment_type', $employmentType);
    }

    /**
     * Scope for staff by position
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope for supervisors
     */
    public function scopeSupervisors($query)
    {
        return $query->whereHas('supervisees');
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('employee_number', 'like', "%{$search}%")
              ->orWhere('department', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%")
              ->orWhere('job_title', 'like', "%{$search}%");
        });
    }
}
