<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthenticationManagement\Http\Controllers\AuthenticationManagementController;
use Modules\AuthenticationManagement\Http\Controllers\UserController;
use Modules\AuthenticationManagement\Http\Controllers\RoleController;
use Modules\AuthenticationManagement\Http\Controllers\StudentController;
use Modules\AuthenticationManagement\Http\Controllers\TeacherController;
use Modules\AuthenticationManagement\Http\Controllers\ParentController;
use Modules\AuthenticationManagement\Http\Controllers\StaffController;

Route::prefix('auth')->name('auth.')->middleware(['auth', 'verified'])->group(function () {
    // Main Authentication Management Dashboard
    Route::get('/dashboard', [AuthenticationManagementController::class, 'index'])->name('dashboard');
    
    // User Management Routes
    Route::resource('users', UserController::class);
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('users/{user}/remove-role/{role}', [UserController::class, 'removeRole'])->name('users.remove-role');
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    
    // Role Management Routes
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/users', [RoleController::class, 'users'])->name('roles.users');
    
    // Student Profile Routes
    Route::resource('students', StudentController::class);
    Route::post('students/{student}/assign-parent', [StudentController::class, 'assignParent'])->name('students.assign-parent');
    Route::delete('students/{student}/remove-parent/{parent}', [StudentController::class, 'removeParent'])->name('students.remove-parent');
    Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    
    // Teacher Profile Routes
    Route::resource('teachers', TeacherController::class);
    Route::post('teachers/{teacher}/assign-subjects', [TeacherController::class, 'assignSubjects'])->name('teachers.assign-subjects');
    Route::post('teachers/{teacher}/assign-classes', [TeacherController::class, 'assignClasses'])->name('teachers.assign-classes');
    Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
    
    // Parent Profile Routes
    Route::resource('parents', ParentController::class);
    Route::get('parents/{parent}/students', [ParentController::class, 'students'])->name('parents.students');
    Route::post('parents/{parent}/update-permissions/{student}', [ParentController::class, 'updatePermissions'])->name('parents.update-permissions');
    
    // Staff Profile Routes
    Route::resource('staff', StaffController::class);
    Route::post('staff/{staff}/assign-supervisor', [StaffController::class, 'assignSupervisor'])->name('staff.assign-supervisor');
    Route::post('staff/{staff}/assign-responsibilities', [StaffController::class, 'assignResponsibilities'])->name('staff.assign-responsibilities');
    Route::get('staff/export', [StaffController::class, 'export'])->name('staff.export');
});
