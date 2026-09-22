<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthenticationManagement\Http\Controllers\AuthenticationManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authenticationmanagements', AuthenticationManagementController::class)->names('authenticationmanagement');
});
