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