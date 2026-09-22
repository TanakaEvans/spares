@extends('layouts.admin')

@section('title', 'Authentication Management')
@section('page-title', 'Authentication & Role Management')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Authentication & Role Management</h2>
                            <p class="text-muted mb-0">Manage user accounts, roles, and access permissions across the school portal.</p>
                        </div>
                        <div class="text-right">
                            <a href="{{ route('auth.users.create') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Add New User
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +5%
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_users']) }}</div>
            <div class="stat-label">Total Users</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +3%
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['active_users']) }}</div>
            <div class="stat-label">Active Users</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon info">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            <div class="stat-value">{{ $stats['total_roles'] }}</div>
            <div class="stat-label">System Roles</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon warning">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['students_count']) }}</div>
            <div class="stat-label">Students</div>
        </div>
    </div>

    <!-- Profile Type Stats -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon info">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['teachers_count']) }}</div>
            <div class="stat-label">Teachers</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon success">
                    <i class="fas fa-user-friends"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['parents_count']) }}</div>
            <div class="stat-label">Parents</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon primary">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['staff_count']) }}</div>
            <div class="stat-label">Staff Members</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon danger">
                    <i class="fas fa-user-times"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_users'] - $stats['active_users']) }}</div>
            <div class="stat-label">Inactive Users</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row">
        <!-- Management Modules -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Management Modules</h3>
                    <p class="card-subtitle">Access different user and profile management areas</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Management -->
                        <div class="col-md-6 mb-3">
                            <div class="module-card" data-href="{{ route('auth.users.index') }}">
                                <div class="module-card-icon primary">
                                    <i class="fas fa-users-cog"></i>
                                </div>
                                <h4 class="module-card-title">User Management</h4>
                                <p class="module-card-description">
                                    Manage system users, credentials, and account status.
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">{{ $stats['total_users'] }} users</small>
                                </div>
                            </div>
                        </div>

                        <!-- Role Management -->
                        <div class="col-md-6 mb-3">
                            <div class="module-card" data-href="{{ route('auth.roles.index') }}">
                                <div class="module-card-icon success">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <h4 class="module-card-title">Role Management</h4>
                                <p class="module-card-description">
                                    Define and manage user roles and permissions.
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">{{ $stats['total_roles'] }} roles</small>
                                </div>
                            </div>
                        </div>

                        <!-- Student Profiles -->
                        <div class="col-md-6 mb-3">
                            <div class="module-card" data-href="{{ route('auth.students.index') }}">
                                <div class="module-card-icon info">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h4 class="module-card-title">Student Profiles</h4>
                                <p class="module-card-description">
                                    Manage student information and academic details.
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">{{ $stats['students_count'] }} students</small>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher Profiles -->
                        <div class="col-md-6 mb-3">
                            <div class="module-card" data-href="{{ route('auth.teachers.index') }}">
                                <div class="module-card-icon warning">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <h4 class="module-card-title">Teacher Profiles</h4>
                                <p class="module-card-description">
                                    Manage teacher information and assignments.
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">{{ $stats['teachers_count'] }} teachers</small>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Profiles -->
                        <div class="col-md-6 mb-3">
                            <div class="module-card" data-href="{{ route('auth.parents.index') }}">
                                <div class="module-card-icon success">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <h4 class="module-card-title">Parent Profiles</h4>
                                <p class="module-card-description">
                                    Manage parent information and student relationships.
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">{{ $stats['parents_count'] }} parents</small>
                                </div>
                            </div>
                        </div>

                        <!-- Staff Profiles -->
                        <div class="col-md-6 mb-3">
                            <div class="module-card" data-href="{{ route('auth.staff.index') }}">
                                <div class="module-card-icon primary">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h4 class="module-card-title">Staff Profiles</h4>
                                <p class="module-card-description">
                                    Manage administrative and support staff information.
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">{{ $stats['staff_count'] }} staff</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                    <p class="card-subtitle">Common tasks</p>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="{{ route('auth.users.create') }}" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-user-plus"></i>
                            Add New User
                        </a>
                        <a href="{{ route('auth.roles.create') }}" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-shield-alt"></i>
                            Create Role
                        </a>
                        <a href="{{ route('auth.students.create') }}" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-user-graduate"></i>
                            Add Student
                        </a>
                        <a href="{{ route('auth.teachers.create') }}" class="btn btn-warning btn-block">
                            <i class="fas fa-chalkboard-teacher"></i>
                            Add Teacher
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Users</h3>
                    <p class="card-subtitle">Latest registrations</p>
                </div>
                <div class="card-body">
                    @if($recent_users->count() > 0)
                        <div class="recent-users-list">
                            @foreach($recent_users as $user)
                                <div class="recent-user-item">
                                    <div class="user-avatar">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name">{{ $user->name }}</div>
                                        <div class="user-email">{{ $user->email }}</div>
                                        <div class="user-roles">
                                            @foreach($user->roles as $role)
                                                <span class="badge badge-primary">{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="user-status">
                                        <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center">No recent users found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.recent-user-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.recent-user-item:last-child {
    border-bottom: none;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 1rem;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.user-email {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.user-roles .badge {
    font-size: 0.6875rem;
    margin-right: 0.25rem;
}

.user-status {
    margin-left: 1rem;
}
</style>
@endpush
@endsection
