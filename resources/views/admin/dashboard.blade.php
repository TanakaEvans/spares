@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Welcome back, {{ auth()->user()->name ?? 'Administrator' }}!</h2>
                            <p class="text-muted mb-0">Here's your system overview.</p>
                        </div>
                        <div class="text-right">
                            <div class="text-muted">{{ now()->format('l, F j, Y') }}</div>
                            <div class="h5 mb-0">{{ now()->format('g:i A') }}</div>
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
            </div>
            <div class="stat-value">{{ \App\Models\User::count() }}</div>
            <div class="stat-label">Total Users</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon success">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
            <div class="stat-value">{{ \App\Models\Role::count() }}</div>
            <div class="stat-label">Roles</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon info">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            <div class="stat-value">{{ \App\Models\Branch::count() }}</div>
            <div class="stat-label">Branches</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon warning">
                    <i class="fas fa-users-gear"></i>
                </div>
            </div>
            <div class="stat-value">{{ \App\Models\Employee::count() }}</div>
            <div class="stat-label">Employees</div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Users</h3>
                    <p class="card-subtitle">Latest registered system users</p>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        @php
                            $recentUsers = \App\Models\User::with('roles')->latest()->take(5)->get();
                        @endphp
                        @foreach($recentUsers as $user)
                        <div class="activity-item">
                            <div class="activity-icon success">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">{{ $user->name }}</div>
                                <div class="activity-description">{{ $user->email }}</div>
                                <div class="activity-time">{{ $user->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                    <p class="card-subtitle">Frequently used actions</p>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="{{ route('auth.users.index') }}" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-users"></i>
                            Manage Users
                        </a>
                        <a href="{{ route('auth.roles.index') }}" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-shield-alt"></i>
                            Manage Roles
                        </a>
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-code-branch"></i>
                            Manage Branches
                        </a>
                        <a href="{{ route('system.settings') }}" class="btn btn-warning btn-block">
                            <i class="fas fa-cog"></i>
                            System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
.activity-icon.info { background: rgba(6, 182, 212, 0.1); color: var(--info-color); }
.activity-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.activity-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.btn-block {
    display: block;
    width: 100%;
}
</style>
@endpush
@endsection