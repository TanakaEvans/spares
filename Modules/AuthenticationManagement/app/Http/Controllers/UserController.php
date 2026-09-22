<?php

namespace Modules\AuthenticationManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Modules\AuthenticationManagement\Models\User;
use Modules\AuthenticationManagement\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        $users = $query->paginate(15);
        $roles = Role::all();
        
        return view('authenticationmanagement::users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        return view('authenticationmanagement::users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:auth_users'],
            'username' => ['required', 'string', 'max:100', 'unique:auth_users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'status' => ['required', 'in:active,inactive'],
            'roles' => ['array'],
            'roles.*' => ['exists:auth_roles,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        // Assign roles
        if ($request->roles) {
            $user->roles()->attach($request->roles, [
                'assigned_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('auth.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'studentProfile', 'teacherProfile', 'parentProfile', 'staffProfile']);
        return view('authenticationmanagement::users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $user->load('roles');
        return view('authenticationmanagement::users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:auth_users,email,' . $user->id],
            'username' => ['required', 'string', 'max:100', 'unique:auth_users,username,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'status' => ['required', 'in:active,inactive'],
            'roles' => ['array'],
            'roles.*' => ['exists:auth_roles,id'],
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Sync roles
        if ($request->has('roles')) {
            $rolesWithPivot = [];
            foreach ($request->roles as $roleId) {
                $rolesWithPivot[$roleId] = [
                    'assigned_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $user->roles()->sync($rolesWithPivot);
        }

        return redirect()->route('auth.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deletion of the current user
        if ($user->id === auth()->id()) {
            return redirect()->route('auth.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('auth.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role_id' => ['required', 'exists:auth_roles,id'],
        ]);

        $role = Role::find($request->role_id);
        
        if (!$user->hasRole($role->name)) {
            $user->assignRole($role->name, auth()->id());
            return response()->json(['message' => 'Role assigned successfully']);
        }

        return response()->json(['message' => 'User already has this role'], 400);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(User $user, Role $role)
    {
        if ($user->hasRole($role->name)) {
            $user->removeRole($role->name);
            return response()->json(['message' => 'Role removed successfully']);
        }

        return response()->json(['message' => 'User does not have this role'], 400);
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'message' => "User status changed to {$newStatus}",
            'status' => $newStatus
        ]);
    }
}
