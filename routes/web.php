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

        // Document proof (print pipeline verification)
        Route::get('print/proof', \App\Http\Controllers\Admin\PrintProofController::class)
            ->name('print.proof')
            ->defaults('description', 'Render the document pipeline proof PDF');

        // System Health & Integrity dashboard
        Route::get('health', [\App\Http\Controllers\Admin\HealthController::class, 'index'])
            ->name('health')
            ->defaults('description', 'System health & integrity dashboard');
        Route::post('health/post-opening-stock', [\App\Http\Controllers\Admin\HealthController::class, 'postOpeningStock'])
            ->name('health.post-opening-stock')
            ->defaults('description', 'Post the opening inventory balance to the GL');

        // Password policy, comms, print templates, notifications, backups
        Route::get('password-policy', [\App\Http\Controllers\Admin\PasswordPolicyController::class, 'index'])->name('password-policy.index')->defaults('description', 'Password policy');
        Route::put('password-policy', [\App\Http\Controllers\Admin\PasswordPolicyController::class, 'update'])->name('password-policy.update')->defaults('description', 'Update password policy');
        Route::get('comms', [\App\Http\Controllers\Admin\CommsController::class, 'index'])->name('comms.index')->defaults('description', 'Email & SMS settings');
        Route::put('comms', [\App\Http\Controllers\Admin\CommsController::class, 'update'])->name('comms.update')->defaults('description', 'Update comms settings');
        Route::get('print-templates', [\App\Http\Controllers\Admin\PrintTemplateController::class, 'index'])->name('print-templates.index')->defaults('description', 'Print templates');
        Route::put('print-templates', [\App\Http\Controllers\Admin\PrintTemplateController::class, 'update'])->name('print-templates.update')->defaults('description', 'Update print templates');
        Route::get('notifications-centre', [\App\Http\Controllers\Admin\NotificationsController::class, 'index'])->name('notifications-centre.index')->defaults('description', 'Notifications centre');
        Route::get('backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('backups.index')->defaults('description', 'Backup management');
        Route::post('backups', [\App\Http\Controllers\Admin\BackupController::class, 'store'])->name('backups.store')->defaults('description', 'Create a backup');

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

    // Legacy settings routes → the real Configuration Centre.
    Route::get('/settings', fn () => redirect()->route('admin.settings.index'))->name('settings.index');
    Route::get('/system/settings', fn () => redirect()->route('admin.settings.index'))
        ->name('system.settings')->defaults('description', 'System settings');

    // Activity log — audited trail of posted financial events.
    Route::get('/system/logs', [\App\Http\Controllers\SystemController::class, 'logs'])
        ->name('system.logs')->defaults('description', 'Activity log');

    // ── Vehicle Reference module ─────────────────────────────────────────
    Route::prefix('vehicle-ref')->name('vehicle-ref.')->group(function () {
        Route::get('fitment', \App\Http\Controllers\VehicleRef\FitmentLookupController::class)
            ->name('fitment')->defaults('description', 'Find every part that fits a vehicle');
        Route::get('cross-ref', \App\Http\Controllers\VehicleRef\CrossReferenceController::class)
            ->name('cross-ref')->defaults('description', 'Search any part number across all references');

        Route::get('makes', [\App\Http\Controllers\VehicleRef\VehicleMakeController::class, 'index'])->name('makes.index');
        Route::post('makes', [\App\Http\Controllers\VehicleRef\VehicleMakeController::class, 'store'])->name('makes.store');
        Route::patch('makes/{make}', [\App\Http\Controllers\VehicleRef\VehicleMakeController::class, 'update'])->name('makes.update');

        Route::get('models', [\App\Http\Controllers\VehicleRef\VehicleModelController::class, 'index'])->name('models.index');
        Route::post('models', [\App\Http\Controllers\VehicleRef\VehicleModelController::class, 'store'])->name('models.store');
        Route::get('models/{model}', [\App\Http\Controllers\VehicleRef\VehicleModelController::class, 'show'])->name('models.show');
        Route::post('models/{model}/variants', [\App\Http\Controllers\VehicleRef\VehicleModelController::class, 'storeVariant'])->name('models.variants.store');

        Route::get('engines', [\App\Http\Controllers\VehicleRef\EngineCodeController::class, 'index'])->name('engines.index');
        Route::post('engines', [\App\Http\Controllers\VehicleRef\EngineCodeController::class, 'store'])->name('engines.store');

        Route::get('supersessions', [\App\Http\Controllers\VehicleRef\SupersessionController::class, 'index'])->name('supersessions.index')->defaults('description', 'Part supersessions');
        Route::get('bulletins', [\App\Http\Controllers\VehicleRef\BulletinController::class, 'index'])->name('bulletins.index')->defaults('description', 'Technical bulletins');
        Route::post('bulletins', [\App\Http\Controllers\VehicleRef\BulletinController::class, 'store'])->name('bulletins.store')->defaults('description', 'Publish a bulletin');
    });

    // ── Inventory module ─────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('parts', [\App\Http\Controllers\Inventory\PartController::class, 'index'])->name('parts.index');
        Route::get('parts/create', [\App\Http\Controllers\Inventory\PartController::class, 'create'])->name('parts.create');
        Route::post('parts', [\App\Http\Controllers\Inventory\PartController::class, 'store'])->name('parts.store');
        Route::get('parts/{part}', [\App\Http\Controllers\Inventory\PartController::class, 'show'])->name('parts.show');
        Route::get('parts/{part}/edit', [\App\Http\Controllers\Inventory\PartController::class, 'edit'])->name('parts.edit');
        Route::patch('parts/{part}', [\App\Http\Controllers\Inventory\PartController::class, 'update'])->name('parts.update');

        Route::post('parts/{part}/cross-references', [\App\Http\Controllers\Inventory\PartRelationController::class, 'storeCrossReference'])->name('parts.crossrefs.store');
        Route::delete('parts/{part}/cross-references/{crossReference}', [\App\Http\Controllers\Inventory\PartRelationController::class, 'destroyCrossReference'])->name('parts.crossrefs.destroy');
        Route::post('parts/{part}/fitments', [\App\Http\Controllers\Inventory\PartRelationController::class, 'storeFitment'])->name('parts.fitments.store');
        Route::delete('parts/{part}/fitments/{fitment}', [\App\Http\Controllers\Inventory\PartRelationController::class, 'destroyFitment'])->name('parts.fitments.destroy');
        Route::post('parts/{part}/supersession', [\App\Http\Controllers\Inventory\PartRelationController::class, 'storeSupersession'])->name('parts.supersession.store');

        Route::get('categories', [\App\Http\Controllers\Inventory\PartCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [\App\Http\Controllers\Inventory\PartCategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [\App\Http\Controllers\Inventory\PartCategoryController::class, 'update'])->name('categories.update');

        Route::get('brands', [\App\Http\Controllers\Inventory\PartBrandController::class, 'index'])->name('brands.index');
        Route::post('brands', [\App\Http\Controllers\Inventory\PartBrandController::class, 'store'])->name('brands.store');

        Route::get('bins', [\App\Http\Controllers\Inventory\BinLocationController::class, 'index'])->name('bins.index');
        Route::post('bins', [\App\Http\Controllers\Inventory\BinLocationController::class, 'store'])->name('bins.store');

        Route::get('stock', [\App\Http\Controllers\Inventory\StockLevelController::class, 'index'])->name('stock.index');
        Route::patch('stock/{level}/reorder-levels', [\App\Http\Controllers\Inventory\StockMovementController::class, 'updateReorderLevels'])->name('stock.reorder-levels');

        Route::get('adjustments', [\App\Http\Controllers\Inventory\StockMovementController::class, 'adjustments'])->name('adjustments.index');
        Route::post('adjustments', [\App\Http\Controllers\Inventory\StockMovementController::class, 'storeAdjustment'])->name('adjustments.store');

        Route::get('transfers', [\App\Http\Controllers\Inventory\StockMovementController::class, 'transfers'])->name('transfers.index');
        Route::post('transfers', [\App\Http\Controllers\Inventory\StockMovementController::class, 'storeTransfer'])->name('transfers.store');
        Route::post('transfers/{transfer}/dispatch', [\App\Http\Controllers\Inventory\StockMovementController::class, 'dispatchTransfer'])->name('transfers.dispatch');
        Route::post('transfers/{transfer}/receive', [\App\Http\Controllers\Inventory\StockMovementController::class, 'receiveTransfer'])->name('transfers.receive');

        Route::get('reorder', [\App\Http\Controllers\Inventory\StockMovementController::class, 'reorder'])->name('reorder.index');
        Route::post('reorder/create-pos', [\App\Http\Controllers\Inventory\StockMovementController::class, 'createReorderPos'])->name('reorder.create-pos');
        Route::post('reorder/preferred-supplier', [\App\Http\Controllers\Inventory\StockMovementController::class, 'setPreferredSupplier'])->name('reorder.preferred-supplier');

        Route::get('stock-takes', [\App\Http\Controllers\Inventory\StockTakeController::class, 'index'])->name('stock-takes.index')->defaults('description', 'Stock takes — full and spot counts');
        Route::get('stock-takes/create', [\App\Http\Controllers\Inventory\StockTakeController::class, 'create'])->name('stock-takes.create')->defaults('description', 'Start a new stock take');
        Route::post('stock-takes', [\App\Http\Controllers\Inventory\StockTakeController::class, 'store'])->name('stock-takes.store')->defaults('description', 'Snapshot stock and begin counting');
        Route::get('stock-takes/{stockTake}', [\App\Http\Controllers\Inventory\StockTakeController::class, 'show'])->name('stock-takes.show')->defaults('description', 'Count and review a stock take');
        Route::patch('stock-takes/{stockTake}/lines/{line}', [\App\Http\Controllers\Inventory\StockTakeController::class, 'recordCount'])->name('stock-takes.count')->defaults('description', 'Record a counted quantity');
        Route::post('stock-takes/{stockTake}/review', [\App\Http\Controllers\Inventory\StockTakeController::class, 'review'])->name('stock-takes.review')->defaults('description', 'Lock counts for review');
        Route::post('stock-takes/{stockTake}/post', [\App\Http\Controllers\Inventory\StockTakeController::class, 'post'])->name('stock-takes.post')->defaults('description', 'Post the variance as an adjustment');

        Route::get('serials', [\App\Http\Controllers\Inventory\SerialNumberController::class, 'index'])->name('serials.index')->defaults('description', 'Serial & batch tracking');
        Route::get('serials/part-lookup', [\App\Http\Controllers\Inventory\SerialNumberController::class, 'partLookup'])->name('serials.part-lookup')->defaults('description', 'Part lookup for serials');
        Route::post('serials', [\App\Http\Controllers\Inventory\SerialNumberController::class, 'store'])->name('serials.store')->defaults('description', 'Record a serial number');
    });

    // ── Suppliers module ─────────────────────────────────────────────────
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Suppliers\SupplierController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\Suppliers\SupplierController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Suppliers\SupplierController::class, 'store'])->name('store');
        Route::get('{supplier}', [\App\Http\Controllers\Suppliers\SupplierController::class, 'show'])->name('show');
        Route::get('{supplier}/edit', [\App\Http\Controllers\Suppliers\SupplierController::class, 'edit'])->name('edit');
        Route::patch('{supplier}', [\App\Http\Controllers\Suppliers\SupplierController::class, 'update'])->name('update');
        Route::post('{supplier}/contacts', [\App\Http\Controllers\Suppliers\SupplierController::class, 'storeContact'])->name('contacts.store');

        Route::get('{supplier}/price-lists/import', [\App\Http\Controllers\Suppliers\SupplierPriceListController::class, 'import'])->name('pricelists.import');
        Route::post('{supplier}/price-lists/preview', [\App\Http\Controllers\Suppliers\SupplierPriceListController::class, 'preview'])->name('pricelists.preview');
        Route::post('{supplier}/price-lists', [\App\Http\Controllers\Suppliers\SupplierPriceListController::class, 'store'])->name('pricelists.store');
        Route::post('{supplier}/price-lists/{priceList}/activate', [\App\Http\Controllers\Suppliers\SupplierPriceListController::class, 'activate'])->name('pricelists.activate');

        Route::get('lists/approved', [\App\Http\Controllers\Suppliers\ApprovedSupplierController::class, 'index'])->name('approved.index')->defaults('description', 'Approved supplier list');
        Route::get('lists/contacts', [\App\Http\Controllers\Suppliers\SupplierContactController::class, 'index'])->name('contacts.index')->defaults('description', 'All supplier contacts');
        Route::get('lists/performance', [\App\Http\Controllers\Suppliers\SupplierPerformanceController::class, 'index'])->name('performance.index')->defaults('description', 'Supplier performance');
    });

    // ── Purchasing module ────────────────────────────────────────────────
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('orders', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/create', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'create'])->name('orders.create');
        Route::get('orders/part-lookup', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'partLookup'])->name('orders.part-lookup');
        Route::post('orders', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/transition', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'transition'])->name('orders.transition');
        Route::get('orders/{order}/print', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class, 'print'])->name('orders.print');

        Route::get('grns', [\App\Http\Controllers\Purchasing\GrnController::class, 'index'])->name('grns.index');
        Route::get('orders/{order}/receive', [\App\Http\Controllers\Purchasing\GrnController::class, 'create'])->name('grns.create');
        Route::post('orders/{order}/receive', [\App\Http\Controllers\Purchasing\GrnController::class, 'store'])->name('grns.store');

        Route::get('invoices', [\App\Http\Controllers\Purchasing\SupplierInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('grns/{grn}/invoice', [\App\Http\Controllers\Purchasing\SupplierInvoiceController::class, 'create'])->name('invoices.create');
        Route::post('grns/{grn}/invoice', [\App\Http\Controllers\Purchasing\SupplierInvoiceController::class, 'store'])->name('invoices.store');
        Route::post('invoices/{invoice}/post', [\App\Http\Controllers\Purchasing\SupplierInvoiceController::class, 'post'])->name('invoices.post');

        Route::get('returns', [\App\Http\Controllers\Purchasing\SupplierReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/create', [\App\Http\Controllers\Purchasing\SupplierReturnController::class, 'create'])->name('returns.create');
        Route::post('returns', [\App\Http\Controllers\Purchasing\SupplierReturnController::class, 'store'])->name('returns.store');
        Route::post('returns/{return}/ship', [\App\Http\Controllers\Purchasing\SupplierReturnController::class, 'ship'])->name('returns.ship');
        Route::post('returns/{return}/credit', [\App\Http\Controllers\Purchasing\SupplierReturnController::class, 'credit'])->name('returns.credit');

        Route::get('supplier-credits', [\App\Http\Controllers\Purchasing\SupplierCreditController::class, 'index'])->name('supplier-credits.index')->defaults('description', 'Supplier credit notes');

        Route::get('imports', [\App\Http\Controllers\Purchasing\ImportShipmentController::class, 'index'])->name('imports.index')->defaults('description', 'Import shipments');
        Route::post('imports', [\App\Http\Controllers\Purchasing\ImportShipmentController::class, 'store'])->name('imports.store')->defaults('description', 'Create an import shipment');
        Route::post('imports/{shipment}/transition', [\App\Http\Controllers\Purchasing\ImportShipmentController::class, 'transition'])->name('imports.transition')->defaults('description', 'Update shipment status');
        Route::get('price-comparison', [\App\Http\Controllers\Purchasing\PriceComparisonController::class, 'index'])->name('price-comparison.index')->defaults('description', 'Supplier price comparison');
    });

    // ── Customers module ─────────────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Customers\CustomerController::class, 'index'])->name('index')->defaults('description', 'Customer directory');
        Route::get('groups', [\App\Http\Controllers\Customers\CustomerGroupController::class, 'index'])->name('groups.index')->defaults('description', 'Customer groups and their price lists');
        Route::post('groups', [\App\Http\Controllers\Customers\CustomerGroupController::class, 'store'])->name('groups.store')->defaults('description', 'Create a customer group');
        Route::patch('groups/{group}', [\App\Http\Controllers\Customers\CustomerGroupController::class, 'update'])->name('groups.update')->defaults('description', 'Update a customer group');
        Route::get('create', [\App\Http\Controllers\Customers\CustomerController::class, 'create'])->name('create')->defaults('description', 'Add a customer');
        Route::post('/', [\App\Http\Controllers\Customers\CustomerController::class, 'store'])->name('store')->defaults('description', 'Save a new customer');
        Route::get('{customer}', [\App\Http\Controllers\Customers\CustomerController::class, 'show'])->name('show')->defaults('description', 'Customer detail and account');
        Route::get('{customer}/statement', [\App\Http\Controllers\Customers\CustomerController::class, 'statement'])->name('statement')->defaults('description', 'Print a customer account statement');
        Route::get('{customer}/edit', [\App\Http\Controllers\Customers\CustomerController::class, 'edit'])->name('edit')->defaults('description', 'Edit a customer');
        Route::patch('{customer}', [\App\Http\Controllers\Customers\CustomerController::class, 'update'])->name('update')->defaults('description', 'Update a customer');
        Route::post('{customer}/hold', [\App\Http\Controllers\Customers\CustomerController::class, 'toggleHold'])->name('hold')->defaults('description', 'Place or release a credit hold');

        Route::get('comms/log', [\App\Http\Controllers\Customers\CustomerNoteController::class, 'index'])->name('comms.index')->defaults('description', 'Communication log');
        Route::post('comms/log', [\App\Http\Controllers\Customers\CustomerNoteController::class, 'store'])->name('comms.store')->defaults('description', 'Log a communication');
        Route::get('loyalty/programme', [\App\Http\Controllers\Customers\LoyaltyController::class, 'index'])->name('loyalty.index')->defaults('description', 'Loyalty programme');
    });

    // ── Sales & POS module ───────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {
        // Point of sale
        Route::get('pos', [\App\Http\Controllers\Sales\PointOfSaleController::class, 'create'])->name('pos')->defaults('description', 'Counter sales till');
        Route::get('pos/part-lookup', [\App\Http\Controllers\Sales\PointOfSaleController::class, 'partLookup'])->name('pos.part-lookup')->defaults('description', 'Till part search');
        Route::get('pos/customer-lookup', [\App\Http\Controllers\Sales\PointOfSaleController::class, 'customerLookup'])->name('pos.customer-lookup')->defaults('description', 'Till customer search');
        Route::post('pos', [\App\Http\Controllers\Sales\PointOfSaleController::class, 'store'])->name('pos.store')->defaults('description', 'Post a counter sale');

        // Invoices
        Route::get('invoices', [\App\Http\Controllers\Sales\InvoiceController::class, 'index'])->name('invoices.index')->defaults('description', 'Tax invoices');
        Route::get('invoices/{invoice}', [\App\Http\Controllers\Sales\InvoiceController::class, 'show'])->name('invoices.show')->defaults('description', 'Invoice detail');
        Route::get('invoices/{invoice}/print', [\App\Http\Controllers\Sales\InvoiceController::class, 'print'])->name('invoices.print')->defaults('description', 'Print an A4 tax invoice');
        Route::get('invoices/{invoice}/receipt', [\App\Http\Controllers\Sales\InvoiceController::class, 'receipt'])->name('invoices.receipt')->defaults('description', 'Print an 80mm receipt');

        // Credit notes
        Route::get('credit-notes', [\App\Http\Controllers\Sales\CreditNoteController::class, 'index'])->name('credit-notes.index')->defaults('description', 'Credit notes and returns');
        Route::get('invoices/{invoice}/credit', [\App\Http\Controllers\Sales\CreditNoteController::class, 'create'])->name('credit-notes.create')->defaults('description', 'Raise a credit note');
        Route::post('invoices/{invoice}/credit', [\App\Http\Controllers\Sales\CreditNoteController::class, 'store'])->name('credit-notes.store')->defaults('description', 'Post a credit note');
        Route::get('credit-notes/{creditNote}', [\App\Http\Controllers\Sales\CreditNoteController::class, 'show'])->name('credit-notes.show')->defaults('description', 'Credit note detail');
        Route::get('credit-notes/{creditNote}/print', [\App\Http\Controllers\Sales\CreditNoteController::class, 'print'])->name('credit-notes.print')->defaults('description', 'Print a credit note');

        // Quotations
        Route::get('quotes', [\App\Http\Controllers\Sales\QuoteController::class, 'index'])->name('quotes.index')->defaults('description', 'Quotations');
        Route::get('quotes/create', [\App\Http\Controllers\Sales\QuoteController::class, 'create'])->name('quotes.create')->defaults('description', 'New quotation');
        Route::post('quotes', [\App\Http\Controllers\Sales\QuoteController::class, 'store'])->name('quotes.store')->defaults('description', 'Save a quotation');
        Route::get('quotes/{quote}', [\App\Http\Controllers\Sales\QuoteController::class, 'show'])->name('quotes.show')->defaults('description', 'Quotation detail');
        Route::post('quotes/{quote}/convert', [\App\Http\Controllers\Sales\QuoteController::class, 'convert'])->name('quotes.convert')->defaults('description', 'Convert a quote to an order');
        Route::get('quotes/{quote}/print', [\App\Http\Controllers\Sales\QuoteController::class, 'print'])->name('quotes.print')->defaults('description', 'Print a quotation');

        // Sales orders
        Route::get('orders', [\App\Http\Controllers\Sales\OrderController::class, 'index'])->name('orders.index')->defaults('description', 'Sales orders');
        Route::get('orders/{order}', [\App\Http\Controllers\Sales\OrderController::class, 'show'])->name('orders.show')->defaults('description', 'Sales order detail');
        Route::post('orders/{order}/fulfil', [\App\Http\Controllers\Sales\OrderController::class, 'fulfil'])->name('orders.fulfil')->defaults('description', 'Fulfil an order and invoice it');
        Route::post('orders/{order}/cancel', [\App\Http\Controllers\Sales\OrderController::class, 'cancel'])->name('orders.cancel')->defaults('description', 'Cancel an order');

        // Price lists
        Route::get('price-lists', [\App\Http\Controllers\Sales\PriceListController::class, 'index'])->name('price-lists.index')->defaults('description', 'Selling price lists');
        Route::post('price-lists', [\App\Http\Controllers\Sales\PriceListController::class, 'store'])->name('price-lists.store')->defaults('description', 'Create a price list');
        Route::get('price-lists/{priceList}', [\App\Http\Controllers\Sales\PriceListController::class, 'show'])->name('price-lists.show')->defaults('description', 'Price list detail');
        Route::post('price-lists/{priceList}/items', [\App\Http\Controllers\Sales\PriceListController::class, 'storeItem'])->name('price-lists.items.store')->defaults('description', 'Set a part price');
        Route::patch('price-lists/{priceList}/items/{item}', [\App\Http\Controllers\Sales\PriceListController::class, 'updateItem'])->name('price-lists.items.update')->defaults('description', 'Update a part price');
        Route::delete('price-lists/{priceList}/items/{item}', [\App\Http\Controllers\Sales\PriceListController::class, 'destroyItem'])->name('price-lists.items.destroy')->defaults('description', 'Remove a part price');

        // Promotions
        Route::get('promotions', [\App\Http\Controllers\Sales\PromotionController::class, 'index'])->name('promotions.index')->defaults('description', 'Promotions & discounts');
        Route::post('promotions', [\App\Http\Controllers\Sales\PromotionController::class, 'store'])->name('promotions.store')->defaults('description', 'Create a promotion');
        Route::post('promotions/{promotion}/toggle', [\App\Http\Controllers\Sales\PromotionController::class, 'toggle'])->name('promotions.toggle')->defaults('description', 'Activate/deactivate a promotion');
        // Lay-bys
        Route::get('laybys', [\App\Http\Controllers\Sales\LaybyController::class, 'index'])->name('laybys.index')->defaults('description', 'Lay-by management');
        Route::post('laybys', [\App\Http\Controllers\Sales\LaybyController::class, 'store'])->name('laybys.store')->defaults('description', 'Create a lay-by');
        Route::post('laybys/{layby}/payment', [\App\Http\Controllers\Sales\LaybyController::class, 'addPayment'])->name('laybys.payment')->defaults('description', 'Record a lay-by payment');
        // Delivery notes
        Route::get('delivery-notes', [\App\Http\Controllers\Sales\DeliveryNoteController::class, 'index'])->name('delivery-notes.index')->defaults('description', 'Delivery notes');
        Route::post('delivery-notes', [\App\Http\Controllers\Sales\DeliveryNoteController::class, 'store'])->name('delivery-notes.store')->defaults('description', 'Create a delivery note');
        Route::post('delivery-notes/{deliveryNote}/transition', [\App\Http\Controllers\Sales\DeliveryNoteController::class, 'transition'])->name('delivery-notes.transition')->defaults('description', 'Update delivery status');
    });

    // ── Finance & Accounts module ────────────────────────────────────────
    Route::prefix('finance')->name('finance.')->group(function () {
        // Chart of accounts + periods
        Route::get('coa', [\App\Http\Controllers\Finance\ChartOfAccountsController::class, 'index'])->name('coa.index')->defaults('description', 'Chart of accounts');
        Route::get('periods', [\App\Http\Controllers\Finance\PeriodController::class, 'index'])->name('periods.index')->defaults('description', 'Financial periods');
        Route::post('periods/{period}/transition', [\App\Http\Controllers\Finance\PeriodController::class, 'transition'])->name('periods.transition')->defaults('description', 'Open, close or lock a period');

        // General ledger + journals
        Route::get('gl', [\App\Http\Controllers\Finance\GeneralLedgerController::class, 'index'])->name('gl.index')->defaults('description', 'General ledger enquiry');
        Route::get('journals', [\App\Http\Controllers\Finance\JournalController::class, 'index'])->name('journals.index')->defaults('description', 'Journal register');
        Route::get('journals/create', [\App\Http\Controllers\Finance\JournalController::class, 'create'])->name('journals.create')->defaults('description', 'Capture a manual journal');
        Route::post('journals', [\App\Http\Controllers\Finance\JournalController::class, 'store'])->name('journals.store')->defaults('description', 'Post a manual journal');
        Route::get('journals/{journal}', [\App\Http\Controllers\Finance\JournalController::class, 'show'])->name('journals.show')->defaults('description', 'Journal detail');
        Route::post('journals/{journal}/reverse', [\App\Http\Controllers\Finance\JournalController::class, 'reverse'])->name('journals.reverse')->defaults('description', 'Reverse a manual journal');

        // Accounts receivable
        Route::get('receipts', [\App\Http\Controllers\Finance\ReceiptController::class, 'index'])->name('receipts.index')->defaults('description', 'Customer receipts & AR ageing');
        Route::get('receipts/create', [\App\Http\Controllers\Finance\ReceiptController::class, 'create'])->name('receipts.create')->defaults('description', 'Capture a customer receipt');
        Route::get('receipts/customer-lookup', [\App\Http\Controllers\Finance\ReceiptController::class, 'customerLookup'])->name('receipts.customer-lookup')->defaults('description', 'Customer & open-invoice lookup');
        Route::post('receipts', [\App\Http\Controllers\Finance\ReceiptController::class, 'store'])->name('receipts.store')->defaults('description', 'Post a customer receipt');

        // Accounts payable + payment run
        Route::get('payments', [\App\Http\Controllers\Finance\PaymentController::class, 'index'])->name('payments.index')->defaults('description', 'Supplier payments & AP ageing');
        Route::get('payments/create', [\App\Http\Controllers\Finance\PaymentController::class, 'create'])->name('payments.create')->defaults('description', 'Capture a supplier payment');
        Route::get('payments/supplier-lookup', [\App\Http\Controllers\Finance\PaymentController::class, 'supplierLookup'])->name('payments.supplier-lookup')->defaults('description', 'Supplier & open-invoice lookup');
        Route::post('payments', [\App\Http\Controllers\Finance\PaymentController::class, 'store'])->name('payments.store')->defaults('description', 'Post a supplier payment');
        Route::get('payment-run', [\App\Http\Controllers\Finance\PaymentController::class, 'runIndex'])->name('payment-run.index')->defaults('description', 'Batch payment run');
        Route::post('payment-run', [\App\Http\Controllers\Finance\PaymentController::class, 'runExecute'])->name('payment-run.execute')->defaults('description', 'Execute a batch payment run');

        // VAT returns
        Route::get('vat', [\App\Http\Controllers\Finance\VatController::class, 'index'])->name('vat.index')->defaults('description', 'VAT returns');
        Route::post('vat/generate', [\App\Http\Controllers\Finance\VatController::class, 'generate'])->name('vat.generate')->defaults('description', 'Generate a VAT return');
        Route::post('vat/{vatReturn}/transition', [\App\Http\Controllers\Finance\VatController::class, 'transition'])->name('vat.transition')->defaults('description', 'Submit or pay a VAT return');

        // Financial reports
        Route::get('reports/trial-balance', [\App\Http\Controllers\Finance\ReportController::class, 'trialBalance'])->name('reports.trial-balance')->defaults('description', 'Trial balance');
        Route::get('reports/income-statement', [\App\Http\Controllers\Finance\ReportController::class, 'incomeStatement'])->name('reports.income-statement')->defaults('description', 'Income statement (P&L)');
        Route::get('reports/balance-sheet', [\App\Http\Controllers\Finance\ReportController::class, 'balanceSheet'])->name('reports.balance-sheet')->defaults('description', 'Balance sheet');

        // Cash & bank
        Route::get('bank', [\App\Http\Controllers\Finance\BankController::class, 'index'])->name('bank.index')->defaults('description', 'Cash & bank management');
        Route::post('bank', [\App\Http\Controllers\Finance\BankController::class, 'store'])->name('bank.store')->defaults('description', 'Add a bank account');
        Route::post('bank/{bankAccount}/lines', [\App\Http\Controllers\Finance\BankController::class, 'addLine'])->name('bank.lines.store')->defaults('description', 'Add a statement line');
        Route::post('bank/lines/{line}/reconcile', [\App\Http\Controllers\Finance\BankController::class, 'reconcile'])->name('bank.reconcile')->defaults('description', 'Toggle reconciled');
    });

    // ── Workshop module ──────────────────────────────────────────────────
    Route::prefix('workshop')->name('workshop.')->group(function () {
        // Technician board
        Route::get('board', [\App\Http\Controllers\Workshop\BoardController::class, 'index'])->name('board')->defaults('description', 'Technician job board');

        // Vehicles
        Route::get('vehicles', [\App\Http\Controllers\Workshop\VehicleController::class, 'index'])->name('vehicles.index')->defaults('description', 'Vehicle registry');
        Route::get('vehicles/create', [\App\Http\Controllers\Workshop\VehicleController::class, 'create'])->name('vehicles.create')->defaults('description', 'Register a vehicle');
        Route::get('vehicles/customer-lookup', [\App\Http\Controllers\Workshop\VehicleController::class, 'customerLookup'])->name('vehicles.customer-lookup')->defaults('description', 'Customer lookup');
        Route::post('vehicles', [\App\Http\Controllers\Workshop\VehicleController::class, 'store'])->name('vehicles.store')->defaults('description', 'Save a vehicle');
        Route::get('vehicles/{vehicle}', [\App\Http\Controllers\Workshop\VehicleController::class, 'show'])->name('vehicles.show')->defaults('description', 'Vehicle & service history');

        // Labour codes + technicians
        Route::get('labour', [\App\Http\Controllers\Workshop\LabourCodeController::class, 'index'])->name('labour.index')->defaults('description', 'Labour codes & rates');
        Route::post('labour', [\App\Http\Controllers\Workshop\LabourCodeController::class, 'store'])->name('labour.store')->defaults('description', 'Create a labour code');
        Route::patch('labour/{labourCode}', [\App\Http\Controllers\Workshop\LabourCodeController::class, 'update'])->name('labour.update')->defaults('description', 'Update a labour code');
        Route::post('labour/{labourCode}/rates', [\App\Http\Controllers\Workshop\LabourCodeController::class, 'storeRate'])->name('labour.rates.store')->defaults('description', 'Set a make-specific rate');

        Route::get('technicians', [\App\Http\Controllers\Workshop\TechnicianController::class, 'index'])->name('technicians.index')->defaults('description', 'Technicians');
        Route::post('technicians', [\App\Http\Controllers\Workshop\TechnicianController::class, 'store'])->name('technicians.store')->defaults('description', 'Add a technician');
        Route::patch('technicians/{technician}', [\App\Http\Controllers\Workshop\TechnicianController::class, 'update'])->name('technicians.update')->defaults('description', 'Update a technician');

        // Job cards
        Route::get('jobs', [\App\Http\Controllers\Workshop\JobCardController::class, 'index'])->name('jobs.index')->defaults('description', 'Job cards');
        Route::get('jobs/create', [\App\Http\Controllers\Workshop\JobCardController::class, 'create'])->name('jobs.create')->defaults('description', 'Open a job card');
        Route::get('jobs/vehicle-lookup', [\App\Http\Controllers\Workshop\JobCardController::class, 'vehicleLookup'])->name('jobs.vehicle-lookup')->defaults('description', 'Vehicle lookup');
        Route::get('jobs/part-lookup', [\App\Http\Controllers\Workshop\JobCardController::class, 'partLookup'])->name('jobs.part-lookup')->defaults('description', 'Part lookup');
        Route::post('jobs', [\App\Http\Controllers\Workshop\JobCardController::class, 'store'])->name('jobs.store')->defaults('description', 'Save a job card');
        Route::get('jobs/{job}', [\App\Http\Controllers\Workshop\JobCardController::class, 'show'])->name('jobs.show')->defaults('description', 'Job card detail');
        Route::patch('jobs/{job}', [\App\Http\Controllers\Workshop\JobCardController::class, 'update'])->name('jobs.update')->defaults('description', 'Update a job card');
        Route::post('jobs/{job}/transition', [\App\Http\Controllers\Workshop\JobCardController::class, 'transition'])->name('jobs.transition')->defaults('description', 'Move a job through its lifecycle');
        Route::post('jobs/{job}/labour', [\App\Http\Controllers\Workshop\JobCardController::class, 'addLabour'])->name('jobs.labour.store')->defaults('description', 'Add labour to a job');
        Route::delete('jobs/{job}/labour/{labour}', [\App\Http\Controllers\Workshop\JobCardController::class, 'removeLabour'])->name('jobs.labour.destroy')->defaults('description', 'Remove labour');
        Route::post('jobs/{job}/parts', [\App\Http\Controllers\Workshop\JobCardController::class, 'requestPart'])->name('jobs.parts.store')->defaults('description', 'Add a part to a job');
        Route::post('jobs/{job}/parts/{part}/issue', [\App\Http\Controllers\Workshop\JobCardController::class, 'issuePart'])->name('jobs.parts.issue')->defaults('description', 'Issue a part from stock');
        Route::post('jobs/{job}/parts/{part}/return', [\App\Http\Controllers\Workshop\JobCardController::class, 'returnPart'])->name('jobs.parts.return')->defaults('description', 'Return a part to stock');
        Route::post('jobs/{job}/invoice', [\App\Http\Controllers\Workshop\JobCardController::class, 'invoice'])->name('jobs.invoice')->defaults('description', 'Invoice a completed job');

        Route::get('warranty', [\App\Http\Controllers\Workshop\WarrantyClaimController::class, 'index'])->name('warranty.index')->defaults('description', 'Warranty claims');
        Route::post('warranty', [\App\Http\Controllers\Workshop\WarrantyClaimController::class, 'store'])->name('warranty.store')->defaults('description', 'Raise a warranty claim');
        Route::post('warranty/{warrantyClaim}/transition', [\App\Http\Controllers\Workshop\WarrantyClaimController::class, 'transition'])->name('warranty.transition')->defaults('description', 'Progress a warranty claim');
    });

    // ── Reports & Analytics module ───────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Reports\DashboardController::class, 'index'])->name('dashboard')->defaults('description', 'Executive dashboard');
        Route::get('sales', [\App\Http\Controllers\Reports\SalesReportController::class, 'index'])->name('sales.index')->defaults('description', 'Sales reports');
        Route::get('inventory', [\App\Http\Controllers\Reports\InventoryReportController::class, 'index'])->name('inventory.index')->defaults('description', 'Inventory reports');
        Route::get('customers', [\App\Http\Controllers\Reports\CustomerReportController::class, 'index'])->name('customers.index')->defaults('description', 'Customer reports');
        Route::get('suppliers', [\App\Http\Controllers\Reports\SupplierReportController::class, 'index'])->name('suppliers.index')->defaults('description', 'Supplier reports');
        Route::get('workshop', [\App\Http\Controllers\Reports\WorkshopReportController::class, 'index'])->name('workshop.index')->defaults('description', 'Workshop reports');

        Route::get('scheduled', [\App\Http\Controllers\Reports\ScheduledReportController::class, 'index'])->name('scheduled.index')->defaults('description', 'Scheduled reports');
        Route::post('scheduled', [\App\Http\Controllers\Reports\ScheduledReportController::class, 'store'])->name('scheduled.store')->defaults('description', 'Create a scheduled report');
        Route::post('scheduled/{scheduledReport}/toggle', [\App\Http\Controllers\Reports\ScheduledReportController::class, 'toggle'])->name('scheduled.toggle')->defaults('description', 'Activate/deactivate a schedule');
    });

    // Notification bell actions
    Route::post('/notifications/{id}/read', function (\Illuminate\Http\Request $request, string $id) {
        $request->user()->notifications()->where('id', $id)->first()?->markAsRead();

        return back();
    })->name('notifications.read')->defaults('description', 'Mark a notification as read');

    Route::post('/notifications/read-all', function (\Illuminate\Http\Request $request) {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    })->name('notifications.read-all')->defaults('description', 'Mark all notifications as read');

    // Command palette (Ctrl+K) search endpoint
    Route::get('/search', function (\Illuminate\Http\Request $request, \App\Services\GlobalSearchService $search) {
        return response()->json([
            'groups' => $search->search((string) $request->query('q', ''), $request->user()),
        ]);
    })->name('search.global')->defaults('description', 'Global command palette search');

    // In-app documentation viewer (page guides deep-link into the spec)
    Route::get('/help/docs/{path?}', [\App\Http\Controllers\HelpDocsController::class, 'show'])
        ->where('path', '.*')
        ->name('help.docs.show')
        ->defaults('description', 'Read system documentation');
});