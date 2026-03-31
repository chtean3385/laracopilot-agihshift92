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
use App\Http\Controllers\Admin\HotelSwitchController;
use App\Http\Controllers\Admin\SaHotelFilterController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\WhatsAppController;
use App\Http\Controllers\Admin\PaymentLinksController;

// ── Installer (must be first, no auth middleware) ───────────────────────────
Route::middleware(['not.installed'])->group(function () {
    Route::get('/install',          [\App\Http\Controllers\InstallerController::class, 'index'])->name('install');
    Route::post('/install/test-db', [\App\Http\Controllers\InstallerController::class, 'testDb'])->name('install.testDb');
    Route::post('/install/run',     [\App\Http\Controllers\InstallerController::class, 'run'])->name('install.run');
});

// ── Healthcheck ────────────────────────────────────────────────────────────
Route::get('/health', fn() => response('OK', 200));

// ── Root ───────────────────────────────────────────────────────────────────
Route::get('/', [AdminAuthController::class, 'showLogin'])->name('home');

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

// ── Hotel Picker ────────────────────────────────────────────────────────────
Route::get('/select-hotel',  [HotelSwitchController::class, 'index'] )->name('select.hotel');
Route::post('/select-hotel', [HotelSwitchController::class, 'select'])->name('select.hotel.post');

// ── Super Admin Hotel Filter ─────────────────────────────────────────────────
Route::post('/super-admin/hotel-filter', [SaHotelFilterController::class, 'filter'])->name('sa.hotel.filter');

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
Route::delete('/rooms/{id}',        [RoomController::class, 'destroy']  )->middleware('permission:rooms.delete')->name('rooms.destroy');
Route::post('/rooms/{id}/deactivate',[RoomController::class, 'deactivate'])->middleware('permission:rooms.edit')->name('rooms.deactivate');
Route::post('/rooms/{id}/activate',  [RoomController::class, 'activate']  )->middleware('permission:rooms.edit')->name('rooms.activate');
Route::get('/rooms/{id}',            [RoomController::class, 'show']      )->name('rooms.show');

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
    Route::get('/reports/occupancy',       [ReportController::class, 'occupancy']     )->name('reports.occupancy');
    Route::get('/reports/bookings',        [ReportController::class, 'bookings']      )->name('reports.bookings');
    Route::get('/reports/guest-register',  [ReportController::class, 'guestRegister'] )->name('reports.guest_register');
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

// ── Modules (Super Admin only) ─────────────────────────────────────────────
Route::get('/settings/modules',                  [ModuleController::class, 'index'] )->name('modules.index');
Route::post('/settings/modules/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');

// ── WhatsApp Automation ────────────────────────────────────────────────────
Route::get('/whatsapp/config',                    [WhatsAppController::class, 'config']       )->name('whatsapp.config');
Route::post('/whatsapp/config',                   [WhatsAppController::class, 'configSave']   )->name('whatsapp.config.save');
Route::get('/whatsapp/templates',                 [WhatsAppController::class, 'templates']    )->name('whatsapp.templates');
Route::get('/whatsapp/templates/{template}/edit', [WhatsAppController::class, 'templateEdit'] )->name('whatsapp.template.edit');
Route::put('/whatsapp/templates/{template}',      [WhatsAppController::class, 'templateSave'] )->name('whatsapp.template.save');
Route::post('/whatsapp/test-send',                [WhatsAppController::class, 'testSend']     )->name('whatsapp.test.send');

// ── Payment Links ──────────────────────────────────────────────────────────
Route::get('/payment-links/config',                           [PaymentLinksController::class, 'config']          )->name('payment_links.config');
Route::post('/payment-links/config',                          [PaymentLinksController::class, 'configSave']      )->name('payment_links.config.save');
Route::post('/payment-links/invoices/{id}/razorpay',          [PaymentLinksController::class, 'razorpayCreate']  )->name('payment_links.razorpay.create');
Route::get('/payment-links/invoices/{id}/upi-qr',             [PaymentLinksController::class, 'upiQr']           )->name('payment_links.upi_qr');
Route::get('/payment-links/upi-config',                       [PaymentLinksController::class, 'upiConfig']       )->name('payment_links.upi_config');
Route::post('/payment-links/booking/{id}/razorpay',           [PaymentLinksController::class, 'razorpayForBooking'])->name('payment_links.booking.razorpay');
Route::get('/payment-links/razorpay/webhook',                 [PaymentLinksController::class, 'razorpayWebhook'] )->name('payment_links.razorpay.webhook')->withoutMiddleware(['web']);

// ── OTA Channel Manager ────────────────────────────────────────────────────
Route::get('/channel-manager',                            [\App\Http\Controllers\Admin\ChannelManagerController::class, 'index']            )->name('channel_manager.index');
Route::get('/channel-manager/config',                     [\App\Http\Controllers\Admin\ChannelManagerController::class, 'config']           )->name('channel_manager.config');
Route::post('/channel-manager/config',                    [\App\Http\Controllers\Admin\ChannelManagerController::class, 'configSave']       )->name('channel_manager.config.save');
Route::post('/channel-manager/config/test',               [\App\Http\Controllers\Admin\ChannelManagerController::class, 'configTest']       )->name('channel_manager.config.test');
Route::get('/channel-manager/rooms',                      [\App\Http\Controllers\Admin\ChannelManagerController::class, 'rooms']            )->name('channel_manager.rooms');
Route::post('/channel-manager/rooms',                     [\App\Http\Controllers\Admin\ChannelManagerController::class, 'roomsSave']        )->name('channel_manager.rooms.save');
Route::get('/channel-manager/availability',               [\App\Http\Controllers\Admin\ChannelManagerController::class, 'availability']     )->name('channel_manager.availability');
Route::post('/channel-manager/availability/sync',         [\App\Http\Controllers\Admin\ChannelManagerController::class, 'availabilitySync'] )->name('channel_manager.availability.sync');
Route::get('/channel-manager/bookings',                   [\App\Http\Controllers\Admin\ChannelManagerController::class, 'bookings']         )->name('channel_manager.bookings');
Route::post('/channel-manager/bookings',                  [\App\Http\Controllers\Admin\ChannelManagerController::class, 'bookingStore']     )->name('channel_manager.booking.store');
Route::post('/channel-manager/bookings/{id}/convert',     [\App\Http\Controllers\Admin\ChannelManagerController::class, 'bookingConvert']   )->name('channel_manager.booking.convert');
Route::post('/channel-manager/bookings/{id}/cancel',      [\App\Http\Controllers\Admin\ChannelManagerController::class, 'bookingCancel']    )->name('channel_manager.booking.cancel');

// ── Booking Guests (Police Register) ────────────────────────────────────────
Route::post('/bookings/{bookingId}/guests',                        [\App\Http\Controllers\Admin\BookingGuestController::class, 'store']          )->name('booking.guests.store');
Route::delete('/bookings/{bookingId}/guests/{guestId}',           [\App\Http\Controllers\Admin\BookingGuestController::class, 'destroy']        )->name('booking.guests.destroy');
Route::post('/bookings/{bookingId}/guests/{guestId}/signature',   [\App\Http\Controllers\Admin\BookingGuestController::class, 'saveSignature']  )->name('booking.guests.signature');
Route::post('/bookings/{bookingId}/guests/{guestId}/document',    [\App\Http\Controllers\Admin\BookingGuestController::class, 'uploadDoc']      )->name('booking.guests.document');
Route::get('/bookings/{bookingId}/guests/{guestId}/document',     [\App\Http\Controllers\Admin\BookingGuestController::class, 'downloadDoc']    )->name('booking.guests.document.download');
// Primary guest (customer) signature
Route::post('/guests/{customerId}/signature',                      [\App\Http\Controllers\Admin\CustomerController::class, 'saveSignature']     )->name('customers.signature');

// ── Pathik Autofill ─────────────────────────────────────────────────────────
Route::get( '/pathik',                 [\App\Http\Controllers\Admin\PathikController::class, 'index']           )->name('pathik.index');
Route::post('/pathik/token/regenerate',[\App\Http\Controllers\Admin\PathikController::class, 'regenerateToken'] )->name('pathik.token.regenerate');
Route::post('/pathik/pending',         [\App\Http\Controllers\Admin\PathikController::class, 'pendingStore']    )->name('pathik.pending.store');
Route::post('/pathik/clear',           [\App\Http\Controllers\Admin\PathikController::class, 'clearPending']    )->name('pathik.clear');
// Extension endpoint — no session auth, validates api_token param instead
Route::get( '/pathik/pending',         [\App\Http\Controllers\Admin\PathikController::class, 'pendingFetch']    )->name('pathik.pending.fetch')->withoutMiddleware(['web']);


// ── Platform Admin (Super Admin only) ───────────────────────────────────────
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;

Route::prefix('platform')->middleware('platform.admin')->group(function () {
    Route::get('/',          fn() => redirect()->route('platform.dashboard'))->name('platform.index');
    Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');

    // Placeholder routes for Hotel and User management (implemented in Tasks #8 and #9)
    Route::get('/hotels',           fn() => abort(503, 'Hotel management coming soon — Task #8'))->name('platform.hotels.index');
    Route::get('/hotels/create',    fn() => abort(503, 'Coming soon'))->name('platform.hotels.create');
    Route::post('/hotels',          fn() => abort(503, 'Coming soon'))->name('platform.hotels.store');
    Route::get('/hotels/{id}/edit', fn() => abort(503, 'Coming soon'))->name('platform.hotels.edit');
    Route::put('/hotels/{id}',      fn() => abort(503, 'Coming soon'))->name('platform.hotels.update');
    Route::post('/hotels/{id}/suspend',   fn() => abort(503, 'Coming soon'))->name('platform.hotels.suspend');
    Route::post('/hotels/{id}/activate',  fn() => abort(503, 'Coming soon'))->name('platform.hotels.activate');
    Route::get('/hotels/{id}/view-in-crm', [PlatformDashboardController::class, 'viewInCrm'])->name('platform.hotels.view-in-crm');

    Route::get('/users',       fn() => abort(503, 'User management coming soon — Task #9'))->name('platform.users.index');
    Route::get('/users/{id}',  fn() => abort(503, 'Coming soon'))->name('platform.users.show');
    Route::post('/users/{id}/hotel/{hotelId}/suspend',  fn() => abort(503, 'Coming soon'))->name('platform.users.suspend');
    Route::post('/users/{id}/hotel/{hotelId}/activate', fn() => abort(503, 'Coming soon'))->name('platform.users.activate');
});
