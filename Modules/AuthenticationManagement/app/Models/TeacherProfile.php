<?php

namespace Modules\AuthenticationManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    use HasFactory;

    protected $table = 'auth_teacher_profiles';

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
        'qualification',
        'specialization',
        'hire_date',
        'employment_type',
        'salary',
        'status',
        'subjects_taught',
        'classes_assigned',
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
            'subjects_taught' => 'array',
            'classes_assigned' => 'array',
        ];
    }

    /**
     * Get the user that owns the teacher profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Check if teacher teaches a specific subject
     */
    public function teachesSubject(string $subject): bool
    {
        $subjects = $this->subjects_taught ?? [];
        return in_array($subject, $subjects);
    }

    /**
     * Check if teacher is assigned to a specific class
     */
    public function isAssignedToClass(string $classLevel, ?string $section = null): bool
    {
        $classes = $this->classes_assigned ?? [];
        
        foreach ($classes as $class) {
            if (isset($class['level']) && $class['level'] === $classLevel) {
                if ($section === null || (isset($class['section']) && $class['section'] === $section)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Scope for active teachers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for teachers by employment type
     */
    public function scopeByEmploymentType($query, string $employmentType)
    {
        return $query->where('employment_type', $employmentType);
    }

    /**
     * Scope for teachers by subject
     */
    public function scopeBySubject($query, string $subject)
    {
        return $query->whereJsonContains('subjects_taught', $subject);
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
              ->orWhere('qualification', 'like', "%{$search}%")
              ->orWhere('specialization', 'like', "%{$search}%");
        });
    }
}
