<?php

use App\Http\Controllers\Admin\CustomerCsvController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/admin/upload-csv', [CustomerCsvController::class, 'store'])
        ->middleware('role:admin');

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::put('/customer/{customer}/payment-status', [CustomerController::class, 'updatePaymentStatus'])
        ->middleware('role:admin,user');
    Route::post('/customer/{customer}/send-notification', [NotificationController::class, 'store'])
        ->middleware('role:admin,user');

    Route::get('/reports/summary', ReportController::class);
});
