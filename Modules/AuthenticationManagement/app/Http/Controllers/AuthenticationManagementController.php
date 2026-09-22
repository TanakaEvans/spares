<?php

namespace Modules\AuthenticationManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AuthenticationManagement\Models\User;
use Modules\AuthenticationManagement\Models\Role;
use Modules\AuthenticationManagement\Models\StudentProfile;
use Modules\AuthenticationManagement\Models\TeacherProfile;
use Modules\AuthenticationManagement\Models\ParentProfile;
use Modules\AuthenticationManagement\Models\StaffProfile;

class AuthenticationManagementController extends Controller
{
    /**
     * Display the Authentication Management dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_roles' => Role::count(),
            'students_count' => StudentProfile::where('status', 'active')->count(),
            'teachers_count' => TeacherProfile::where('status', 'active')->count(),
            'parents_count' => ParentProfile::where('status', 'active')->count(),
            'staff_count' => StaffProfile::where('status', 'active')->count(),
        ];

        $recent_users = User::with('roles')
            ->latest()
            ->take(5)
            ->get();

        $user_growth = $this->getUserGrowthData();
        $role_distribution = $this->getRoleDistribution();

        return view('authenticationmanagement::dashboard', compact(
            'stats',
            'recent_users',
            'user_growth',
            'role_distribution'
        ));
    }

    /**
     * Get user growth data for the chart
     */
    private function getUserGrowthData()
    {
        $months = [];
        $data = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');
            $data[] = User::whereYear('created_at', $month->year)
                         ->whereMonth('created_at', $month->month)
                         ->count();
        }

        return [
            'labels' => $months,
            'data' => $data
        ];
    }

    /**
     * Get role distribution data
     */
    private function getRoleDistribution()
    {
        return Role::withCount('users')
            ->get()
            ->map(function ($role) {
                return [
                    'name' => $role->name,
                    'count' => $role->users_count,
                    'percentage' => $role->users_count > 0 ? 
                        round(($role->users_count / User::count()) * 100, 1) : 0
                ];
            });
    }
