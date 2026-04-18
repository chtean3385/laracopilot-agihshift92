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

// ── Super Admin Hotel Filter ─────────────────────────────────────────────────
Route::post('/super-admin/hotel-filter', [SaHotelFilterController::class, 'filter'])->name('sa.hotel.filter');

// ── Dashboard (all logged-in users) ────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
Route::post('/whatsapp/test-send',                      [WhatsAppController::class, 'testSend']        )->name('whatsapp.test.send');

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
});

// ── Admin: Booking Widget Settings ──────────────────────────────────────────
Route::get( '/booking-widget/settings',        [\App\Http\Controllers\Admin\BookingWidgetSettingsController::class, 'index']         )->name('admin.booking-widget.settings');
Route::put( '/booking-widget/settings',        [\App\Http\Controllers\Admin\BookingWidgetSettingsController::class, 'update']        )->name('admin.booking-widget.settings.update');
Route::post('/booking-widget/confirm/{id}',    [\App\Http\Controllers\Admin\BookingWidgetSettingsController::class, 'confirmBooking'])->name('admin.booking-widget.confirm');

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

    // Analytics & Campaigns
    Route::get('/analytics',           [\App\Http\Controllers\Platform\AnalyticsController::class, 'index']        )->name('platform.analytics.index');
    Route::get('/analytics/campaigns', [\App\Http\Controllers\Platform\AnalyticsController::class, 'campaigns']    )->name('platform.analytics.campaigns');
    Route::post('/analytics/campaigns',[\App\Http\Controllers\Platform\AnalyticsController::class, 'sendCampaign'] )->name('platform.analytics.campaigns.send');

    // WA Inbox (Task #54)
    Route::get('/wa-inbox', fn() => view('platform.wa-inbox.index'))->name('platform.wa-inbox');
    Route::post('/wa/upload-media', [\App\Http\Controllers\Platform\WhatsAppController::class, 'uploadMedia'])->name('platform.wa.upload-media');

    // Push Notifications (Platform Admin)
    Route::get('/notifications/settings',  [\App\Http\Controllers\Platform\PushNotificationsController::class, 'settings']    )->name('platform.notifications.settings');
    Route::post('/notifications/settings', [\App\Http\Controllers\Platform\PushNotificationsController::class, 'settingsSave'])->name('platform.notifications.settings.save');
    Route::get('/notifications/send',      [\App\Http\Controllers\Platform\PushNotificationsController::class, 'send']        )->name('platform.notifications.send');
    Route::post('/notifications/send',     [\App\Http\Controllers\Platform\PushNotificationsController::class, 'sendPost']    )->name('platform.notifications.send.post');
    Route::get('/notifications/history',   [\App\Http\Controllers\Platform\PushNotificationsController::class, 'history']     )->name('platform.notifications.history');
});
