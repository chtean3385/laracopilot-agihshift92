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
        // ── Roles + permissions ──────────────────────────────────────────────────
        $allSlugs      = DB::table('permissions')->pluck('slug')->all();
        $limitedSlugs  = DB::table('permissions')
            ->whereNotIn('slug', ['settings.view', 'roles.view', 'roles.edit',
                                  'users.view', 'users.create', 'users.edit', 'users.delete'])
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
            } else {
                $roleId = $existing->id;
            }

            $permIds        = DB::table('permissions')->whereIn('slug', $def['perms'])->pluck('id')->all();
            $existing_pivot = DB::table('role_permissions')->where('role_id', $roleId)->pluck('permission_id')->all();
            $toInsert       = array_diff($permIds, $existing_pivot);
            foreach ($toInsert as $permId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ]);
            }
        }

        // ── Modules ──────────────────────────────────────────────────────────────
        $moduleDefs = [
            ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation',  'description' => 'Send automated WhatsApp messages on booking, check-in reminders, and check-out.'],
            ['slug' => 'payment_links',   'name' => 'Payment Links',        'description' => 'Generate UPI QR codes and Razorpay payment links from invoices and bookings.'],
            ['slug' => 'pathik',          'name' => 'Pathik Autofill',      'description' => 'Auto-fill Gujarat Pathik portal with guest data from the CRM via Chrome extension.'],
            ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager',  'description' => 'Sync room availability and rates with OTA platforms like eZee, STAAH, SiteMinder.'],
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
                'trigger_event'  => 'checkout.done',
                'template_name'  => 'Check-Out Thank You + Bill',
                'message_body'   => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again!",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'      => true,
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
                'message_body'   => "Payment Received ✅\n\nDear {{guest_name}},\n\nWe've received your payment of {{amount_paid}} via {{payment_method}}.\n\n📋 Booking: {{booking_number}}\n💰 Balance Due: {{balance_due}}\n\nThank you! — {{hotel_name}}",
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
            $exists = DB::table('whatsapp_templates')
                ->where('hotel_id', $hotelId)
                ->where('trigger_event', $t['trigger_event'])
                ->exists();
            if (!$exists) {
                DB::table('whatsapp_templates')->insert(array_merge($t, [
                    'hotel_id'   => $hotelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $this->info("Hotel '{$hotelName}' (#{$hotelId}): roles, modules, templates provisioned.");
    }
}
