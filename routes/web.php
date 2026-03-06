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

// ── Root redirect ──────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ── Auth ───────────────────────────────────────────────
Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AdminAuthController::class, 'logout'])->name('logout');

// ── Dashboard ──────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── Customers ──────────────────────────────────────────
Route::get('/customers',             [CustomerController::class, 'index']  )->name('customers.index');
Route::get('/customers/create',      [CustomerController::class, 'create'] )->name('customers.create');
Route::post('/customers',            [CustomerController::class, 'store']  )->name('customers.store');
Route::get('/customers/{id}',        [CustomerController::class, 'show']   )->name('customers.show');
Route::get('/customers/{id}/edit',   [CustomerController::class, 'edit']   )->name('customers.edit');
Route::put('/customers/{id}',        [CustomerController::class, 'update'] )->name('customers.update');
Route::delete('/customers/{id}',     [CustomerController::class, 'destroy'])->name('customers.destroy');

// ── Documents ──────────────────────────────────────────
Route::get('/customers/{customerId}/documents',        [DocumentController::class, 'index']   )->name('documents.index');
Route::get('/customers/{customerId}/documents/create', [DocumentController::class, 'create']  )->name('documents.create');
Route::post('/customers/{customerId}/documents',       [DocumentController::class, 'store']   )->name('documents.store');
Route::get('/documents/{id}/download',                 [DocumentController::class, 'download'])->name('documents.download');
Route::delete('/documents/{id}',                       [DocumentController::class, 'destroy'] )->name('documents.destroy');

// ── Rooms ──────────────────────────────────────────────
Route::get('/rooms',           [RoomController::class, 'index']  )->name('rooms.index');
Route::get('/rooms/create',    [RoomController::class, 'create'] )->name('rooms.create');
Route::post('/rooms',          [RoomController::class, 'store']  )->name('rooms.store');
Route::get('/rooms/{id}',      [RoomController::class, 'show']   )->name('rooms.show');
Route::get('/rooms/{id}/edit', [RoomController::class, 'edit']   )->name('rooms.edit');
Route::put('/rooms/{id}',      [RoomController::class, 'update'] )->name('rooms.update');
Route::delete('/rooms/{id}',   [RoomController::class, 'destroy'])->name('rooms.destroy');

// ── Bookings ───────────────────────────────────────────
Route::get('/bookings',               [BookingController::class, 'index']  )->name('bookings.index');
Route::get('/bookings/create',        [BookingController::class, 'create'] )->name('bookings.create');
Route::post('/bookings',              [BookingController::class, 'store']  )->name('bookings.store');
Route::get('/bookings/{id}',          [BookingController::class, 'show']   )->name('bookings.show');
Route::get('/bookings/{id}/edit',     [BookingController::class, 'edit']   )->name('bookings.edit');
Route::put('/bookings/{id}',          [BookingController::class, 'update'] )->name('bookings.update');
Route::delete('/bookings/{id}',       [BookingController::class, 'destroy'])->name('bookings.destroy');

// ── Check-In ───────────────────────────────────────────
Route::get('/checkin',             [CheckInController::class, 'index']  )->name('checkin.index');
Route::get('/checkin/{id}',        [CheckInController::class, 'show']   )->name('checkin.show');
Route::post('/checkin/{id}',       [CheckInController::class, 'process'])->name('checkin.process');

// ── Check-Out ──────────────────────────────────────────
Route::get('/checkout',            [CheckOutController::class, 'index']  )->name('checkout.index');
Route::get('/checkout/{id}',       [CheckOutController::class, 'show']   )->name('checkout.show');
Route::post('/checkout/{id}',      [CheckOutController::class, 'process'])->name('checkout.process');

// ── Payments ───────────────────────────────────────────
Route::get('/payments',           [PaymentController::class, 'index']  )->name('payments.index');
Route::get('/payments/create',    [PaymentController::class, 'create'] )->name('payments.create');
Route::post('/payments',          [PaymentController::class, 'store']  )->name('payments.store');
Route::get('/payments/{id}',      [PaymentController::class, 'show']   )->name('payments.show');
Route::delete('/payments/{id}',   [PaymentController::class, 'destroy'])->name('payments.destroy');

// ── Invoices ───────────────────────────────────────────
Route::get('/invoices',           [InvoiceController::class, 'index']  )->name('invoices.index');
Route::get('/invoices/{id}',      [InvoiceController::class, 'show']   )->name('invoices.show');
Route::get('/invoices/{id}/print',[InvoiceController::class, 'print']  )->name('invoices.print');
Route::delete('/invoices/{id}',   [InvoiceController::class, 'destroy'])->name('invoices.destroy');

// ── Reports ────────────────────────────────────────────
Route::get('/reports',            [ReportController::class, 'index']    )->name('reports.index');
Route::get('/reports/revenue',    [ReportController::class, 'revenue']  )->name('reports.revenue');
Route::get('/reports/occupancy',  [ReportController::class, 'occupancy'])->name('reports.occupancy');
Route::get('/reports/bookings',   [ReportController::class, 'bookings'] )->name('reports.bookings');

// ── Register (redirect to login) ───────────────────────
Route::get('/register', fn() => redirect()->route('login'))->name('register');

// ── Settings ───────────────────────────────────────────
Route::get('/settings',           [SettingController::class, 'index']  )->name('settings.index');
Route::put('/settings',           [SettingController::class, 'update'] )->name('settings.update');