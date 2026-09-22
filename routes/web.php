<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureHasRole;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
})->name('welcome');

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth', EnsurePasswordIsChanged::class, EnsureHasRole::class])->group(function () {
    // Main Dashboard Route
    Route::get('/dashboard', function () {
        $stats = [
            'total_users' => \App\Models\User::count(),
            'active_users' => \App\Models\User::where('status', 'active')->count(),
            'total_roles' => \App\Models\Role::count(),
            'total_employees' => \App\Models\Employee::count(),
            'total_branches' => \App\Models\Branch::count(),
        ];

        $recent_users = \App\Models\User::with('roles')
            ->latest()
            ->take(5)
            ->get();

        return inertia('Dashboard', [
            'stats' => $stats,
            'recent_users' => $recent_users,
        ]);
    })->name('dashboard')->defaults('description', 'Access main system dashboard');

    // Module landing pages (sub-module cards)
    Route::get('/modules/{module}', function (string $module) {
        $allowed = [
            'inventory', 'sales', 'purchasing', 'workshop', 'customers',
            'suppliers', 'finance', 'vehicle-reference', 'reports', 'system-admin',
        ];
        abort_unless(in_array($module, $allowed), 404);

        return inertia('Modules/Show', ['moduleKey' => $module]);
    })->name('modules.show')->defaults('description', 'View module sub-modules');

    // Force Change Password Routes
    Route::get('password/change', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::put('password/change', [ChangePasswordController::class, 'update'])->name('password.update');

    // User Management Routes (Admin only)
    Route::prefix('auth')->name('auth.')->middleware('admin')->group(function () {
        // Users CRUD
        Route::get('users', [UserController::class, 'index'])
            ->name('users.index')
            ->defaults('description', 'View and manage all system users');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->name('users.show')
            ->defaults('description', 'View user details');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit')
            ->defaults('description', 'Edit user information');
        Route::patch('users/{user}', [UserController::class, 'update'])
            ->name('users.update')
            ->defaults('description', 'Update user information');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy')
            ->defaults('description', 'Delete user account');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status')
            ->defaults('description', 'Activate or deactivate user');

        // Role Bulk Operations & Reports
        Route::get('roles/bulk-assign', [RoleController::class, 'bulkAssign'])->name('roles.bulk-assign');
        Route::post('roles/bulk-assign', [RoleController::class, 'storeBulkAssign'])->name('roles.bulk-assign.store');
        Route::get('roles/bulk-remove', [RoleController::class, 'bulkRemove'])->name('roles.bulk-remove');
        Route::post('roles/bulk-remove', [RoleController::class, 'storeBulkRemove'])->name('roles.bulk-remove.store');
        Route::get('roles/users-report', [RoleController::class, 'usersReport'])->name('roles.users-report');

        // Roles CRUD
        Route::get('roles', [RoleController::class, 'index'])
            ->name('roles.index')
            ->defaults('description', 'View and manage user roles');
        Route::get('roles/create', [RoleController::class, 'create'])
            ->name('roles.create')
            ->defaults('description', 'Create new user role');
        Route::post('roles', [RoleController::class, 'store'])
            ->name('roles.store')
            ->defaults('description', 'Save new role to database');
        Route::get('roles/{role}', [RoleController::class, 'show'])
            ->name('roles.show')
            ->defaults('description', 'View role details and permissions');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
            ->name('roles.edit')
            ->defaults('description', 'Edit role information');
        Route::patch('roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update')
            ->defaults('description', 'Update role information');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])
            ->name('roles.destroy')
            ->defaults('description', 'Delete user role');
    });

    // Auth Management
    Route::get('auth/management', [\App\Http\Controllers\AuthManagementController::class, 'index'])->name('auth.management');
    Route::post('auth/management/{user}/reset', [\App\Http\Controllers\AuthManagementController::class, 'resetUser'])->name('auth.management.reset');
    Route::post('auth/management/{user}/unlock', [\App\Http\Controllers\AuthManagementController::class, 'unlockUser'])->name('auth.management.unlock');
    Route::patch('auth/management/{user}/toggle-status', [\App\Http\Controllers\AuthManagementController::class, 'toggleStatus'])->name('auth.management.toggle-status');

    // Admin Routes (Company, Branches, Departments, Employees)
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        // Configuration Centre
        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])
            ->name('settings.index')
            ->defaults('description', 'Configuration centre — all system settings');
        Route::put('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])
            ->name('settings.update')
            ->defaults('description', 'Save system settings');
        Route::delete('settings/override', [\App\Http\Controllers\Admin\SettingsController::class, 'revert'])
            ->name('settings.revert')
            ->defaults('description', 'Revert a branch setting to the global value');

        // Currencies & Exchange Rates
        Route::get('currencies', [\App\Http\Controllers\Admin\CurrencyController::class, 'index'])
            ->name('currencies.index')
            ->defaults('description', 'Manage currencies and exchange rates');
        Route::post('currencies/rates', [\App\Http\Controllers\Admin\CurrencyController::class, 'storeRate'])
            ->name('currencies.rates.store')
            ->defaults('description', 'Capture a daily exchange rate');
        Route::patch('currencies/{currency}/toggle', [\App\Http\Controllers\Admin\CurrencyController::class, 'toggleActive'])
            ->name('currencies.toggle')
            ->defaults('description', 'Activate or deactivate a currency');

        // Number Sequences
        Route::get('sequences', [\App\Http\Controllers\Admin\NumberSequenceController::class, 'index'])
            ->name('sequences.index')
            ->defaults('description', 'Configure document number sequences');
        Route::patch('sequences/{sequence}', [\App\Http\Controllers\Admin\NumberSequenceController::class, 'update'])
            ->name('sequences.update')
            ->defaults('description', 'Update a document number sequence');

        // Sections
        Route::resource('sections', \App\Http\Controllers\SectionController::class);

        // Company Details
        Route::get('company', [CompanyController::class, 'index'])
            ->name('company.index')
            ->defaults('description', 'View and manage company details');
        Route::post('company', [CompanyController::class, 'store'])
            ->name('company.store')
            ->defaults('description', 'Save company details');
        Route::post('company/logo', [CompanyController::class, 'uploadLogo'])
            ->name('company.logo')
            ->defaults('description', 'Upload company logo');

        // Branches CRUD
        Route::get('branches', [BranchController::class, 'index'])
            ->name('branches.index')
            ->defaults('description', 'View and manage company branches');
        Route::get('branches/create', [BranchController::class, 'create'])
            ->name('branches.create')
            ->defaults('description', 'Create new branch');
        Route::post('branches', [BranchController::class, 'store'])
            ->name('branches.store')
            ->defaults('description', 'Save new branch');
        Route::get('branches/{branch}', [BranchController::class, 'show'])
            ->name('branches.show')
            ->defaults('description', 'View branch details');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])
            ->name('branches.edit')
            ->defaults('description', 'Edit branch information');
        Route::patch('branches/{branch}', [BranchController::class, 'update'])
            ->name('branches.update')
            ->defaults('description', 'Update branch information');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
            ->name('branches.destroy')
            ->defaults('description', 'Delete branch');
        Route::patch('branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])
            ->name('branches.toggle-status')
            ->defaults('description', 'Activate or deactivate branch');

        // Departments CRUD
        Route::get('departments', [DepartmentController::class, 'index'])
            ->name('departments.index')
            ->defaults('description', 'View and manage departments');
        Route::get('departments/create', [DepartmentController::class, 'create'])
            ->name('departments.create')
            ->defaults('description', 'Create new department');
        Route::post('departments', [DepartmentController::class, 'store'])
            ->name('departments.store')
            ->defaults('description', 'Save new department');
        Route::get('departments/{department}', [DepartmentController::class, 'show'])
            ->name('departments.show')
            ->defaults('description', 'View department details');
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->name('departments.edit')
            ->defaults('description', 'Edit department information');
        Route::patch('departments/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update')
            ->defaults('description', 'Update department information');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])
            ->name('departments.destroy')
            ->defaults('description', 'Delete department');
        Route::patch('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])
            ->name('departments.toggle-status')
            ->defaults('description', 'Activate or deactivate department');

        // Employees CRUD
        Route::get('employees', [EmployeeController::class, 'index'])
            ->name('employees.index')
            ->defaults('description', 'View and manage employees');
        Route::get('employees/create', [EmployeeController::class, 'create'])
            ->name('employees.create')
            ->defaults('description', 'Add new employee');
        Route::post('employees', [EmployeeController::class, 'store'])
            ->name('employees.store')
            ->defaults('description', 'Save new employee');
        Route::get('employees/{employee}', [EmployeeController::class, 'show'])
            ->name('employees.show')
            ->defaults('description', 'View employee details');
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])
            ->name('employees.edit')
            ->defaults('description', 'Edit employee information');
        Route::patch('employees/{employee}', [EmployeeController::class, 'update'])
            ->name('employees.update')
            ->defaults('description', 'Update employee information');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
            ->name('employees.destroy')
            ->defaults('description', 'Delete employee');
        Route::patch('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
            ->name('employees.toggle-status')
            ->defaults('description', 'Activate or deactivate employee');
        Route::get('employees/{employee}/create-user', [EmployeeController::class, 'createUserAccount'])
            ->name('employees.create-user')
            ->defaults('description', 'Create user account for employee');
        Route::post('employees/{employee}/create-user', [EmployeeController::class, 'storeUserAccount'])
            ->name('employees.store-user')
            ->defaults('description', 'Save user account for employee');
    });

    Route::get('/settings', function () {
        return inertia('System/Settings');
    })->name('settings.index');

    // System routes
    Route::get('/system/settings', function () {
        return inertia('System/Settings');
    })->name('system.settings')->defaults('description', 'System settings');

    Route::get('/system/logs', function () {
        return inertia('System/Logs');
    })->name('system.logs')->defaults('description', 'View system logs');
});