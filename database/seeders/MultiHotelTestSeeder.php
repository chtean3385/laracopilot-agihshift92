<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\WhatsAppTemplate;
use App\Services\HotelContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * MultiHotelTestSeeder — Idempotent seeder for multi-hotel SaaS verification.
 *
 * Creates:
 *   Hotel 1 – Default Hotel  (id=1) — sample room 101 + sample guest Ravi Kumar
 *   Hotel 2 – Beach Resort   (id=2) — empty (no rooms/guests, proves isolation)
 *
 * Users:
 *   admin@resort.com  / admin123  → Hotel 1 (Admin) + Hotel 2 (Admin)  [shows picker]
 *   admin2@hotel.com  / admin123  → Hotel 2 only (Admin)               [auto-selects]
 *
 * Run:   php artisan db:seed --class=MultiHotelTestSeeder
 */
class MultiHotelTestSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------ //
        // 1. Ensure both hotels exist
        // ------------------------------------------------------------------ //

        $hotel1Id = DB::table('hotels')->where('id', 1)->value('id');
        if (!$hotel1Id) {
            $hotel1Id = DB::table('hotels')->insertGetId([
                'name'       => 'Default Hotel',
                'slug'       => 'default-hotel',
                'status'     => 'active',
                'plan'       => 'basic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hotel2Id = DB::table('hotels')->where('name', 'Beach Resort')->value('id');
        if (!$hotel2Id) {
            $hotel2Id = DB::table('hotels')->insertGetId([
                'name'       => 'Beach Resort',
                'slug'       => 'beach-resort',
                'status'     => 'active',
                'plan'       => 'basic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ------------------------------------------------------------------ //
        // 2. Seed Hotel 1 sub-data (roles, modules, settings, WA templates)
        // ------------------------------------------------------------------ //

        app(HotelContext::class)->setHotel($hotel1Id);
        $this->seedRoles($hotel1Id);
        $this->seedModules($hotel1Id);
        $this->seedSettings($hotel1Id, 'Default Hotel', 'admin@resort.com');
        $this->seedWhatsAppTemplates($hotel1Id);

        // Sample room for Hotel 1 (proves isolation — Beach Resort should never see it)
        $roomExists = DB::table('rooms')->where('hotel_id', $hotel1Id)->where('room_number', '101')->exists();
        if (!$roomExists) {
            DB::table('rooms')->insert([
                'hotel_id'       => $hotel1Id,
                'room_number'    => '101',
                'type'           => 'standard',
                'status'         => 'available',
                'price_per_night'=> 2500,
                'capacity'       => 2,
                'floor'          => 1,
                'amenities'      => 'AC, TV, Wi-Fi',
                'view'           => 'Garden',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Sample guest for Hotel 1
        $guestExists = DB::table('customers')->where('hotel_id', $hotel1Id)->where('email', 'ravi.kumar@example.com')->exists();
        if (!$guestExists) {
            DB::table('customers')->insert([
                'hotel_id'    => $hotel1Id,
                'name'        => 'Ravi Kumar',
                'email'       => 'ravi.kumar@example.com',
                'phone'       => '9876543210',
                'address'     => 'Mumbai',
                'city'        => 'Mumbai',
                'state'       => 'Maharashtra',
                'country'     => 'India',
                'id_type'     => 'aadhaar',
                'id_number'   => '0000-0000-0001',
                'nationality' => 'Indian',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ------------------------------------------------------------------ //
        // 3. Seed Hotel 2 sub-data (roles, modules, settings, WA templates)
        //    Beach Resort intentionally has NO rooms/guests — proves isolation.
        // ------------------------------------------------------------------ //

        app(HotelContext::class)->setHotel($hotel2Id);
        $this->seedRoles($hotel2Id);
        $this->seedModules($hotel2Id);
        $this->seedSettings($hotel2Id, 'Beach Resort', 'admin2@hotel.com');
        $this->seedWhatsAppTemplates($hotel2Id);

        // ------------------------------------------------------------------ //
        // 4. Users and hotel assignments
        // ------------------------------------------------------------------ //

        // admin@resort.com → Hotel 1 + Hotel 2 (triggers hotel picker on login)
        $adminId = DB::table('users')->where('email', 'admin@resort.com')->value('id');
        if (!$adminId) {
            $adminId = DB::table('users')->insertGetId([
                'name'       => 'Resort Admin',
                'email'      => 'admin@resort.com',
                'password'   => Hash::make('admin123'),
                'role'       => 'Admin',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assignUserToHotel($adminId, $hotel1Id, 'Admin');
        $this->assignUserToHotel($adminId, $hotel2Id, 'Admin');

        // admin2@hotel.com → Hotel 2 only (auto-selects Beach Resort on login)
        $admin2Id = DB::table('users')->where('email', 'admin2@hotel.com')->value('id');
        if (!$admin2Id) {
            $admin2Id = DB::table('users')->insertGetId([
                'name'       => 'Beach Admin',
                'email'      => 'admin2@hotel.com',
                'password'   => Hash::make('admin123'),
                'role'       => 'Admin',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assignUserToHotel($admin2Id, $hotel2Id, 'Admin');

        // ------------------------------------------------------------------ //
        // 5. Verification summary
        // ------------------------------------------------------------------ //

        app(HotelContext::class)->clear();

        $h1Rooms    = DB::table('rooms')->where('hotel_id', $hotel1Id)->count();
        $h1Guests   = DB::table('customers')->where('hotel_id', $hotel1Id)->count();
        $h1Roles    = DB::table('roles')->where('hotel_id', $hotel1Id)->count();
        $h1Modules  = DB::table('modules')->where('hotel_id', $hotel1Id)->count();

        $h2Rooms    = DB::table('rooms')->where('hotel_id', $hotel2Id)->count();
        $h2Guests   = DB::table('customers')->where('hotel_id', $hotel2Id)->count();
        $h2Roles    = DB::table('roles')->where('hotel_id', $hotel2Id)->count();
        $h2Modules  = DB::table('modules')->where('hotel_id', $hotel2Id)->count();

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║          Multi-Hotel Test Data — Verification            ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->info("║  Hotel 1 (Default Hotel, id={$hotel1Id})                         ║");
        $this->command->info("║    Rooms   : {$h1Rooms}  (room 101 seeded)                        ║");
        $this->command->info("║    Guests  : {$h1Guests}  (Ravi Kumar seeded)                     ║");
        $this->command->info("║    Roles   : {$h1Roles}  | Modules: {$h1Modules}                          ║");
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->info("║  Hotel 2 (Beach Resort, id={$hotel2Id})                          ║");
        $this->command->info("║    Rooms   : {$h2Rooms}  (isolated from Hotel 1)                 ║");
        $this->command->info("║    Guests  : {$h2Guests}  (isolated from Hotel 1)                 ║");
        $this->command->info("║    Roles   : {$h2Roles}  | Modules: {$h2Modules}                          ║");
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->info('║  Test Credentials                                        ║');
        $this->command->info('║    admin@resort.com  / admin123  → Hotel 1+2 (picker)   ║');
        $this->command->info('║    admin2@hotel.com  / admin123  → Hotel 2 only          ║');
        $this->command->info('║    superadmin@gmail.com / Super@#3385 → sees all         ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
        $this->command->info('');
    }

    // ---------------------------------------------------------------------- //
    // Helpers
    // ---------------------------------------------------------------------- //

    private function assignUserToHotel(int $userId, int $hotelId, string $role): void
    {
        $exists = DB::table('hotel_users')
            ->where('user_id', $userId)
            ->where('hotel_id', $hotelId)
            ->exists();

        if (!$exists) {
            DB::table('hotel_users')->insert([
                'hotel_id'      => $hotelId,
                'user_id'       => $userId,
                'role'          => $role,
                'is_hotel_admin'=> true,
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    private function seedRoles(int $hotelId): void
    {
        $permissions = Permission::all();

        $roles = [
            [
                'name'        => 'Admin',
                'description' => 'Full access to all features',
                'is_system'   => true,
                'permSlugs'   => $permissions->pluck('slug')->all(),
            ],
            [
                'name'        => 'Manager',
                'description' => 'Manage bookings, rooms, guests and reports',
                'is_system'   => true,
                'permSlugs'   => $permissions->filter(fn($p) => !in_array($p->slug, ['settings.view', 'roles.manage', 'modules.manage']))->pluck('slug')->all(),
            ],
            [
                'name'        => 'Receptionist',
                'description' => 'Day-to-day front-desk operations',
                'is_system'   => true,
                'permSlugs'   => ['guests.view', 'guests.create', 'guests.edit', 'rooms.view', 'bookings.view', 'bookings.create', 'bookings.edit', 'checkin.process', 'checkout.process', 'payments.view', 'payments.create', 'invoices.view'],
            ],
        ];

        foreach ($roles as $rd) {
            $perms = $rd['permSlugs'];
            unset($rd['permSlugs']);

            $role = Role::withoutGlobalScope(\App\Models\Scopes\HotelScope::class)
                ->firstOrCreate(
                    ['hotel_id' => $hotelId, 'name' => $rd['name']],
                    array_merge($rd, ['hotel_id' => $hotelId])
                );

            $permIds = Permission::whereIn('slug', $perms)->pluck('id')->all();
            $role->permissions()->syncWithoutDetaching($permIds);
        }
    }

    private function seedModules(int $hotelId): void
    {
        $modules = [
            ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation',  'description' => 'Automated WhatsApp messages for bookings and reminders.',         'is_enabled' => true],
            ['slug' => 'payment_links',   'name' => 'Payment Links',        'description' => 'Generate UPI QR codes and Razorpay payment links.',               'is_enabled' => true],
            ['slug' => 'pathik',          'name' => 'Pathik Autofill',      'description' => 'Auto-fill Gujarat Pathik portal via Chrome extension.',           'is_enabled' => true],
            ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager',  'description' => 'Sync availability and rates with OTA platforms.',                  'is_enabled' => true],
        ];

        foreach ($modules as $m) {
            Module::withoutGlobalScope(\App\Models\Scopes\HotelScope::class)
                ->firstOrCreate(
                    ['hotel_id' => $hotelId, 'slug' => $m['slug']],
                    array_merge($m, ['hotel_id' => $hotelId])
                );
        }
    }

    private function seedSettings(int $hotelId, string $hotelName, string $email): void
    {
        $exists = DB::table('settings')->where('hotel_id', $hotelId)->exists();
        if ($exists) return;

        DB::table('settings')->insert([
            'hotel_id'            => $hotelId,
            'resort_name'         => $hotelName,
            'address'             => '123 Main Street, Ahmedabad, Gujarat 380001 India',
            'phone'               => '+91 79 1234 5678',
            'email'               => $email,
            'website'             => '',
            'gst_number'          => '',
            'tax_rate'            => '12',
            'currency'            => 'INR',
            'currency_symbol'     => 'Rs',
            'check_in_time'       => '12:00',
            'check_out_time'      => '11:00',
            'invoice_prefix'      => 'INV',
            'booking_prefix'      => 'BK',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    private function seedWhatsAppTemplates(int $hotelId): void
    {
        $templates = [
            [
                'trigger_event' => 'booking.created',
                'template_name' => 'Booking Confirmation',
                'message_body'  => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe look forward to welcoming you!",
                'variables_hint'=> '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}, {{check_out_date}}, {{booking_number}}, {{total_amount}}',
                'is_active'     => true,
            ],
            [
                'trigger_event' => 'checkin.tomorrow',
                'template_name' => 'Check-In Reminder',
                'message_body'  => "Hello {{guest_name}}, your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you!",
                'variables_hint'=> '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}',
                'is_active'     => true,
            ],
            [
                'trigger_event' => 'checkout.done',
                'template_name' => 'Check-Out & Invoice',
                'message_body'  => "Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe hope to see you again!",
                'variables_hint'=> '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}',
                'is_active'     => true,
            ],
        ];

        foreach ($templates as $t) {
            WhatsAppTemplate::withoutGlobalScope(\App\Models\Scopes\HotelScope::class)
                ->firstOrCreate(
                    ['hotel_id' => $hotelId, 'trigger_event' => $t['trigger_event']],
                    array_merge($t, ['hotel_id' => $hotelId])
                );
        }
    }
}
