<?php

namespace Modules\AuthenticationManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'auth_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'auth_user_roles', 'user_id', 'role_id')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }

    /**
     * Get the student profile associated with the user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get the teacher profile associated with the user.
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * Get the parent profile associated with the user.
     */
    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    /**
     * Get the staff profile associated with the user.
     */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all specified roles
     */
    public function hasAllRoles(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->count() === count($roles);
    }

    /**
     * Assign a role to the user
     */
    public function assignRole(string $roleName, ?int $assignedBy = null): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id, [
                'assigned_by' => $assignedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Get user's primary profile based on their role
     */
    public function getPrimaryProfile()
    {
        if ($this->hasRole('Student')) {
            return $this->studentProfile;
        }
        
        if ($this->hasRole('Teacher')) {
            return $this->teacherProfile;
        }
        
        if ($this->hasRole('Parent')) {
            return $this->parentProfile;
        }
        
        if ($this->hasAnyRole(['Staff', 'Admin'])) {
            return $this->staffProfile;
        }
        
        return null;
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for users by role
     */
    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }
}
