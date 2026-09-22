<?php

namespace Modules\AuthenticationManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $table = 'auth_student_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'phone_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'class_level',
        'section',
        'enrollment_date',
        'status',
        'medical_conditions',
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
            'enrollment_date' => 'date',
            'medical_conditions' => 'array',
        ];
    }

    /**
     * Get the user that owns the student profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parents associated with this student.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            ParentProfile::class,
            'auth_parent_student_relationships',
            'student_id',
            'parent_id'
        )->withPivot([
            'relationship_type',
            'is_primary_contact',
            'has_pickup_permission',
            'receives_notifications'
        ])->withTimestamps();
    }

    /**
     * Get the primary contact parent
     */
    public function primaryContact()
    {
        return $this->parents()->wherePivot('is_primary_contact', true)->first();
    }

    /**
     * Get parents with pickup permission
     */
    public function parentsWithPickupPermission()
    {
        return $this->parents()->wherePivot('has_pickup_permission', true);
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
     * Scope for active students
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for students by class
     */
    public function scopeByClass($query, string $classLevel, ?string $section = null)
    {
        $query = $query->where('class_level', $classLevel);
        
        if ($section) {
            $query->where('section', $section);
        }
        
        return $query;
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('student_number', 'like', "%{$search}%");
        });
    }
}
