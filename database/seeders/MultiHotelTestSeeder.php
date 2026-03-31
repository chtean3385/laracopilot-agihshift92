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
 * Idempotent seeder for multi-hotel SaaS isolation verification.
 *
 * Hotel 1 (Default Hotel) — room 101 (Rs 2000/night) + guest "Test Guest"
 * Hotel 2 (Beach Resort)  — no rooms/guests, proves per-hotel data isolation
 *
 * Credentials:
 *   admin@resort.com  / admin123  → Hotel 1 + 2 (shows hotel picker)
 *   admin2@hotel.com  / admin123  → Hotel 2 only (auto-selects)
 *
 * Run: php artisan db:seed --class=MultiHotelTestSeeder
 */
class MultiHotelTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Hotels
        $hotel1Id = DB::table('hotels')->where('slug', 'default-hotel')->value('id')
            ?? DB::table('hotels')->where('id', 1)->value('id')
            ?? DB::table('hotels')->insertGetId([
                'name' => 'Default Hotel', 'slug' => 'default-hotel',
                'status' => 'active', 'plan' => 'basic',
                'created_at' => now(), 'updated_at' => now(),
            ]);

        $hotel2Id = DB::table('hotels')->where('name', 'Beach Resort')->value('id') ?? DB::table('hotels')->insertGetId([
            'name' => 'Beach Resort', 'slug' => 'beach-resort',
            'status' => 'active', 'plan' => 'basic',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 2. Hotel 1 — sub-data + sample room + sample guest
        app(HotelContext::class)->setHotel($hotel1Id);
        $this->seedRoles($hotel1Id);
        $this->seedModules($hotel1Id);
        $this->seedSettings($hotel1Id, 'Default Hotel', 'admin@resort.com');
        $this->seedWhatsAppTemplates($hotel1Id);

        if (!DB::table('rooms')->where('hotel_id', $hotel1Id)->where('room_number', '101')->exists()) {
            DB::table('rooms')->insert([
                'hotel_id' => $hotel1Id, 'room_number' => '101', 'type' => 'standard',
                'status' => 'available', 'price_per_night' => 2000, 'capacity' => 2,
                'floor' => 1, 'amenities' => 'AC, TV, Wi-Fi', 'view' => 'Garden',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (!DB::table('customers')->where('hotel_id', $hotel1Id)->where('email', 'testguest@example.com')->exists()) {
            DB::table('customers')->insert([
                'hotel_id' => $hotel1Id, 'name' => 'Test Guest',
                'email' => 'testguest@example.com', 'phone' => '9876543210',
                'address' => 'Mumbai', 'city' => 'Mumbai', 'state' => 'Maharashtra',
                'country' => 'India', 'id_type' => 'aadhaar', 'id_number' => '0000-0000-0001',
                'nationality' => 'Indian', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 3. Hotel 2 — sub-data only; intentionally no rooms/guests (proves isolation)
        app(HotelContext::class)->setHotel($hotel2Id);
        $this->seedRoles($hotel2Id);
        $this->seedModules($hotel2Id);
        $this->seedSettings($hotel2Id, 'Beach Resort', 'admin2@hotel.com');
        $this->seedWhatsAppTemplates($hotel2Id);

        // 4. Users + hotel assignments
        $adminId = DB::table('users')->where('email', 'admin@resort.com')->value('id')
            ?? DB::table('users')->insertGetId([
                'name' => 'Resort Admin', 'email' => 'admin@resort.com',
                'password' => Hash::make('admin123'), 'role' => 'Admin',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        $this->assignUserToHotel($adminId, $hotel1Id, 'Admin');
        $this->assignUserToHotel($adminId, $hotel2Id, 'Admin');

        $admin2Id = DB::table('users')->where('email', 'admin2@hotel.com')->value('id')
            ?? DB::table('users')->insertGetId([
                'name' => 'Beach Admin', 'email' => 'admin2@hotel.com',
                'password' => Hash::make('admin123'), 'role' => 'Admin',
                'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        $this->assignUserToHotel($admin2Id, $hotel2Id, 'Admin');

        // 5. Verification summary
        app(HotelContext::class)->clear();

        $this->command->info('Multi-Hotel Verification:');
        $this->command->info(sprintf(
            '  Hotel 1 (Default Hotel, id=%d): rooms=%d guests=%d roles=%d modules=%d settings=%d',
            $hotel1Id,
            DB::table('rooms')->where('hotel_id', $hotel1Id)->count(),
            DB::table('customers')->where('hotel_id', $hotel1Id)->count(),
            DB::table('roles')->where('hotel_id', $hotel1Id)->count(),
            DB::table('modules')->where('hotel_id', $hotel1Id)->count(),
            DB::table('settings')->where('hotel_id', $hotel1Id)->count()
        ));
        $this->command->info(sprintf(
            '  Hotel 2 (Beach Resort,  id=%d): rooms=%d guests=%d roles=%d modules=%d settings=%d',
            $hotel2Id,
            DB::table('rooms')->where('hotel_id', $hotel2Id)->count(),
            DB::table('customers')->where('hotel_id', $hotel2Id)->count(),
            DB::table('roles')->where('hotel_id', $hotel2Id)->count(),
            DB::table('modules')->where('hotel_id', $hotel2Id)->count(),
            DB::table('settings')->where('hotel_id', $hotel2Id)->count()
        ));
        $this->command->info('  admin@resort.com/admin123 (H1+H2 picker) | admin2@hotel.com/admin123 (H2 only)');
    }

    private function assignUserToHotel(int $userId, int $hotelId, string $role): void
    {
        if (!DB::table('hotel_users')->where('user_id', $userId)->where('hotel_id', $hotelId)->exists()) {
            DB::table('hotel_users')->insert([
                'hotel_id' => $hotelId, 'user_id' => $userId, 'role' => $role,
                'is_hotel_admin' => true, 'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedRoles(int $hotelId): void
    {
        $all = Permission::all()->pluck('slug')->all();
        $limited = Permission::whereNotIn('slug', ['settings.view', 'roles.manage', 'modules.manage'])->pluck('slug')->all();
        $frontdesk = ['guests.view', 'guests.create', 'guests.edit', 'rooms.view', 'bookings.view',
            'bookings.create', 'bookings.edit', 'checkin.process', 'checkout.process',
            'payments.view', 'payments.create', 'invoices.view'];

        $defs = [
            ['name' => 'Admin',        'description' => 'Full access',                'is_system' => true, 'perms' => $all],
            ['name' => 'Manager',      'description' => 'Manage bookings and reports', 'is_system' => true, 'perms' => $limited],
            ['name' => 'Receptionist', 'description' => 'Front-desk operations',       'is_system' => true, 'perms' => $frontdesk],
        ];

        foreach ($defs as $d) {
            $role = Role::withoutGlobalScope(\App\Models\Scopes\HotelScope::class)
                ->firstOrCreate(
                    ['hotel_id' => $hotelId, 'name' => $d['name']],
                    ['hotel_id' => $hotelId, 'name' => $d['name'], 'description' => $d['description'], 'is_system' => $d['is_system']]
                );
            $permIds = Permission::whereIn('slug', $d['perms'])->pluck('id')->all();
            $role->permissions()->syncWithoutDetaching($permIds);
        }
    }

    private function seedModules(int $hotelId): void
    {
        $modules = [
            ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation',  'description' => 'Automated WhatsApp messages.',            'is_enabled' => true],
            ['slug' => 'payment_links',   'name' => 'Payment Links',        'description' => 'Generate UPI QR codes and payment links.', 'is_enabled' => true],
            ['slug' => 'pathik',          'name' => 'Pathik Autofill',      'description' => 'Gujarat Pathik portal autofill.',          'is_enabled' => true],
            ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager',  'description' => 'Sync with OTA platforms.',                 'is_enabled' => true],
        ];
        foreach ($modules as $m) {
            Module::withoutGlobalScope(\App\Models\Scopes\HotelScope::class)
                ->firstOrCreate(['hotel_id' => $hotelId, 'slug' => $m['slug']], array_merge($m, ['hotel_id' => $hotelId]));
        }
    }

    private function seedSettings(int $hotelId, string $hotelName, string $email): void
    {
        if (DB::table('settings')->where('hotel_id', $hotelId)->exists()) return;

        DB::table('settings')->insert([
            'hotel_id' => $hotelId, 'resort_name' => $hotelName,
            'address' => '123 Main Street, Ahmedabad, Gujarat 380001',
            'phone' => '+91 79 1234 5678', 'email' => $email,
            'tax_rate' => '12', 'currency' => 'INR', 'currency_symbol' => 'Rs',
            'check_in_time' => '12:00', 'check_out_time' => '11:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function seedWhatsAppTemplates(int $hotelId): void
    {
        $templates = [
            ['trigger_event' => 'booking.created', 'template_name' => 'Booking Confirmation',
                'message_body' => "Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed!\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nRef: {{booking_number}}\nTotal: ₹{{total_amount}}",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}, {{check_out_date}}, {{booking_number}}, {{total_amount}}', 'is_active' => true],
            ['trigger_event' => 'checkin.tomorrow', 'template_name' => 'Check-In Reminder',
                'message_body' => "Hello {{guest_name}}, your check-in at {{hotel_name}} is tomorrow!\n\nRoom: {{room_number}}\nDate: {{check_in_date}}",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}', 'is_active' => true],
            ['trigger_event' => 'checkout.done', 'template_name' => 'Check-Out & Invoice',
                'message_body' => "Thank you, {{guest_name}}, for staying at {{hotel_name}}!\n\nInvoice: {{invoice_number}}\nTotal: ₹{{total_amount}}",
                'variables_hint' => '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}', 'is_active' => true],
        ];
        foreach ($templates as $t) {
            WhatsAppTemplate::withoutGlobalScope(\App\Models\Scopes\HotelScope::class)
                ->firstOrCreate(['hotel_id' => $hotelId, 'trigger_event' => $t['trigger_event']], array_merge($t, ['hotel_id' => $hotelId]));
        }
    }
}
