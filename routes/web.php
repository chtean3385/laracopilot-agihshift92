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
use App\Http\Controllers\Admin\BookingExtraChargeController;
use App\Http\Controllers\Admin\DataCleanupController;
use App\Http\Controllers\Admin\FoodBillingController;
use App\Http\Controllers\Admin\WhatsAppController;
use App\Http\Controllers\Admin\WhatsAppSetupController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\Admin\PaymentLinksController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Admin\RestaurantMenuController;
use App\Http\Controllers\Admin\RestaurantOrderController;
use App\Http\Controllers\Admin\RestaurantBillController;
use App\Http\Controllers\Admin\FoodMenuAdminController;
use App\Http\Controllers\Admin\FoodOrderController;
use App\Http\Controllers\FoodMenuPublicController;

// ── Installer (must be first, no auth middleware) ───────────────────────────
Route::middleware(['not.installed'])->group(function () {
    Route::get('/install',          [\App\Http\Controllers\InstallerController::class, 'index'])->name('install');
    Route::post('/install/test-db', [\App\Http\Controllers\InstallerController::class, 'testDb'])->name('install.testDb');
    Route::post('/install/run',     [\App\Http\Controllers\InstallerController::class, 'run'])->name('install.run');
});

// ── Healthcheck ────────────────────────────────────────────────────────────
Route::get('/health', fn() => response('OK', 200));

// ── WhatsApp Webhook (public — no auth, no CSRF, no hotel-context middleware) ─
// CSRF is excluded via bootstrap/app.php → validateCsrfTokens(except:['webhook/*'])
Route::withoutMiddleware([
    \App\Http\Middleware\SetHotelContext::class,
    \App\Http\Middleware\CheckTrialStatus::class,
])->group(function () {
    Route::get('/webhook/whatsapp',  [WhatsAppWebhookController::class, 'verify'] )->name('whatsapp.webhook.verify');
    Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('whatsapp.webhook.receive');

    // OTA Email Inbound Parse webhook (Mailgun — public, no auth, no CSRF)
    Route::post('/webhook/ota-email', [\App\Http\Controllers\OtaEmailWebhookController::class, 'receive'])->name('ota-email.webhook.receive');
});

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

// ── Upgrade / Plan expired page (accessible even when trial locked) ─────────
Route::get('/upgrade',         [\App\Http\Controllers\Admin\UpgradeController::class, 'index'])->name('upgrade');
Route::post('/upgrade/request',[\App\Http\Controllers\Admin\UpgradeController::class, 'request'])->name('upgrade.request');
Route::post('/upgrade/extend-trial',[\App\Http\Controllers\Admin\UpgradeController::class, 'extendTrial'])->name('upgrade.extend-trial');

// ── Public Pricing Page (no auth required — shareable link) ─────────────────
Route::withoutMiddleware([
    \App\Http\Middleware\SetHotelContext::class,
    \App\Http\Middleware\CheckTrialStatus::class,
])->group(function () {
    Route::get('/pricing',          [\App\Http\Controllers\PublicPricingController::class, 'index'])  ->name('pricing');
    Route::post('/pricing/enquire', [\App\Http\Controllers\PublicPricingController::class, 'enquire'])->name('pricing.enquire');
});

// ── Super Admin Hotel Filter ─────────────────────────────────────────────────
Route::post('/super-admin/hotel-filter', [SaHotelFilterController::class, 'filter'])->name('sa.hotel.filter');

// ── Dashboard (all logged-in users) ────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── Help / User Guide (bilingual EN/HI, all logged-in users) ───────────────
Route::get('/help', fn() => view('admin.help.guide'))->name('help.guide');

// ── Customers ──────────────────────────────────────────────────────────────
Route::get('/customers',           [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/create',    [CustomerController::class, 'create'])->middleware('permission:guests.create')->name('customers.create');
Route::post('/customers',            [CustomerController::class, 'store']      )->middleware('permission:guests.create')->name('customers.store');
Route::post('/customers/quick-store',[CustomerController::class, 'quickStore'])->middleware('permission:guests.create')->name('customers.quickStore');
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
Route::get('/bookings',                      [BookingController::class, 'index']             )->name('bookings.index');
Route::get('/bookings/create',               [BookingController::class, 'create']            )->middleware('permission:bookings.create')->name('bookings.create');
Route::get('/bookings/available-time-slots', [BookingController::class, 'availableTimeSlots'])->name('bookings.available_time_slots');
Route::get('/bookings/available-rooms',      [BookingController::class, 'availableRooms']     )->name('bookings.available_rooms');
Route::post('/bookings',                     [BookingController::class, 'store']             )->middleware('permission:bookings.create')->name('bookings.store');
Route::get('/bookings/{id}/edit',            [BookingController::class, 'edit']              )->middleware('permission:bookings.edit')->name('bookings.edit');
Route::put('/bookings/{id}',                 [BookingController::class, 'update']            )->middleware('permission:bookings.edit')->name('bookings.update');
Route::delete('/bookings/{id}',              [BookingController::class, 'destroy']           )->middleware('permission:bookings.delete')->name('bookings.destroy');
Route::get('/bookings/{id}',                 [BookingController::class, 'show']              )->name('bookings.show');
Route::post('/bookings/{booking}/extra-charges',                [BookingExtraChargeController::class, 'store']  )->name('bookings.extra_charges.store');
Route::delete('/bookings/{booking}/extra-charges/{charge}',    [BookingExtraChargeController::class, 'destroy'])->name('bookings.extra_charges.destroy');

Route::get('/food-billing',           [FoodBillingController::class, 'index'])->name('food-billing.index');
Route::get('/food-billing/{booking}', [FoodBillingController::class, 'show']) ->name('food-billing.show');

// ── Check-In ───────────────────────────────────────────────────────────────
Route::get('/checkin',       [CheckInController::class, 'index']  )->name('checkin.index');
Route::get('/checkin/{id}',  [CheckInController::class, 'show']   )->name('checkin.show');
Route::post('/checkin/{id}', [CheckInController::class, 'process'])->middleware('permission:checkin.process')->name('checkin.process');

// ── Check-Out ──────────────────────────────────────────────────────────────
Route::get('/checkout',            [CheckOutController::class, 'index']  )->name('checkout.index');
Route::get('/checkout/{id}',       [CheckOutController::class, 'show']   )->name('checkout.show');
Route::post('/checkout/{id}',      [CheckOutController::class, 'process'])->middleware('permission:checkout.process')->name('checkout.process');
Route::post('/checkout/{id}/void', [CheckOutController::class, 'void']   )->name('checkout.void');

// ── Payments ───────────────────────────────────────────────────────────────
Route::get('/payments',        [PaymentController::class, 'index'])->name('payments.index');
Route::get('/payments/create', [PaymentController::class, 'create'])->middleware('permission:payments.create')->name('payments.create');
Route::post('/payments',       [PaymentController::class, 'store'] )->middleware('permission:payments.create')->name('payments.store');
Route::delete('/payments/{id}',[PaymentController::class, 'destroy'])->middleware('permission:payments.delete')->name('payments.destroy');
Route::get('/payments/{id}',   [PaymentController::class, 'show'] )->name('payments.show');

// ── Invoices ───────────────────────────────────────────────────────────────
Route::get('/invoices',                       [InvoiceController::class, 'index']   )->name('invoices.index');
Route::get('/invoices/trash',                 [InvoiceController::class, 'trash']   )->middleware('permission:invoices.delete')->name('invoices.trash');
Route::post('/invoices/{id}/restore',         [InvoiceController::class, 'restore'] )->middleware('permission:invoices.delete')->name('invoices.restore');
Route::get('/invoices/{id}/print',            [InvoiceController::class, 'print']      )->name('invoices.print');
Route::get('/invoices/{id}/print-gst',        [InvoiceController::class, 'printGst']   )->name('invoices.print-gst');
Route::get('/invoices/{id}/download-pdf',     [InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');
Route::get('/invoices/{id}/edit',             [InvoiceController::class, 'edit']    )->name('invoices.edit');
Route::put('/invoices/{id}',                  [InvoiceController::class, 'update']  )->name('invoices.update');
Route::delete('/invoices/{id}',               [InvoiceController::class, 'destroy'] )->middleware('permission:invoices.delete')->name('invoices.destroy');
Route::get('/invoices/{id}',                  [InvoiceController::class, 'show']    )->name('invoices.show');

// ── Reports ────────────────────────────────────────────────────────────────
Route::middleware('permission:reports.view')->group(function () {
    Route::get('/reports',           [ReportController::class, 'index']    )->name('reports.index');
    Route::get('/reports/performance', [ReportController::class, 'performance'])->name('reports.performance');
    Route::get('/reports/revenue',   [ReportController::class, 'revenue']  )->name('reports.revenue');
    Route::get('/reports/occupancy',       [ReportController::class, 'occupancy']     )->name('reports.occupancy');
    Route::get('/reports/bookings',        [ReportController::class, 'bookings']      )->name('reports.bookings');
    Route::get('/reports/guest-register',  [ReportController::class, 'guestRegister'] )->name('reports.guest_register');
    Route::get('/reports/inventory-stock',     [ReportController::class, 'inventoryStock']     )->name('reports.inventory_stock');
    Route::get('/reports/inventory-movements', [ReportController::class, 'inventoryMovements'] )->name('reports.inventory_movements');
});

// ── Dashboard JSON endpoint for Revenue Trend widget ──────────────────────
Route::get('/dashboard/revenue-trend', [\App\Http\Controllers\Admin\DashboardController::class, 'revenueTrend'])
    ->name('dashboard.revenue_trend');

// ── Settings ───────────────────────────────────────────────────────────────
Route::middleware('permission:settings.view')->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.edit')->name('settings.update');
});


// ── Activity Log ───────────────────────────────────────────────────────────
Route::get('/activity-log', [ActivityLogController::class, 'index'])
    ->middleware('permission:activity_log.view')
    ->name('activity_log.index');

// ── Data Cleanup ────────────────────────────────────────────────────────────
Route::middleware('permission:data.truncate')->group(function () {
    Route::get('/data-cleanup',  [DataCleanupController::class, 'index']   )->name('data-cleanup.index');
    Route::post('/data-cleanup', [DataCleanupController::class, 'truncate'])->name('data-cleanup.truncate');
});

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

// ── Time Slot Pricing ──────────────────────────────────────────────────────
Route::get('/settings/time-slots',                    [TimeSlotController::class, 'index']      )->name('time-slots.index');
Route::post('/settings/time-slots',                   [TimeSlotController::class, 'store']      )->name('time-slots.store');
Route::put('/settings/time-slots/{timeSlot}',         [TimeSlotController::class, 'update']     )->name('time-slots.update');
Route::post('/settings/time-slots/{timeSlot}/toggle', [TimeSlotController::class, 'toggle']     )->name('time-slots.toggle');
Route::post('/settings/time-slots/reorder',           [TimeSlotController::class, 'reorder']    )->name('time-slots.reorder');
Route::delete('/settings/time-slots/{timeSlot}',      [TimeSlotController::class, 'destroy']    )->name('time-slots.destroy');
Route::post('/settings/add-ons',                      [TimeSlotController::class, 'addOnStore'] )->name('add-ons.store');
Route::delete('/settings/add-ons/{id}',               [TimeSlotController::class, 'addOnDestroy'])->name('add-ons.destroy');

// ── Dashboard calendar day-summary ─────────────────────────────────────────
Route::get('/calendar/day-summary',      [DashboardController::class, 'daySummary']       )->name('calendar.day_summary');
Route::get('/dashboard/availability',    [DashboardController::class, 'checkAvailability'])->name('dashboard.availability');
Route::get('/dashboard/live-feed',       [DashboardController::class, 'liveFeed']          )->name('dashboard.live_feed');
Route::get('/dashboard/kpi-live',        [DashboardController::class, 'kpiLive']           )->name('dashboard.kpi_live');

// ── Slot Search Engine ──────────────────────────────────────────────────────
use App\Http\Controllers\Admin\SlotSearchController;
Route::get('/slot-search', [SlotSearchController::class, 'index'])->name('slot-search.index');
Route::get('/slot-search/pdf', [SlotSearchController::class, 'pdf'])->name('slot-search.pdf');

// ── Dashboard preferences ───────────────────────────────────────────────────
use App\Http\Controllers\Admin\DashboardPreferenceController;
Route::post('/dashboard/preferences/save',         [DashboardPreferenceController::class, 'save']       )->name('dashboard.preferences.save');
Route::post('/dashboard/preferences/save-default', [DashboardPreferenceController::class, 'saveDefault'])->name('dashboard.preferences.save_default');
Route::post('/dashboard/preferences/reset',        [DashboardPreferenceController::class, 'reset']      )->name('dashboard.preferences.reset');

// ── Slot Availability Report ────────────────────────────────────────────────
Route::get('/reports/slot-availability',        [ReportController::class, 'slotAvailability']      )->middleware('permission:reports.view')->name('reports.slot_availability');
Route::get('/reports/slot-availability/export', [ReportController::class, 'slotAvailabilityExport'])->middleware('permission:reports.view')->name('reports.slot_availability.export');
Route::get('/reports/slot-bookings',            [ReportController::class, 'slotBookings']           )->middleware('permission:reports.view')->name('reports.slot_bookings');
Route::get('/reports/slot-bookings/export',     [ReportController::class, 'slotBookingsExport']     )->middleware('permission:reports.view')->name('reports.slot_bookings.export');

// ── WhatsApp — Setup Wizard ────────────────────────────────────────────────
Route::get('/whatsapp/setup',                           [WhatsAppSetupController::class, 'index']            )->name('whatsapp.setup');
Route::post('/whatsapp/setup/activate-shared',          [WhatsAppSetupController::class, 'activateShared']   )->name('whatsapp.setup.activate-shared');
Route::post('/whatsapp/setup/embedded-complete',        [WhatsAppSetupController::class, 'embeddedComplete'] )->name('whatsapp.setup.embedded-complete');
Route::post('/whatsapp/setup/resume',                   [WhatsAppSetupController::class, 'resumeSetup']      )->name('whatsapp.setup.resume');
Route::post('/whatsapp/setup/retry-step',               [WhatsAppSetupController::class, 'retryStep']        )->name('whatsapp.setup.retry-step');
Route::post('/whatsapp/setup/reset',                    [WhatsAppSetupController::class, 'reset']            )->name('whatsapp.setup.reset');
Route::post('/whatsapp/setup/test-shared',              [WhatsAppSetupController::class, 'testShared']       )->name('whatsapp.setup.test-shared');

// ── WhatsApp — Templates & Automations ────────────────────────────────────
Route::get('/whatsapp/automations',                     [WhatsAppController::class, 'templates']       )->name('whatsapp.templates');
Route::get('/whatsapp/automations/create',              [WhatsAppController::class, 'templateCreate']  )->name('whatsapp.template.create');
Route::post('/whatsapp/automations',                    [WhatsAppController::class, 'templateStore']   )->name('whatsapp.template.store');
Route::get('/whatsapp/automations/{template}/edit',     [WhatsAppController::class, 'templateEdit']    )->name('whatsapp.template.edit');
Route::put('/whatsapp/automations/{template}',          [WhatsAppController::class, 'templateSave']    )->name('whatsapp.template.save');
Route::delete('/whatsapp/automations/{template}',       [WhatsAppController::class, 'templateDestroy'] )->name('whatsapp.template.destroy');
Route::post('/whatsapp/automations/{template}/toggle',       [WhatsAppController::class, 'templateToggle']  )->name('whatsapp.template.toggle');
Route::post('/whatsapp/automations/{template}/submit-meta',  [WhatsAppController::class, 'submitToMeta']    )->name('whatsapp.template.submit-meta');
Route::post('/whatsapp/automations/sync-from-meta',          [WhatsAppController::class, 'syncFromMeta']          )->name('whatsapp.template.sync-meta');
Route::post('/whatsapp/automations/toggle-platform',         [WhatsAppController::class, 'togglePlatformTemplates'])->name('whatsapp.toggle-platform-templates');
Route::post('/whatsapp/automations/{template}/customize',    [WhatsAppController::class, 'templateCustomize']      )->name('whatsapp.template.customize');
Route::post('/whatsapp/test-send',                      [WhatsAppController::class, 'testSend']        )->name('whatsapp.test.send');
Route::post('/whatsapp/test-json',                      [WhatsAppController::class, 'testSendJson']    )->name('whatsapp.test.json');

// Legacy config redirect (keep old URLs working)
Route::get('/whatsapp/config',   fn() => redirect()->route('whatsapp.setup'))->name('whatsapp.config');
Route::post('/whatsapp/config',  fn() => redirect()->route('whatsapp.setup'))->name('whatsapp.config.save');

// ── Push Notification FCM Tokens (Hotel CRM users) ────────────────────────
Route::post('/notifications/fcm-token',         [\App\Http\Controllers\Admin\FcmTokenController::class, 'store']     )->name('fcm.token.store');
Route::delete('/notifications/fcm-token',       [\App\Http\Controllers\Admin\FcmTokenController::class, 'destroy']   )->name('fcm.token.destroy');
Route::get('/notifications/unread',             [\App\Http\Controllers\Admin\FcmTokenController::class, 'unread']    )->name('fcm.notifications.unread');
Route::post('/notifications/{id}/read',         [\App\Http\Controllers\Admin\FcmTokenController::class, 'markRead']  )->name('fcm.notifications.read');
Route::get('/api/crm/firebase-config',          [\App\Http\Controllers\Admin\FcmTokenController::class, 'firebaseConfig'])->name('fcm.config');

// ── Payment Links ──────────────────────────────────────────────────────────
Route::get('/payment-links/config',                           [PaymentLinksController::class, 'config']          )->name('payment_links.config');
Route::post('/payment-links/config',                          [PaymentLinksController::class, 'configSave']      )->name('payment_links.config.save');
Route::post('/payment-links/invoices/{id}/razorpay',          [PaymentLinksController::class, 'razorpayCreate']  )->name('payment_links.razorpay.create');
Route::get('/payment-links/invoices/{id}/upi-qr',             [PaymentLinksController::class, 'upiQr']           )->name('payment_links.upi_qr');
Route::get('/payment-links/upi-config',                       [PaymentLinksController::class, 'upiConfig']       )->name('payment_links.upi_config');
Route::post('/payment-links/booking/{id}/razorpay',           [PaymentLinksController::class, 'razorpayForBooking'])->name('payment_links.booking.razorpay');
Route::get('/payment-links/razorpay/webhook',                 [PaymentLinksController::class, 'razorpayWebhook'] )->name('payment_links.razorpay.webhook')->withoutMiddleware(['web']);

// ── OTA Email Parser ──────────────────────────────────────────────────────
Route::get('/email-parser/config',                  [\App\Http\Controllers\Admin\EmailParserController::class, 'index']           )->name('email-parser.config');
Route::post('/email-parser/config',                 [\App\Http\Controllers\Admin\EmailParserController::class, 'saveConfig']      )->name('email-parser.config.save');
Route::post('/email-parser/test-connection',        [\App\Http\Controllers\Admin\EmailParserController::class, 'testConnection'] )->name('email-parser.test-connection');
Route::post('/email-parser/toggle-active',          [\App\Http\Controllers\Admin\EmailParserController::class, 'toggleActive']   )->name('email-parser.toggle-active');
Route::get('/email-parser/logs',                    [\App\Http\Controllers\Admin\EmailParserController::class, 'logs']            )->name('email-parser.logs');
Route::get('/email-parser/conflicts',               [\App\Http\Controllers\Admin\EmailParserController::class, 'conflicts']       )->name('email-parser.conflicts');
Route::post('/email-parser/conflicts/{id}/resolve', [\App\Http\Controllers\Admin\EmailParserController::class, 'resolveConflict'])->name('email-parser.conflicts.resolve');

// ── OTA WhatsApp Sync — Import Queue ───────────────────────────────────────
Route::get('/ota-bookings',                   [\App\Http\Controllers\Admin\OtaBookingController::class, 'index']  )->name('ota-bookings.index');
Route::get('/ota-bookings/history',           [\App\Http\Controllers\Admin\OtaBookingController::class, 'history'])->name('ota-bookings.history');
Route::post('/ota-bookings/simulate',         [\App\Http\Controllers\Admin\OtaBookingController::class, 'simulate'])->name('ota-bookings.simulate');
Route::post('/ota-bookings/{import}/confirm', [\App\Http\Controllers\Admin\OtaBookingController::class, 'confirm'])->name('ota-bookings.confirm');
Route::post('/ota-bookings/{import}/reject',  [\App\Http\Controllers\Admin\OtaBookingController::class, 'reject'] )->name('ota-bookings.reject');
Route::put('/ota-bookings/{import}',          [\App\Http\Controllers\Admin\OtaBookingController::class, 'update'] )->name('ota-bookings.update');
// Hotel-scoped aliases (explicit hotel ID in URL for direct access without session context)
Route::get('/hotel/{hotelId}/ota-bookings',              [\App\Http\Controllers\Admin\OtaBookingController::class, 'index']  )->name('ota-bookings.hotel.index');
Route::get('/hotel/{hotelId}/ota-bookings/history',      [\App\Http\Controllers\Admin\OtaBookingController::class, 'history'])->name('ota-bookings.hotel.history');
Route::post('/hotel/{hotelId}/ota-bookings/{import}/confirm', [\App\Http\Controllers\Admin\OtaBookingController::class, 'confirm'])->name('ota-bookings.hotel.confirm');
Route::post('/hotel/{hotelId}/ota-bookings/{import}/reject',  [\App\Http\Controllers\Admin\OtaBookingController::class, 'reject'] )->name('ota-bookings.hotel.reject');

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

// ── Public Booking Widget (no auth, no hotel-context, CSRF exempted via bootstrap/app.php) ──
Route::withoutMiddleware([
    \App\Http\Middleware\SetHotelContext::class,
    \App\Http\Middleware\CheckTrialStatus::class,
])->group(function () {
    // Standalone full-page booking form
    Route::get( '/book/{slug}',              [\App\Http\Controllers\PublicBookingController::class, 'show']            )->name('public.booking.show');
    // iFrame-optimised version (embedded in hotel website)
    Route::get( '/book/{slug}/iframe',       [\App\Http\Controllers\PublicBookingController::class, 'iframe']          )->name('public.booking.iframe');
    // JS floating widget (canonical path)
    Route::get( '/widget/{slug}/embed.js',   [\App\Http\Controllers\PublicBookingController::class, 'embedJs']         )->name('public.booking.embed_js');
    // Availability AJAX (POST to carry body params, no CSRF — hotel token validated server-side)
    Route::post('/book/{slug}/availability', [\App\Http\Controllers\PublicBookingController::class, 'availability']    )->name('public.booking.availability');
    // Submit booking (POST, hotel token validated server-side)
    Route::post('/book/{slug}/book',         [\App\Http\Controllers\PublicBookingController::class, 'store']           )->name('public.booking.store');
    // Confirmation page
    Route::get( '/book/{slug}/confirm/{ref}',[\App\Http\Controllers\PublicBookingController::class, 'confirm']         )->name('public.booking.confirm');
    // ICS calendar download
    Route::get( '/book/{slug}/confirm/{ref}/ical', [\App\Http\Controllers\PublicBookingController::class, 'ical']      )->name('public.booking.ical');
    // Guest submits UPI UTR
    Route::post('/book/{slug}/payment-ref',  [\App\Http\Controllers\PublicBookingController::class, 'submitPaymentRef'])->name('public.booking.payment_ref');

    /* ── Standalone Food Menu module is dormant. Routes below are kept for
     *     reference but commented out. The QR + scan-to-order
     *     flow now lives in the Restaurant module (/r/{slug}/...).
     *
     * Route::get( '/menu/{slug}',                        [FoodMenuPublicController::class, 'show']  )->name('public.food-menu.show.no-room');
     * Route::get( '/menu/{slug}/order/{number}',         [FoodMenuPublicController::class, 'status'])->name('public.food-menu.status');
     * Route::post('/menu/{slug}/order',                  [FoodMenuPublicController::class, 'order'] )->name('public.food-menu.order');
     * Route::get( '/menu/{slug}/{room}',                 [FoodMenuPublicController::class, 'show']  )->name('public.food-menu.show')->where('room', '[A-Za-z0-9_\-]+');
     */

    // ── Public Restaurant Menu (QR scan-to-order) ──
    Route::get( '/r/{slug}',                       [\App\Http\Controllers\RestaurantPublicController::class, 'show']      )->name('public.restaurant.show');
    Route::get( '/r/{slug}/order/{number}',        [\App\Http\Controllers\RestaurantPublicController::class, 'status']    )->name('public.restaurant.status');
    Route::post('/r/{slug}/order',                 [\App\Http\Controllers\RestaurantPublicController::class, 'order']     )->name('public.restaurant.order');
    Route::get( '/r/{slug}/room/{room}',           [\App\Http\Controllers\RestaurantPublicController::class, 'showRoom']  )->name('public.restaurant.show.room')->where('room', '[A-Za-z0-9_\-]+');
    Route::get( '/r/{slug}/table/{table}',         [\App\Http\Controllers\RestaurantPublicController::class, 'showTable'] )->name('public.restaurant.show.table')->where('table', '[A-Za-z0-9_\- ]+');
});

// ── Admin: Booking Widget Settings ──────────────────────────────────────────
Route::get( '/booking-widget/settings',        [\App\Http\Controllers\Admin\BookingWidgetSettingsController::class, 'index']         )->name('admin.booking-widget.settings');
Route::put( '/booking-widget/settings',        [\App\Http\Controllers\Admin\BookingWidgetSettingsController::class, 'update']        )->name('admin.booking-widget.settings.update');
Route::post('/booking-widget/confirm/{id}',    [\App\Http\Controllers\Admin\BookingWidgetSettingsController::class, 'confirmBooking'])->name('admin.booking-widget.confirm');

// ── Restaurant Module ───────────────────────────────────────────────────────
Route::middleware('permission:restaurant.view')->prefix('restaurant')->name('restaurant.')->group(function () {

    // Table Map
    Route::get('/',                    [RestaurantController::class, 'index']      )->name('index');

    // Tables management
    Route::middleware('permission:restaurant.tables')->group(function () {
        Route::post('/tables',             [RestaurantController::class, 'tableStore']  )->name('tables.store');
        Route::put('/tables/{id}',         [RestaurantController::class, 'tableUpdate'] )->name('tables.update');
        Route::delete('/tables/{id}',      [RestaurantController::class, 'tableDestroy'])->name('tables.destroy');
        Route::post('/tables/{id}/status', [RestaurantController::class, 'tableStatus'] )->name('tables.status');
    });

    // Menu management
    Route::middleware('permission:restaurant.menu')->group(function () {
        Route::get('/menu',                    [RestaurantMenuController::class, 'index']          )->name('menu.index');
        Route::post('/menu/categories',        [RestaurantMenuController::class, 'categoryStore']  )->name('menu.categories.store');
        Route::put('/menu/categories/{id}',    [RestaurantMenuController::class, 'categoryUpdate'] )->name('menu.categories.update');
        Route::delete('/menu/categories/{id}', [RestaurantMenuController::class, 'categoryDestroy'])->name('menu.categories.destroy');
        Route::post('/menu/items',             [RestaurantMenuController::class, 'itemStore']      )->name('menu.items.store');
        Route::put('/menu/items/{id}',         [RestaurantMenuController::class, 'itemUpdate']     )->name('menu.items.update');
        Route::delete('/menu/items/{id}',      [RestaurantMenuController::class, 'itemDestroy']    )->name('menu.items.destroy');
        Route::post('/menu/items/{id}/toggle', [RestaurantMenuController::class, 'itemToggle']     )->name('menu.items.toggle');
    });

    // Orders
    Route::middleware('permission:restaurant.orders')->group(function () {
        Route::get('/orders',              [RestaurantOrderController::class, 'index']      )->name('orders.index');
        Route::get('/orders/{id}',         [RestaurantOrderController::class, 'show']       )->name('orders.show');
        Route::post('/orders',             [RestaurantOrderController::class, 'store']      )->name('orders.store');
        Route::put('/orders/{id}',         [RestaurantOrderController::class, 'update']     )->name('orders.update');
        Route::post('/orders/{id}/kot',    [RestaurantOrderController::class, 'printKot']   )->name('orders.kot');
        Route::get('/orders/{id}/kot-print',[RestaurantOrderController::class, 'kotPrint']  )->name('orders.kot.print');
        Route::post('/orders/{id}/cancel', [RestaurantOrderController::class, 'cancel']     )->name('orders.cancel');
        Route::post('/orders/{id}/items',  [RestaurantOrderController::class, 'addItem']    )->name('orders.items.add');
        Route::delete('/orders/{id}/items/{itemId}', [RestaurantOrderController::class, 'removeItem'])->name('orders.items.remove');
        Route::patch ('/orders/{id}/items/{itemId}', [RestaurantOrderController::class, 'updateItemQty'])->name('orders.items.qty');
        // Guest QR order approval flow
        Route::post('/orders/{id}/approve', [RestaurantOrderController::class, 'approve']  )->name('orders.approve');
        Route::post('/orders/{id}/reject',  [RestaurantOrderController::class, 'reject']   )->name('orders.reject');
    });

    // QR codes for tables and rooms (Restaurant module)
    Route::middleware('permission:restaurant.menu')->group(function () {
        Route::get('/menu/qr',          [\App\Http\Controllers\Admin\RestaurantQrController::class, 'index']   )->name('qr.index');
        Route::get('/menu/qr/pdf',      [\App\Http\Controllers\Admin\RestaurantQrController::class, 'pdf']     )->name('qr.pdf');
        Route::get('/menu/qr/download', [\App\Http\Controllers\Admin\RestaurantQrController::class, 'download'])->name('qr.download');
    });

    // Billing
    Route::middleware('permission:restaurant.billing')->group(function () {
        Route::get('/bills',               [RestaurantBillController::class, 'index']       )->name('bills.index');
        Route::post('/bills',              [RestaurantBillController::class, 'store']       )->name('bills.store');
        Route::get('/bills/{id}',          [RestaurantBillController::class, 'show']        )->name('bills.show');
        Route::get('/bills/{id}/print',    [RestaurantBillController::class, 'print']       )->name('bills.print');
    });

    // Reports
    Route::get('/reports', [RestaurantController::class, 'reports'])
        ->middleware('permission:restaurant.reports')
        ->name('reports');
});


// ── Inventory Management ─────────────────────────────────────────────────────
Route::middleware('permission:inventory.view')->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/',                    [\App\Http\Controllers\Admin\InventoryController::class, 'index']          )->name('index');
    // Static paths FIRST — before /{id} wildcard to avoid conflict
    Route::get('/create',              [\App\Http\Controllers\Admin\InventoryController::class, 'create']         )->middleware('permission:inventory.create')->name('create');
    Route::post('/',                   [\App\Http\Controllers\Admin\InventoryController::class, 'store']          )->middleware('permission:inventory.create')->name('store');
    Route::get('/categories',          [\App\Http\Controllers\Admin\InventoryController::class, 'categories']     )->name('categories');
    Route::post('/categories',         [\App\Http\Controllers\Admin\InventoryController::class, 'categoryStore']  )->middleware('permission:inventory.create')->name('categories.store');
    Route::put('/categories/{id}',     [\App\Http\Controllers\Admin\InventoryController::class, 'categoryUpdate'] )->middleware('permission:inventory.edit')->name('categories.update');
    Route::delete('/categories/{id}',  [\App\Http\Controllers\Admin\InventoryController::class, 'categoryDestroy'])->middleware('permission:inventory.delete')->name('categories.destroy');
    // Dynamic /{id} paths after static ones
    Route::get('/{id}/edit',           [\App\Http\Controllers\Admin\InventoryController::class, 'edit']           )->middleware('permission:inventory.edit')->name('edit');
    Route::put('/{id}',                [\App\Http\Controllers\Admin\InventoryController::class, 'update']         )->middleware('permission:inventory.edit')->name('update');
    Route::delete('/{id}',             [\App\Http\Controllers\Admin\InventoryController::class, 'destroy']        )->middleware('permission:inventory.delete')->name('destroy');
    Route::get('/{id}/movements',      [\App\Http\Controllers\Admin\InventoryController::class, 'movements']      )->name('movements');
    Route::post('/{id}/adjust',        [\App\Http\Controllers\Admin\InventoryController::class, 'adjust']         )->middleware('permission:inventory.adjust')->name('adjust');
    Route::post('/{id}/purchase',      [\App\Http\Controllers\Admin\InventoryController::class, 'purchase']       )->middleware('permission:inventory.create')->name('purchase');
    Route::post('/{id}/usage',         [\App\Http\Controllers\Admin\InventoryController::class, 'usage']          )->middleware('permission:inventory.adjust')->name('usage');
});


/* ── Standalone Food Menu / Food Orders modules are dormant.
 *     All scan-to-order flow now lives inside the Restaurant module
 *     (admin: /restaurant/menu/qr  •  guest: /r/{slug}/...).
 *     Code, controllers, models, tables and permissions are kept intact
 *     so the module can be re-enabled later without data loss.
 *
 * Route::middleware('permission:food_menu.manage')->prefix('food-menu')->name('food-menu.')->group(function () {
 *     Route::get('/',                    [FoodMenuAdminController::class, 'dashboard']        )->name('dashboard');
 *     Route::get('/admin',               [FoodMenuAdminController::class, 'dashboard']        )->name('admin');
 *     Route::get('/categories',          [FoodMenuAdminController::class, 'categories']       )->name('categories');
 *     Route::post('/categories',         [FoodMenuAdminController::class, 'categoryStore']    )->name('categories.store');
 *     Route::put('/categories/{id}',     [FoodMenuAdminController::class, 'categoryUpdate']   )->name('categories.update');
 *     Route::delete('/categories/{id}',  [FoodMenuAdminController::class, 'categoryDestroy']  )->name('categories.destroy');
 *     Route::get('/items/create',        [FoodMenuAdminController::class, 'itemCreate']       )->name('items.create');
 *     Route::post('/items',              [FoodMenuAdminController::class, 'itemStore']        )->name('items.store');
 *     Route::get('/qr',                  [FoodMenuAdminController::class, 'qr']               )->name('qr');
 *     Route::get('/qr/download',         [FoodMenuAdminController::class, 'qrDownload']       )->name('qr.download');
 *     Route::get('/qr/pdf',              [FoodMenuAdminController::class, 'qrPdf']            )->name('qr.pdf');
 *     Route::get('/items/{id}/edit',     [FoodMenuAdminController::class, 'itemEdit']         )->name('items.edit');
 *     Route::put('/items/{id}',          [FoodMenuAdminController::class, 'itemUpdate']       )->name('items.update');
 *     Route::delete('/items/{id}',       [FoodMenuAdminController::class, 'itemDestroy']      )->name('items.destroy');
 *     Route::post('/items/{id}/toggle',  [FoodMenuAdminController::class, 'itemToggle']       )->name('items.toggle');
 * });
 *
 * Route::middleware('permission:food_menu.orders.view')->prefix('food-orders')->name('food-orders.')->group(function () {
 *     Route::get('/',                    [FoodOrderController::class, 'index']                )->name('index');
 *     Route::get('/report',              [FoodOrderController::class, 'report']               )->name('report');
 *     Route::get('/{id}',                [FoodOrderController::class, 'show']                 )->name('show');
 *     Route::get('/{id}/kot',            [FoodOrderController::class, 'kotPrint']             )->name('kot');
 *     Route::middleware('permission:food_menu.orders.manage')->group(function () {
 *         Route::post('/{id}/status',    [FoodOrderController::class, 'status']               )->name('status');
 *         Route::post('/{id}/edit-item', [FoodOrderController::class, 'editItem']             )->name('edit-item');
 *         Route::post('/{id}/add-item',  [FoodOrderController::class, 'addItem']              )->name('add-item');
 *     });
 * });
 */


// ── Pathik Autofill ─────────────────────────────────────────────────────────
Route::get( '/pathik',                 [\App\Http\Controllers\Admin\PathikController::class, 'index']           )->name('pathik.index');
Route::post('/pathik/token/regenerate',[\App\Http\Controllers\Admin\PathikController::class, 'regenerateToken'] )->name('pathik.token.regenerate');
Route::post('/pathik/pending',         [\App\Http\Controllers\Admin\PathikController::class, 'pendingStore']    )->name('pathik.pending.store');
Route::post('/pathik/clear',           [\App\Http\Controllers\Admin\PathikController::class, 'clearPending']    )->name('pathik.clear');
// Extension endpoint — no session auth, validates api_token param instead
Route::get( '/pathik/pending',         [\App\Http\Controllers\Admin\PathikController::class, 'pendingFetch']    )->name('pathik.pending.fetch')->withoutMiddleware(['web']);


// ── Platform Admin Login (no auth required) ──────────────────────────────────
Route::get('/platform/login',         [\App\Http\Controllers\Platform\AuthController::class, 'showLogin'])->name('platform.login');
Route::post('/platform/login',        [\App\Http\Controllers\Platform\AuthController::class, 'login'])->name('platform.login.post');
Route::get('/platform/login/verify-2fa',  [\App\Http\Controllers\Platform\AuthController::class, 'show2faVerify'])->name('platform.login.2fa');
Route::post('/platform/login/verify-2fa', [\App\Http\Controllers\Platform\AuthController::class, 'verify2fa'])->name('platform.login.2fa.post');

// ── Platform Admin (Super Admin only) ───────────────────────────────────────
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\HotelController as PlatformHotelController;

Route::prefix('platform')->middleware('platform.admin')->group(function () {
    Route::get('/',          fn() => redirect()->route('platform.dashboard'))->name('platform.index');
    Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');
    Route::post('/dismiss-reminder', function () {
        session(['platform_reminder_dismissed' => true]);
        return redirect()->back();
    })->name('platform.dismiss-reminder');

    // Hotel management (Task #8)
    Route::get('/hotels',                [PlatformHotelController::class, 'index'])->name('platform.hotels.index');
    Route::get('/hotels/create',         [PlatformHotelController::class, 'create'])->name('platform.hotels.create');
    Route::post('/hotels',               [PlatformHotelController::class, 'store'])->name('platform.hotels.store');
    Route::get('/hotels/{id}/edit',      [PlatformHotelController::class, 'edit'])->name('platform.hotels.edit');
    Route::put('/hotels/{id}',           [PlatformHotelController::class, 'update'])->name('platform.hotels.update');
    Route::post('/hotels/{id}/suspend',  [PlatformHotelController::class, 'suspend'])->name('platform.hotels.suspend');
    Route::post('/hotels/{id}/activate', [PlatformHotelController::class, 'activate'])->name('platform.hotels.activate');
    Route::delete('/hotels/{id}',        [PlatformHotelController::class, 'destroy'])->name('platform.hotels.destroy');
    Route::post('/hotels/{id}/users',         [PlatformHotelController::class, 'storeUser'])->name('platform.hotels.users.store');
    Route::post('/hotels/{id}/send-welcome',  [PlatformHotelController::class, 'sendWelcomeEmail'])->name('platform.hotels.send-welcome');
    Route::get('/hotels/{id}/view-in-crm',    [PlatformDashboardController::class, 'viewInCrm'])->name('platform.hotels.view-in-crm');

    // Plans management (Task #13)
    Route::get('/plans',          [\App\Http\Controllers\Platform\PlanController::class, 'index'])->name('platform.plans.index');
    Route::get('/plans/{id}/edit',[\App\Http\Controllers\Platform\PlanController::class, 'edit'])->name('platform.plans.edit');
    Route::put('/plans/{id}',     [\App\Http\Controllers\Platform\PlanController::class, 'update'])->name('platform.plans.update');

    // User management (Task #9)
    Route::get('/users',                                          [\App\Http\Controllers\Platform\UserController::class, 'index'])->name('platform.users.index');
    Route::get('/users/{id}/reset-password',                      [\App\Http\Controllers\Platform\UserController::class, 'showResetPassword'])->name('platform.users.reset.show');
    Route::post('/users/{id}/reset-password',                     [\App\Http\Controllers\Platform\UserController::class, 'resetPassword'])->name('platform.users.reset');
    Route::get('/users/{id}',                                     [\App\Http\Controllers\Platform\UserController::class, 'show'])->name('platform.users.show');
    Route::post('/users/{id}/hotel/{hotelId}/suspend',            [\App\Http\Controllers\Platform\UserController::class, 'suspend'])->name('platform.users.suspend');
    Route::post('/users/{id}/hotel/{hotelId}/activate',           [\App\Http\Controllers\Platform\UserController::class, 'activate'])->name('platform.users.activate');

    // Deleted Guests (Task #18)
    Route::get('/guests/deleted',        [\App\Http\Controllers\Platform\GuestController::class, 'deleted'])->name('platform.guests.deleted');
    Route::post('/guests/{id}/restore',  [\App\Http\Controllers\Platform\GuestController::class, 'restore'])->name('platform.guests.restore');

    // Trial management (Task #19)
    Route::post('/hotels/{id}/activate-trial',   [\App\Http\Controllers\Platform\HotelController::class, 'activateTrial'])->name('platform.hotels.activate-trial');
    Route::post('/hotels/{id}/extend-plan',      [\App\Http\Controllers\Platform\HotelController::class, 'extendPlan'])->name('platform.hotels.extend-plan');
    Route::post('/hotels/{id}/cancel-trial',     [\App\Http\Controllers\Platform\HotelController::class, 'cancelTrial'])->name('platform.hotels.cancel-trial');
    Route::post('/hotels/{id}/cancel-plan-expiry', [\App\Http\Controllers\Platform\HotelController::class, 'cancelPlanExpiry'])->name('platform.hotels.cancel-plan-expiry');
    Route::post('/hotels/{id}/add-related',        [\App\Http\Controllers\Platform\HotelController::class, 'addRelatedHotel'])->name('platform.hotels.add-related');
    Route::post('/hotels/{id}/send-quick-wa',      [\App\Http\Controllers\Platform\HotelController::class, 'sendQuickWA'])->name('platform.hotels.send-quick-wa');
    Route::post('/hotels/{id}/send-quick-push',    [\App\Http\Controllers\Platform\HotelController::class, 'sendQuickPushHotel'])->name('platform.hotels.send-quick-push');
    Route::post('/hotels/send-wa-all',             [\App\Http\Controllers\Platform\HotelController::class, 'sendWaAll'])->name('platform.hotels.send-wa-all');
    Route::post('/hotels/{id}/module-toggle',      [\App\Http\Controllers\Platform\HotelController::class, 'moduleToggle'])->name('platform.hotels.module-toggle');
    Route::get('/wa-templates',                    [\App\Http\Controllers\Platform\HotelController::class, 'fetchApprovedWaTemplates'])->name('platform.wa-templates');
    Route::post('/users/{id}/toggle-wa-consent',   [\App\Http\Controllers\Platform\UserController::class, 'toggleWaConsent'])->name('platform.users.toggle-wa-consent');

    // 2FA settings
    Route::get('/settings/2fa',          [\App\Http\Controllers\Platform\AuthController::class, 'show2faSetup'])->name('platform.settings.2fa');
    Route::post('/settings/2fa/enable',  [\App\Http\Controllers\Platform\AuthController::class, 'enable2fa'])->name('platform.settings.2fa.enable');
    Route::post('/settings/2fa/disable', [\App\Http\Controllers\Platform\AuthController::class, 'disable2fa'])->name('platform.settings.2fa.disable');

    // Hotel Backups
    Route::get('/backups',                                 [\App\Http\Controllers\Platform\BackupController::class, 'index']  )->name('platform.backups.index');
    Route::post('/backups/{id}/restore',                   [\App\Http\Controllers\Platform\BackupController::class, 'restore'])->name('platform.backups.restore');

    // WhatsApp Platform Settings
    Route::get('/whatsapp',                                   [\App\Http\Controllers\Platform\WhatsAppController::class, 'settings']       )->name('platform.whatsapp.settings');
    Route::post('/whatsapp',                                  [\App\Http\Controllers\Platform\WhatsAppController::class, 'saveSettings']   )->name('platform.whatsapp.save');
    Route::post('/whatsapp/test',                             [\App\Http\Controllers\Platform\WhatsAppController::class, 'testSharedNumber'])->name('platform.whatsapp.test');
    Route::get('/whatsapp/templates',                         [\App\Http\Controllers\Platform\WhatsAppController::class, 'templates']       )->name('platform.whatsapp.templates');
    Route::post('/whatsapp/templates',                        [\App\Http\Controllers\Platform\WhatsAppController::class, 'templateStore']   )->name('platform.whatsapp.template.store');
    Route::put('/whatsapp/templates/{id}',                    [\App\Http\Controllers\Platform\WhatsAppController::class, 'templateSave']    )->name('platform.whatsapp.template.save');
    Route::delete('/whatsapp/templates/{id}',                 [\App\Http\Controllers\Platform\WhatsAppController::class, 'templateDestroy'] )->name('platform.whatsapp.template.destroy');
    Route::post('/whatsapp/templates/{id}/toggle',            [\App\Http\Controllers\Platform\WhatsAppController::class, 'templateToggle']  )->name('platform.whatsapp.template.toggle');
    Route::post('/whatsapp/templates/{id}/submit-meta',       [\App\Http\Controllers\Platform\WhatsAppController::class, 'submitToMeta']    )->name('platform.whatsapp.template.submit-meta');
    Route::post('/whatsapp/templates/sync-from-meta',         [\App\Http\Controllers\Platform\WhatsAppController::class, 'syncFromMeta']      )->name('platform.whatsapp.template.sync');
    Route::get('/whatsapp/logs',                              [\App\Http\Controllers\Platform\WhatsAppController::class, 'webhookLogs']         )->name('platform.whatsapp.logs');
    Route::post('/whatsapp/logs/clear',                       [\App\Http\Controllers\Platform\WhatsAppController::class, 'clearLogs']           )->name('platform.whatsapp.logs.clear');
    Route::get('/whatsapp/numbers',                           [\App\Http\Controllers\Platform\WhatsAppController::class, 'numbers']               )->name('platform.whatsapp.numbers');
    Route::post('/whatsapp/numbers',                          [\App\Http\Controllers\Platform\WhatsAppController::class, 'registerNumber']         )->name('platform.whatsapp.numbers.register');
    Route::post('/whatsapp/numbers/link',                     [\App\Http\Controllers\Platform\WhatsAppController::class, 'linkNumber']              )->name('platform.whatsapp.numbers.link');
    Route::post('/whatsapp/numbers/{configId}/request-otp',   [\App\Http\Controllers\Platform\WhatsAppController::class, 'requestOtp']             )->name('platform.whatsapp.numbers.request-otp');
    Route::post('/whatsapp/numbers/{configId}/verify',         [\App\Http\Controllers\Platform\WhatsAppController::class, 'verifyOtp']              )->name('platform.whatsapp.numbers.verify');
    Route::post('/whatsapp/numbers/{configId}/sync',           [\App\Http\Controllers\Platform\WhatsAppController::class, 'syncStatus']             )->name('platform.whatsapp.numbers.sync');
    Route::delete('/whatsapp/numbers/{configId}',              [\App\Http\Controllers\Platform\WhatsAppController::class, 'removeNumber']           )->name('platform.whatsapp.numbers.remove');

    // Analytics & Campaigns
    Route::get('/analytics',           [\App\Http\Controllers\Platform\AnalyticsController::class, 'index']        )->name('platform.analytics.index');
    Route::get('/analytics/campaigns', [\App\Http\Controllers\Platform\AnalyticsController::class, 'campaigns']    )->name('platform.analytics.campaigns');
    Route::post('/analytics/campaigns',[\App\Http\Controllers\Platform\AnalyticsController::class, 'sendCampaign'] )->name('platform.analytics.campaigns.send');

    // WA Inbox (Task #54)
    Route::get('/wa-inbox', fn() => view('platform.wa-inbox.index'))->name('platform.wa-inbox');

    // WhatsApp Billing
    Route::get('/whatsapp/billing',                         [\App\Http\Controllers\Platform\WhatsAppBillingController::class, 'index']     )->name('platform.whatsapp.billing');
    Route::post('/whatsapp/billing/{hotelId}/mark-paid',    [\App\Http\Controllers\Platform\WhatsAppBillingController::class, 'markPaid']  )->name('platform.whatsapp.billing.paid');
    Route::post('/whatsapp/billing/{hotelId}/mark-unpaid',  [\App\Http\Controllers\Platform\WhatsAppBillingController::class, 'markUnpaid'])->name('platform.whatsapp.billing.unpaid');
    Route::post('/whatsapp/billing/{hotelId}/limit',        [\App\Http\Controllers\Platform\WhatsAppBillingController::class, 'saveLimit'] )->name('platform.whatsapp.billing.limit');
    Route::post('/wa/upload-media', [\App\Http\Controllers\Platform\WhatsAppController::class, 'uploadMedia'])->name('platform.wa.upload-media');

    // OTA WhatsApp Sources
    Route::get('/ota-sources',                                [\App\Http\Controllers\Platform\OtaSourceController::class, 'index']  )->name('platform.ota-sources.index');
    Route::post('/ota-sources',                               [\App\Http\Controllers\Platform\OtaSourceController::class, 'store']  )->name('platform.ota-sources.store');
    Route::put('/ota-sources/{otaSource}',                    [\App\Http\Controllers\Platform\OtaSourceController::class, 'update'] )->name('platform.ota-sources.update');
    Route::delete('/ota-sources/{otaSource}',                 [\App\Http\Controllers\Platform\OtaSourceController::class, 'destroy'])->name('platform.ota-sources.destroy');
    Route::post('/ota-sources/{otaSource}/toggle',            [\App\Http\Controllers\Platform\OtaSourceController::class, 'toggle'] )->name('platform.ota-sources.toggle');

    // OTA Email Inbound Sources (per-hotel email address config)
    Route::get('/ota-email-sources',                              [\App\Http\Controllers\Platform\OtaEmailSourceController::class, 'index']  )->name('platform.ota-email-sources.index');
    Route::post('/ota-email-sources',                             [\App\Http\Controllers\Platform\OtaEmailSourceController::class, 'store']  )->name('platform.ota-email-sources.store');
    Route::put('/ota-email-sources/{otaEmailSource}',             [\App\Http\Controllers\Platform\OtaEmailSourceController::class, 'update'] )->name('platform.ota-email-sources.update');
    Route::delete('/ota-email-sources/{otaEmailSource}',          [\App\Http\Controllers\Platform\OtaEmailSourceController::class, 'destroy'])->name('platform.ota-email-sources.destroy');
    Route::post('/ota-email-sources/{otaEmailSource}/toggle',     [\App\Http\Controllers\Platform\OtaEmailSourceController::class, 'toggle'] )->name('platform.ota-email-sources.toggle');

    // Push Notifications (Platform Admin)
    Route::get('/notifications/settings',  [\App\Http\Controllers\Platform\PushNotificationsController::class, 'settings']    )->name('platform.notifications.settings');
    Route::post('/notifications/settings', [\App\Http\Controllers\Platform\PushNotificationsController::class, 'settingsSave'])->name('platform.notifications.settings.save');
    Route::get('/notifications/send',      [\App\Http\Controllers\Platform\PushNotificationsController::class, 'send']        )->name('platform.notifications.send');
    Route::post('/notifications/send',     [\App\Http\Controllers\Platform\PushNotificationsController::class, 'sendPost']    )->name('platform.notifications.send.post');
    Route::get('/notifications/history',   [\App\Http\Controllers\Platform\PushNotificationsController::class, 'history']     )->name('platform.notifications.history');
});
