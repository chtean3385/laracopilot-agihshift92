<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SafeMigrate extends Command
{
    protected $signature = 'app:safe-migrate';
    protected $description = 'Run migrations safely, handling pre-existing tables from failed deployments';

    public function handle(): int
    {
        $this->call('view:clear');
        $this->info('Checking database state...');

        try {
            // Ensure the migrations tracking table exists
            if (!Schema::hasTable('migrations')) {
                DB::statement('CREATE TABLE IF NOT EXISTS migrations (id serial primary key, migration varchar(255) not null, batch int not null)');
                $this->info('Created migrations tracking table.');
            }

            $trackedCount = DB::table('migrations')->count();
            $tablesExist  = Schema::hasTable('users');
            $isProduction = app()->environment('production');

            if ($trackedCount === 0 && $tablesExist && !$isProduction) {
                $this->warn('Orphaned database state detected (tables exist but not tracked).');
                $this->warn('Dropping all tables and running fresh migrations...');
                $this->call('migrate:fresh', ['--force' => true]);
            } else {
                if ($trackedCount === 0 && $tablesExist && $isProduction) {
                    $this->warn('Orphaned state detected on PRODUCTION — skipping migrate:fresh to protect data.');
                    $this->warn('Running incremental migrate instead.');
                }
                $this->call('migrate', ['--force' => true]);
            }

            // ── 1. Superadmin ────────────────────────────────────────────────────────
            if (DB::table('users')->count() === 0) {
                $this->info('Seeding platform superadmin...');
                DB::table('users')->insert([
                    'name'           => 'Super Admin',
                    'email'          => 'superadmin@gmail.com',
                    'password'       => Hash::make('Super@#3385'),
                    'role'           => 'Super Admin',
                    'is_super_admin' => true,
                    'status'         => 'active',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $this->info('Superadmin created: superadmin@gmail.com');
            }

            // ── 2. Platform plans ────────────────────────────────────────────────────
            if (Schema::hasTable('platform_plans') && DB::table('platform_plans')->count() === 0) {
                $this->call('db:seed', ['--class' => 'PlatformPlanSeeder', '--force' => true]);
            }

            // ── 3. Global permissions catalog ────────────────────────────────────────
            if (Schema::hasTable('permissions') && DB::table('permissions')->count() === 0) {
                $this->info('Seeding global permissions catalog...');
                $this->seedPermissions();
                $this->info('Permissions seeded: ' . DB::table('permissions')->count() . ' rows.');
            }

            // ── 4. Per-hotel provisioning: roles, modules, WhatsApp templates ────────
            if (Schema::hasTable('hotels')) {
                $hotels = DB::table('hotels')->get();
                foreach ($hotels as $hotel) {
                    $this->provisionHotel($hotel->id, $hotel->name);
                }
            }

            // ── 5. Global platform WhatsApp templates (hotel_id = null) ──────────────
            if (Schema::hasTable('whatsapp_templates')) {
                $this->seedGlobalWhatsAppTemplates();
                $this->seedPdfCheckoutTemplate();
            }

            // ── 6. Platform settings from environment variables ────────────────────
            $this->seedPlatformSettings();

        } catch (\Exception $e) {
            $this->error('SafeMigrate failed: ' . $e->getMessage());
            return 1;
        }

        $this->info('Database ready.');
        return 0;
    }

    private function seedPermissions(): void
    {
        $permissions = [
            ['slug' => 'guests.view',       'label' => 'View Guests',              'module' => 'Guests',     'sort_order' => 1],
            ['slug' => 'guests.create',     'label' => 'Add Guests',               'module' => 'Guests',     'sort_order' => 2],
            ['slug' => 'guests.edit',       'label' => 'Edit Guests',              'module' => 'Guests',     'sort_order' => 3],
            ['slug' => 'guests.delete',     'label' => 'Delete Guests',            'module' => 'Guests',     'sort_order' => 4],

            ['slug' => 'rooms.view',        'label' => 'View Rooms',               'module' => 'Rooms',      'sort_order' => 5],
            ['slug' => 'rooms.create',      'label' => 'Add Rooms',                'module' => 'Rooms',      'sort_order' => 6],
            ['slug' => 'rooms.edit',        'label' => 'Edit Rooms',               'module' => 'Rooms',      'sort_order' => 7],
            ['slug' => 'rooms.delete',      'label' => 'Delete Rooms',             'module' => 'Rooms',      'sort_order' => 8],

            ['slug' => 'bookings.view',     'label' => 'View Bookings',            'module' => 'Bookings',   'sort_order' => 9],
            ['slug' => 'bookings.create',   'label' => 'Create Bookings',          'module' => 'Bookings',   'sort_order' => 10],
            ['slug' => 'bookings.edit',     'label' => 'Edit Bookings',            'module' => 'Bookings',   'sort_order' => 11],
            ['slug' => 'bookings.delete',   'label' => 'Delete Bookings',          'module' => 'Bookings',   'sort_order' => 12],

            ['slug' => 'checkin.process',   'label' => 'Process Check-In',         'module' => 'Operations', 'sort_order' => 13],
            ['slug' => 'checkout.process',  'label' => 'Process Check-Out',        'module' => 'Operations', 'sort_order' => 14],

            ['slug' => 'payments.view',     'label' => 'View Payments',            'module' => 'Payments',   'sort_order' => 15],
            ['slug' => 'payments.create',   'label' => 'Record Payments',          'module' => 'Payments',   'sort_order' => 16],
            ['slug' => 'payments.delete',   'label' => 'Delete Payments',          'module' => 'Payments',   'sort_order' => 17],

            ['slug' => 'invoices.view',     'label' => 'View Invoices',            'module' => 'Invoices',   'sort_order' => 18],
            ['slug' => 'invoices.delete',   'label' => 'Delete Invoices',          'module' => 'Invoices',   'sort_order' => 19],

            ['slug' => 'reports.view',      'label' => 'View Reports',             'module' => 'Reports',    'sort_order' => 20],

            ['slug' => 'settings.view',     'label' => 'View Settings',            'module' => 'Settings',   'sort_order' => 21],
            ['slug' => 'settings.edit',     'label' => 'Edit Settings',            'module' => 'Settings',   'sort_order' => 22],

            ['slug' => 'activity_log.view', 'label' => 'View Activity Log',        'module' => 'System',     'sort_order' => 23],

            ['slug' => 'roles.view',        'label' => 'View Roles & Permissions', 'module' => 'System',     'sort_order' => 24],
            ['slug' => 'roles.edit',        'label' => 'Edit Roles & Permissions', 'module' => 'System',     'sort_order' => 25],

            ['slug' => 'users.view',        'label' => 'View Users',               'module' => 'Users',      'sort_order' => 26],
            ['slug' => 'users.create',      'label' => 'Create Users',             'module' => 'Users',      'sort_order' => 27],
            ['slug' => 'users.edit',        'label' => 'Edit Users',               'module' => 'Users',      'sort_order' => 28],
            ['slug' => 'users.delete',      'label' => 'Delete Users',             'module' => 'Users',      'sort_order' => 29],

            ['slug' => 'whatsapp.send',     'label' => 'Send WhatsApp Messages',   'module' => 'WhatsApp',   'sort_order' => 30],
        ];

        $now = now();
        foreach ($permissions as $p) {
            DB::table('permissions')->insertOrIgnore([
                'slug'       => $p['slug'],
                'label'      => $p['label'],
                'module'     => $p['module'],
                'sort_order' => $p['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function provisionHotel(int $hotelId, string $hotelName): void
    {
        // Fast-path: skip fully-provisioned hotels with one cheap query.
        // A hotel is "fully provisioned" when it has all 3 system roles,
        // all 6 modules, and at least 7 per-hotel WhatsApp templates.
        $rolesCount   = DB::table('roles')->where('hotel_id', $hotelId)
            ->whereIn('name', ['Admin', 'Manager', 'Receptionist'])->count();
        $modulesCount = DB::table('modules')->where('hotel_id', $hotelId)
            ->whereIn('slug', ['whatsapp', 'payment_links', 'pathik', 'channel_manager', 'email-parser', 'restaurant'])->count();
        $tplCount     = DB::table('whatsapp_templates')->where('hotel_id', $hotelId)->count();
        // 7 = number of per-hotel templates defined in $templateDefs below.
        if ($rolesCount >= 3 && $modulesCount >= 6 && $tplCount >= 7) {
            return;
        }

        // ── Roles + permissions ──────────────────────────────────────────────────
        // Permissions that must NEVER be auto-granted to any role during provisioning.
        // SaaS admin must explicitly enable these per hotel via the role editor.
        $neverAutoGrant = [
            'guests.delete', 'rooms.delete', 'bookings.delete',
            'payments.delete', 'invoices.delete', 'users.delete',
            'data.truncate',  // Danger Zone — SaaS admin only
            'whatsapp.send',  // WhatsApp — opt-in only
        ];

        $allSlugs     = DB::table('permissions')
            ->whereNotIn('slug', $neverAutoGrant)
            ->pluck('slug')->all();
        $limitedSlugs = DB::table('permissions')
            ->whereNotIn('slug', array_merge($neverAutoGrant, [
                'settings.view', 'roles.view', 'roles.edit',
                'users.view', 'users.create', 'users.edit',
            ]))
            ->pluck('slug')->all();
        $frontdeskSlugs = [
            'guests.view', 'guests.create', 'guests.edit',
            'rooms.view',
            'bookings.view', 'bookings.create', 'bookings.edit',
            'checkin.process', 'checkout.process',
            'payments.view', 'payments.create',
            'invoices.view',
        ];

        $roleDefs = [
            ['name' => 'Admin',        'description' => 'Full access',                'is_system' => true, 'perms' => $allSlugs],
            ['name' => 'Manager',      'description' => 'Manage bookings and reports', 'is_system' => true, 'perms' => $limitedSlugs],
            ['name' => 'Receptionist', 'description' => 'Front-desk operations',       'is_system' => true, 'perms' => $frontdeskSlugs],
        ];

        foreach ($roleDefs as $def) {
            $existing = DB::table('roles')
                ->where('hotel_id', $hotelId)
                ->where('name', $def['name'])
                ->first();

            if (!$existing) {
                $roleId = DB::table('roles')->insertGetId([
                    'hotel_id'    => $hotelId,
                    'name'        => $def['name'],
                    'description' => $def['description'],
                    'is_system'   => $def['is_system'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $permIds = DB::table('permissions')->whereIn('slug', $def['perms'])->pluck('id')->all();
                foreach ($permIds as $permId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }

        // ── Modules ──────────────────────────────────────────────────────────────
        $moduleDefs = [
            ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation',      'description' => 'Send automated WhatsApp messages on booking, check-in reminders, and check-out.'],
            ['slug' => 'payment_links',   'name' => 'Payment Links',            'description' => 'Generate UPI QR codes and Razorpay payment links from invoices and bookings.'],
            ['slug' => 'pathik',          'name' => 'Pathik Autofill',          'description' => 'Auto-fill Gujarat Pathik portal with guest data from the CRM via Chrome extension.'],
            ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager',      'description' => 'Sync room availability and rates with OTA platforms like eZee, STAAH, SiteMinder.'],
            ['slug' => 'email-parser',    'name' => 'OTA Email Parser',         'description' => 'Auto-read OTA booking confirmation emails (Booking.com, Airbnb, MakeMyTrip, Goibibo, Agoda, Expedia) via IMAP every 5 minutes — auto-creates guests and bookings, detects conflicts.'],
            ['slug' => 'restaurant',      'name' => 'Restaurant Management',    'description' => 'Manage restaurant tables, menu, orders, KOT printing and billing. Charge directly or add to guest room bill.'],
        ];
        foreach ($moduleDefs as $m) {
            $exists = DB::table('modules')->where('hotel_id', $hotelId)->where('slug', $m['slug'])->exists();
            if (!$exists) {
                DB::table('modules')->insert(array_merge($m, [
                    'hotel_id'   => $hotelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // ── WhatsApp templates ───────────────────────────────────────────────────
        $templateDefs = [
            [
                'trigger_event'  => 'booking.created',
                'template_name'  => 'Booking Confirmation',
                'message_body'   => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe look forward to welcoming you! For any queries, please contact us.",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}, {{check_out_date}}, {{booking_number}}, {{total_amount}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'checkin.tomorrow',
                'template_name'  => 'Check-In Reminder',
                'message_body'   => "Hello {{guest_name}}, this is a friendly reminder that your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you. We look forward to welcoming you!",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'Check-Out Thank You + Bill',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again!",
                'variables_hint'          => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'               => true,
                'has_document_attachment' => false,
            ],
            [
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'Check-Out & Invoice (PDF)',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nPlease find your invoice attached.\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again!",
                'variables_hint'          => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'               => false,
                'has_document_attachment' => true,
            ],
            [
                'trigger_event'  => 'checkin.done',
                'template_name'  => 'Arrival Welcome',
                'message_body'   => "Welcome to {{hotel_name}}, {{guest_name}}! 🏨\n\nYou're all checked in!\n📍 Room: {{room_number}} ({{room_type}})\n📅 Check-out: {{check_out_date}}\n\nWe hope you have a wonderful stay.",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{room_type}}, {{check_out_date}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'payment.received',
                'template_name'  => 'Payment Receipt',
                'message_body'   => "Payment Received ✅\n\nDear {{guest_name}},\n\nWe've received your payment of {{amount_paid}} via {{payment_method}}.\nBooking Ref: {{booking_number}}, Balance Due: {{balance_due}}.\n\nWe look forward to welcoming you again at {{hotel_name}}!",
                'variables_hint' => '{{guest_name}}, {{amount_paid}}, {{payment_method}}, {{booking_number}}, {{balance_due}}, {{hotel_name}}',
                'is_active'      => true,
            ],
            [
                'trigger_event'  => 'feedback.request',
                'template_name'  => 'Feedback Request',
                'message_body'   => "Dear {{guest_name}},\n\nThank you for staying with us at {{hotel_name}}! 🙏\n\nWe hope you had a pleasant stay from {{check_in_date}} to {{check_out_date}}.\n\nWe'd love to hear your feedback to help us serve you better.\n\nWe look forward to welcoming you again! 🌟",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{check_in_date}}, {{check_out_date}}',
                'is_active'      => true,
            ],
        ];

        foreach ($templateDefs as $t) {
            $isPdf  = !empty($t['has_document_attachment']);
            $exists = DB::table('whatsapp_templates')
                ->where('hotel_id', $hotelId)
                ->where('trigger_event', $t['trigger_event'])
                ->where('has_document_attachment', $isPdf)
                ->exists();
            if (!$exists) {
                DB::table('whatsapp_templates')->insert(array_merge($t, [
                    'hotel_id'                => $hotelId,
                    'has_document_attachment' => $isPdf,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]));
            }
        }

        $this->info("Hotel '{$hotelName}' (#{$hotelId}): roles, modules, templates provisioned.");
    }

    private function seedGlobalWhatsAppTemplates(): void
    {
        // Global templates (hotel_id = null) for the shared platform Meta number.
        // Bodies must not start or end with a variable — Meta rejects those.
        $templates = [
            [
                'trigger_event'    => 'booking.created',
                'template_name'    => 'booking_confrim_crm',
                'message_body'     => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe look forward to welcoming you! For any queries, please contact us.",
                'approval_status'  => 'approved',
                'meta_template_id' => '946426251471676',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'checkin.tomorrow',
                'template_name'    => 'check_in_reminder_day_before',
                'message_body'     => "Hello {{guest_name}}, this is a friendly reminder that your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you. We look forward to welcoming you!",
                'approval_status'  => 'approved',
                'meta_template_id' => '941731028473106',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'checkin.done',
                'template_name'    => 'rrival_elcome',
                'message_body'     => "Welcome to {{hotel_name}}, {{guest_name}}! 🏨\n\nYou're all checked in!\n🚪 Room: {{room_number}} ({{room_type}})\n📅 Check-out: {{check_out_date}}\n\nWe hope you have a wonderful stay. Please don't hesitate to ask if you need anything.",
                'approval_status'  => 'approved',
                'meta_template_id' => '979908241129195',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'checkout.done',
                'template_name'    => 'check_out_and_bill',
                'message_body'     => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again!",
                'approval_status'  => 'approved',
                'meta_template_id' => '2851015818584226',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'feedback.request',
                'template_name'    => 'eedback_equest',
                'message_body'     => "Dear {{guest_name}},\n\nThank you for staying with us at {{hotel_name}}! 🙏\n\nWe hope you had a pleasant stay from {{check_in_date}} to {{check_out_date}}.\n\nWe'd love to hear your feedback to help us serve you better. Please share your experience whenever you get a moment.\n\nWe look forward to welcoming you again! 🌟",
                'approval_status'  => 'approved',
                'meta_template_id' => '1466324025233517',
                'meta_status'      => 'approved',
                'is_active'        => true,
            ],
            [
                'trigger_event'    => 'payment.received',
                'template_name'    => 'payment_receipt',
                'message_body'     => "Payment Received ✅\n\nDear {{guest_name}},\n\nWe've received your payment of {{amount_paid}} via {{payment_method}}.\nBooking Ref: {{booking_number}}, Balance Due: {{balance_due}}.\n\nWe look forward to welcoming you again at {{hotel_name}}!",
                'approval_status'  => 'pending',
                'meta_template_id' => null,
                'meta_status'      => 'not_submitted',
                'is_active'        => false,
            ],
        ];

        $count = 0;
        foreach ($templates as $tpl) {
            // Match by template_name (unique identifier) rather than trigger_event,
            // because checkout.done legitimately has two rows (text + PDF).
            $existing = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('template_name', $tpl['template_name'])
                ->first();

            if ($existing) {
                $update = [
                    'trigger_event'           => $tpl['trigger_event'],
                    'message_body'            => $tpl['message_body'],
                    'has_document_attachment' => false, // text templates are never PDF
                    'updated_at'              => now(),
                ];
                if (!empty($tpl['meta_template_id']) && !$existing->meta_template_id) {
                    $update['meta_template_id'] = $tpl['meta_template_id'];
                    $update['meta_status']       = $tpl['meta_status'];
                    $update['approval_status']   = $tpl['approval_status'];
                    $update['is_active']         = $tpl['is_active'];
                } elseif ($existing->approval_status !== 'approved' && $tpl['approval_status'] === 'approved') {
                    $update['approval_status']  = 'approved';
                    $update['meta_template_id'] = $tpl['meta_template_id'];
                    $update['meta_status']      = $tpl['meta_status'];
                    $update['is_active']        = $tpl['is_active'];
                }
                DB::table('whatsapp_templates')->where('id', $existing->id)->update($update);
                $count++;
            } else {
                DB::table('whatsapp_templates')->insert(array_merge($tpl, [
                    'hotel_id'                => null,
                    'has_document_attachment' => false,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]));
                $count++;
            }
        }

        // Clean up stale text-template duplicates per event.
        // Rows with has_document_attachment=true (PDF templates) are always preserved.
        $events = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->select('trigger_event')
            ->distinct()
            ->pluck('trigger_event');

        foreach ($events as $event) {
            $textRows = DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('trigger_event', $event)
                ->where('has_document_attachment', false)
                ->get();

            if ($textRows->count() <= 1) {
                continue;
            }

            // Sort explicitly: approved > pending > rejected, then lowest id
            $priority = ['approved' => 0, 'pending' => 1, 'rejected' => 2];
            $sorted   = $textRows->sortBy(fn ($r) => [($priority[$r->approval_status] ?? 9), $r->id]);
            $keepId   = $sorted->first()->id;
            DB::table('whatsapp_templates')
                ->whereNull('hotel_id')
                ->where('trigger_event', $event)
                ->where('has_document_attachment', false)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        $this->info("Global WhatsApp templates: {$count} upserted.");
    }

    private function seedPdfCheckoutTemplate(): void
    {
        // Ensure the "check_out_bill_with_pdf" global template exists.
        // This is a DOCUMENT-header Meta template (separate from the approved text-only checkout template).
        // It starts as pending — the Platform Admin submits it to Meta and activates it once approved.
        // The dedup loop preserves it because has_document_attachment = true.
        $exists = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where('template_name', 'check_out_bill_with_pdf')
            ->exists();

        if (!$exists) {
            DB::table('whatsapp_templates')->insert([
                'hotel_id'                => null,
                'trigger_event'           => 'checkout.done',
                'template_name'           => 'check_out_bill_with_pdf',
                'message_body'            => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nYour invoice {{invoice_number}} is attached for your records.\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again!",
                'variables_hint'          => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'               => false,
                'has_document_attachment' => true,
                'approval_status'         => 'pending',
                'meta_template_id'        => null,
                'meta_status'             => 'not_submitted',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
            $this->info('PDF checkout template (check_out_bill_with_pdf) created — submit to Meta to activate.');
        } else {
            $this->info('PDF checkout template: already exists, skipping.');
        }
    }

    private function seedPlatformSettings(): void
    {
        // ── WhatsApp platform settings ───────────────────────────────────────────
        // Only seed when the table is empty (first production boot).
        // Never overwrites data that the Platform Admin has manually configured.
        if (Schema::hasTable('platform_whatsapp_settings') &&
            DB::table('platform_whatsapp_settings')->count() === 0) {

            $token       = env('WA_SAAS_TOKEN');
            $phoneId     = env('WA_SAAS_PHONE_NUMBER_ID');
            $wabaId      = env('WA_SAAS_WABA_ID');
            $metaAppId   = env('WA_META_APP_ID');
            $metaSecret  = env('WA_META_APP_SECRET');
            $metaConfig  = env('WA_META_CONFIG_ID');
            $verifyToken = env('WA_WEBHOOK_VERIFY_TOKEN', 'resort-crm-whatsapp-2026');

            if ($token || $phoneId || $wabaId) {
                $isActive = (bool) ($token && $phoneId && $wabaId);
                DB::table('platform_whatsapp_settings')->insert([
                    'meta_app_id'          => $metaAppId,
                    'meta_app_secret'      => $metaSecret,
                    'meta_config_id'       => $metaConfig,
                    'saas_token'           => $token,
                    'saas_phone_number_id' => $phoneId,
                    'saas_waba_id'         => $wabaId,
                    'webhook_verify_token' => $verifyToken,
                    'is_saas_active'       => $isActive,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
                $this->info('Platform WhatsApp settings seeded from environment variables.');
                if (!$isActive) {
                    $this->warn('WhatsApp: is_saas_active=false — one or more of WA_SAAS_TOKEN, WA_SAAS_PHONE_NUMBER_ID, WA_SAAS_WABA_ID is missing.');
                }
            } else {
                $this->warn('Platform WhatsApp settings: table is empty and no WA_* env vars set — skipping.');
            }
        } else {
            $this->info('Platform WhatsApp settings: already configured, skipping.');
        }

        // ── Firebase platform settings ───────────────────────────────────────────
        if (Schema::hasTable('platform_firebase_settings') &&
            DB::table('platform_firebase_settings')->count() === 0) {

            $projectId  = env('FIREBASE_PROJECT_ID');
            $apiKey     = env('FIREBASE_API_KEY');
            $senderId   = env('FIREBASE_MESSAGING_SENDER_ID');
            $appId      = env('FIREBASE_APP_ID');
            $vapidKey   = env('FIREBASE_VAPID_KEY');
            $fcmKey     = env('FCM_SERVER_KEY');
            $serviceJson = env('FIREBASE_SERVICE_ACCOUNT_JSON');

            if ($projectId || $apiKey || $fcmKey || $serviceJson) {
                DB::table('platform_firebase_settings')->insert([
                    'firebase_project_id'          => $projectId,
                    'firebase_api_key'              => $apiKey,
                    'firebase_messaging_sender_id'  => $senderId,
                    'firebase_app_id'               => $appId,
                    'firebase_vapid_key'            => $vapidKey,
                    'fcm_server_key'                => $fcmKey,
                    'service_account_json'          => $serviceJson,
                    'push_enabled'                  => (bool) $projectId,
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                ]);
                $this->info('Platform Firebase settings seeded from environment variables.');
                if (!$projectId) {
                    $this->warn('Firebase: FIREBASE_PROJECT_ID is not set — push_enabled will be false.');
                }
            } else {
                $this->warn('Platform Firebase settings: table is empty and no FIREBASE_*/FCM_* env vars set — skipping.');
            }
        } else {
            $this->info('Platform Firebase settings: already configured, skipping.');
        }
    }
}
