<?php

namespace Modules\AuthenticationManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParentProfile extends Model
{
    use HasFactory;

    protected $table = 'auth_parent_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'phone_number',
        'work_phone',
        'home_address',
        'work_address',
        'occupation',
        'employer',
        'relationship_type',
        'status',
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
        ];
    }

    /**
     * Get the user that owns the parent profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students associated with this parent.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            StudentProfile::class,
            'auth_parent_student_relationships',
            'parent_id',
            'student_id'
        )->withPivot([
            'relationship_type',
            'is_primary_contact',
            'has_pickup_permission',
            'receives_notifications'
        ])->withTimestamps();
    }

    /**
     * Get students where this parent is the primary contact
     */
    public function primaryStudents()
    {
        return $this->students()->wherePivot('is_primary_contact', true);
    }

    /**
     * Get students this parent can pick up
     */
    public function studentsWithPickupPermission()
    {
        return $this->students()->wherePivot('has_pickup_permission', true);
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
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->diffInYears(now()) : null;
    }

    /**
     * Check if parent is primary contact for any student
     */
    public function isPrimaryContactForAnyStudent(): bool
    {
        return $this->students()->wherePivot('is_primary_contact', true)->exists();
    }

    /**
     * Check if parent has pickup permission for specific student
     */
    public function hasPickupPermissionFor(int $studentId): bool
    {
        return $this->students()
                    ->wherePivot('student_id', $studentId)
                    ->wherePivot('has_pickup_permission', true)
                    ->exists();
    }

    /**
     * Check if parent receives notifications for specific student
     */
    public function receivesNotificationsFor(int $studentId): bool
    {
        return $this->students()
                    ->wherePivot('student_id', $studentId)
                    ->wherePivot('receives_notifications', true)
                    ->exists();
    }

    /**
     * Scope for active parents
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for parents by relationship type
     */
    public function scopeByRelationshipType($query, string $relationshipType)
    {
        return $query->where('relationship_type', $relationshipType);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%")
              ->orWhere('occupation', 'like', "%{$search}%")
              ->orWhere('employer', 'like', "%{$search}%");
        });
    }
}
