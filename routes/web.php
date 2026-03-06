<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\CheckOutController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ForgotPasswordController;

// ── Root redirect ──────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard'));

// ── Auth ───────────────────────────────────────────────────────────────────
Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AdminAuthController::class, 'logout'])->name('logout');
Route::get('/register', fn() => redirect()->route('login'))->name('register');

// ── Forgot / Reset Password (no auth required) ─────────────────────────────
Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendLink'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password',        [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

// ── Dashboard (all logged-in users) ────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── Customers ──────────────────────────────────────────────────────────────
Route::get('/customers',           [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/create',    [CustomerController::class, 'create'])->middleware('permission:guests.create')->name('customers.create');
Route::post('/customers',          [CustomerController::class, 'store'] )->middleware('permission:guests.create')->name('customers.store');
Route::get('/customers/{id}/edit', [CustomerController::class, 'edit']  )->middleware('permission:guests.edit')->name('customers.edit');
Route::put('/customers/{id}',      [CustomerController::class, 'update'])->middleware('permission:guests.edit')->name('customers.update');
Route::delete('/customers/{id}',   [CustomerController::class, 'destroy'])->middleware('permission:guests.delete')->name('customers.destroy');
Route::get('/customers/{id}',      [CustomerController::class, 'show'] )->name('customers.show');

// ── Documents ──────────────────────────────────────────────────────────────
Route::get('/customers/{customerId}/documents',        [DocumentController::class, 'index']   )->name('documents.index');
Route::get('/customers/{customerId}/documents/create', [DocumentController::class, 'create']  )->name('documents.create');
Route::post('/customers/{customerId}/documents',       [DocumentController::class, 'store']   )->name('documents.store');
Route::get('/documents/{id}/download',                 [DocumentController::class, 'download'])->name('documents.download');
Route::delete('/documents/{id}',                       [DocumentController::class, 'destroy'] )->name('documents.destroy');

// ── Rooms ──────────────────────────────────────────────────────────────────
Route::get('/rooms',           [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/create',    [RoomController::class, 'create'])->middleware('permission:rooms.create')->name('rooms.create');
Route::post('/rooms',          [RoomController::class, 'store'] )->middleware('permission:rooms.create')->name('rooms.store');
Route::get('/rooms/{id}/edit', [RoomController::class, 'edit']  )->middleware('permission:rooms.edit')->name('rooms.edit');
Route::put('/rooms/{id}',      [RoomController::class, 'update'])->middleware('permission:rooms.edit')->name('rooms.update');
Route::delete('/rooms/{id}',   [RoomController::class, 'destroy'])->middleware('permission:rooms.delete')->name('rooms.destroy');
Route::get('/rooms/{id}',      [RoomController::class, 'show'] )->name('rooms.show');

// ── Bookings ───────────────────────────────────────────────────────────────
Route::get('/bookings',           [BookingController::class, 'index'])->name('bookings.index');
Route::get('/bookings/create',    [BookingController::class, 'create'])->middleware('permission:bookings.create')->name('bookings.create');
Route::post('/bookings',          [BookingController::class, 'store'] )->middleware('permission:bookings.create')->name('bookings.store');
Route::get('/bookings/{id}/edit', [BookingController::class, 'edit']  )->middleware('permission:bookings.edit')->name('bookings.edit');
Route::put('/bookings/{id}',      [BookingController::class, 'update'])->middleware('permission:bookings.edit')->name('bookings.update');
Route::delete('/bookings/{id}',   [BookingController::class, 'destroy'])->middleware('permission:bookings.delete')->name('bookings.destroy');
Route::get('/bookings/{id}',      [BookingController::class, 'show'] )->name('bookings.show');

// ── Check-In ───────────────────────────────────────────────────────────────
Route::get('/checkin',       [CheckInController::class, 'index']  )->name('checkin.index');
Route::get('/checkin/{id}',  [CheckInController::class, 'show']   )->name('checkin.show');
Route::post('/checkin/{id}', [CheckInController::class, 'process'])->middleware('permission:checkin.process')->name('checkin.process');

// ── Check-Out ──────────────────────────────────────────────────────────────
Route::get('/checkout',       [CheckOutController::class, 'index']  )->name('checkout.index');
Route::get('/checkout/{id}',  [CheckOutController::class, 'show']   )->name('checkout.show');
Route::post('/checkout/{id}', [CheckOutController::class, 'process'])->middleware('permission:checkout.process')->name('checkout.process');

// ── Payments ───────────────────────────────────────────────────────────────
Route::get('/payments',        [PaymentController::class, 'index'])->name('payments.index');
Route::get('/payments/create', [PaymentController::class, 'create'])->middleware('permission:payments.create')->name('payments.create');
Route::post('/payments',       [PaymentController::class, 'store'] )->middleware('permission:payments.create')->name('payments.store');
Route::delete('/payments/{id}',[PaymentController::class, 'destroy'])->middleware('permission:payments.delete')->name('payments.destroy');
Route::get('/payments/{id}',   [PaymentController::class, 'show'] )->name('payments.show');

// ── Invoices ───────────────────────────────────────────────────────────────
Route::get('/invoices',            [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{id}/print', [InvoiceController::class, 'print'])->name('invoices.print');
Route::delete('/invoices/{id}',    [InvoiceController::class, 'destroy'])->middleware('permission:invoices.delete')->name('invoices.destroy');
Route::get('/invoices/{id}',       [InvoiceController::class, 'show'] )->name('invoices.show');

// ── Reports ────────────────────────────────────────────────────────────────
Route::middleware('permission:reports.view')->group(function () {
    Route::get('/reports',           [ReportController::class, 'index']    )->name('reports.index');
    Route::get('/reports/revenue',   [ReportController::class, 'revenue']  )->name('reports.revenue');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');
    Route::get('/reports/bookings',  [ReportController::class, 'bookings'] )->name('reports.bookings');
});

// ── Settings ───────────────────────────────────────────────────────────────
Route::middleware('permission:settings.view')->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.edit')->name('settings.update');
});

// ── Activity Log ───────────────────────────────────────────────────────────
Route::get('/activity-log', [ActivityLogController::class, 'index'])
    ->middleware('permission:activity_log.view')
    ->name('activity_log.index');

// ── Roles & Permissions ────────────────────────────────────────────────────
Route::middleware('permission:roles.view')->group(function () {
    Route::get('/roles',              [RoleController::class, 'index'] )->name('roles.index');
    Route::get('/roles/create',       [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles',             [RoleController::class, 'store'] )->middleware('permission:roles.edit')->name('roles.store');
    Route::get('/roles/{role}/edit',  [RoleController::class, 'edit']  )->name('roles.edit');
    Route::put('/roles/{role}',       [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
    Route::delete('/roles/{role}',    [RoleController::class, 'destroy'])->middleware('permission:roles.edit')->name('roles.destroy');
});

// ── Users ──────────────────────────────────────────────────────────────────
Route::get('/users',           [UserController::class, 'index']  )->middleware('permission:users.view')->name('users.index');
Route::get('/users/create',    [UserController::class, 'create'] )->middleware('permission:users.create')->name('users.create');
Route::post('/users',          [UserController::class, 'store']  )->middleware('permission:users.create')->name('users.store');
Route::get('/users/{id}/edit', [UserController::class, 'edit']   )->middleware('permission:users.edit')->name('users.edit');
Route::put('/users/{id}',      [UserController::class, 'update'] )->middleware('permission:users.edit')->name('users.update');
Route::delete('/users/{id}',   [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

// ── Change Password (any logged-in user) ───────────────────────────────────
Route::get('/change-password',  [UserController::class, 'changePasswordForm'])->name('password.change.form');
Route::post('/change-password', [UserController::class, 'changePassword'])->name('password.change');
