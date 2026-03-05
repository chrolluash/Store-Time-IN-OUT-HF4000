<?php

use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\EmployeeLogsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\KioskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('employees', EmployeeController::class)->except(['show']);
    Route::get('employees/{employee}/enroll', [EmployeeController::class, 'enroll'])->name('employees.enroll');
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('employees/{employee}/logs', [EmployeeLogsController::class, 'index'])->name('employees.logs');
    Route::get('employees/{employee}/logs/export', [EmployeeLogsController::class, 'export'])->name('employees.logs.export');
});

// Fingerprint routes — outside auth, CSRF exempt
Route::prefix('admin')->name('admin.')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::post('employees/{employee}/fingerprint', [EmployeeController::class, 'saveFingerprint'])->name('employees.fingerprint.save');
    Route::delete('employees/{employee}/fingerprint', [EmployeeController::class, 'clearFingerprint'])->name('employees.fingerprint.clear');
});

// Fingerprint match test & get — no CSRF
Route::post('admin/employees/{employee}/fingerprint/test', [EmployeeController::class, 'testFingerprint'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('admin.employees.fingerprint.test');

Route::get('admin/employees/{employee}/fingerprint/get', [EmployeeController::class, 'getFingerprint'])
    ->name('admin.employees.fingerprint.get');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

require __DIR__.'/auth.php';

// Kiosk — public, no auth
Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/kiosk/templates', [KioskController::class, 'templates'])->name('kiosk.templates');
Route::get('/kiosk/today-logs', [KioskController::class, 'todayLogs'])->name('kiosk.today-logs');
Route::post('/kiosk/log', [KioskController::class, 'log'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('kiosk.log');