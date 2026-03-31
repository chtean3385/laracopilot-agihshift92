<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HotelController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index()
    {
        $hotels = DB::table('hotels')
            ->select(
                'hotels.id', 'hotels.name', 'hotels.slug', 'hotels.email',
                'hotels.phone', 'hotels.plan', 'hotels.status',
                'hotels.max_rooms', 'hotels.max_users',
                'hotels.created_at', 'hotels.trial_ends_at', 'hotels.plan_expires_at',
            )
            ->selectRaw('(SELECT COUNT(*) FROM rooms WHERE rooms.hotel_id = hotels.id) as room_count')
            ->selectRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.hotel_id = hotels.id) as booking_count')
            ->selectRaw('(SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.hotel_id = hotels.id AND payments.status = "completed") as revenue')
            ->selectRaw('(SELECT COUNT(*) FROM hotel_users WHERE hotel_users.hotel_id = hotels.id AND hotel_users.status = "active") as user_count')
            ->orderByDesc('hotels.created_at')
            ->get();

        $currencySymbol = DB::table('settings')->value('currency_symbol') ?? 'Rs';
        $plans          = config('plans', []);

        return view('platform.hotels.index', compact('hotels', 'currencySymbol', 'plans'));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        $plans = config('plans', []);
        return view('platform.hotels.create', compact('plans'));
    }

    // ── Store (with full atomic provisioning) ────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'plan'        => 'required|in:basic,pro,enterprise',
            'max_rooms'   => 'required|integer|min:1',
            'max_users'   => 'required|integer|min:1',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $slug = $this->generateUniqueSlug($data['name']);

        DB::transaction(function () use ($data, $slug) {
            // (a) Hotel row
            $hotelId = DB::table('hotels')->insertGetId([
                'name'        => $data['name'],
                'slug'        => $slug,
                'email'       => $data['email'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'address'     => $data['address'] ?? null,
                'plan'        => $data['plan'],
                'status'      => 'active',
                'max_rooms'   => $data['max_rooms'],
                'max_users'   => $data['max_users'],
                'admin_notes' => $data['admin_notes'] ?? null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // (b) Settings row
            DB::table('settings')->insert([
                'hotel_id'        => $hotelId,
                'resort_name'     => $data['name'],
                'address'         => $data['address'] ?? '',
                'phone'           => $data['phone'] ?? '',
                'email'           => $data['email'] ?? '',
                'tax_rate'        => '12',
                'currency'        => 'INR',
                'currency_symbol' => 'Rs',
                'check_in_time'   => '12:00',
                'check_out_time'  => '11:00',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // (c) 4 default modules (all disabled by default — SA enables per-plan)
            $modules = [
                ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation', 'description' => 'Automated WhatsApp messages.',            'is_enabled' => false],
                ['slug' => 'payment_links',   'name' => 'Payment Links',       'description' => 'Generate UPI QR codes and payment links.', 'is_enabled' => false],
                ['slug' => 'pathik',          'name' => 'Pathik Autofill',     'description' => 'Gujarat Pathik portal autofill.',          'is_enabled' => false],
                ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager', 'description' => 'Sync with OTA platforms.',                 'is_enabled' => false],
            ];
            foreach ($modules as $m) {
                DB::table('modules')->insert(array_merge($m, [
                    'hotel_id'   => $hotelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // (d) 3 system roles with permissions
            $this->provisionRoles($hotelId);
        });

        return redirect()->route('platform.hotels.index')
            ->with('success', "Hotel \"{$data['name']}\" created and fully provisioned.");
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        $plans = config('plans', []);
        return view('platform.hotels.edit', compact('hotel', 'plans'));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'plan'        => 'required|in:basic,pro,enterprise',
            'max_rooms'   => 'required|integer|min:1',
            'max_users'   => 'required|integer|min:1',
            'status'      => 'required|in:active,suspended',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        DB::table('hotels')->where('id', $id)->update(array_merge($data, [
            'updated_at' => now(),
        ]));

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "Hotel \"{$hotel->name}\" updated successfully.");
    }

    // ── Suspend ───────────────────────────────────────────────────────────────

    public function suspend(int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        DB::table('hotels')->where('id', $id)->update([
            'status'     => 'suspended',
            'updated_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', "Hotel \"{$hotel->name}\" has been suspended. All staff logins will be blocked.");
    }

    // ── Activate ──────────────────────────────────────────────────────────────

    public function activate(int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        DB::table('hotels')->where('id', $id)->update([
            'status'     => 'active',
            'updated_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', "Hotel \"{$hotel->name}\" has been reactivated.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n    = 1;

        while (DB::table('hotels')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    private function provisionRoles(int $hotelId): void
    {
        $all        = Permission::pluck('slug')->all();
        $limited    = Permission::whereNotIn('slug', ['settings.view', 'roles.view', 'roles.edit', 'users.view', 'users.create', 'users.edit', 'users.delete'])->pluck('slug')->all();
        $frontdesk  = ['guests.view', 'guests.create', 'guests.edit', 'rooms.view',
                        'bookings.view', 'bookings.create', 'bookings.edit',
                        'checkin.process', 'checkout.process', 'payments.view',
                        'payments.create', 'invoices.view'];

        $defs = [
            ['name' => 'Admin',        'description' => 'Full access',                 'is_system' => true,  'perms' => $all],
            ['name' => 'Manager',      'description' => 'Manage bookings and reports',  'is_system' => true,  'perms' => $limited],
            ['name' => 'Receptionist', 'description' => 'Front-desk operations',        'is_system' => true,  'perms' => $frontdesk],
        ];

        foreach ($defs as $d) {
            $role = Role::firstOrCreate(
                ['hotel_id' => $hotelId, 'name' => $d['name']],
                ['hotel_id' => $hotelId, 'name' => $d['name'], 'description' => $d['description'], 'is_system' => $d['is_system']]
            );
            $permIds = Permission::whereIn('slug', $d['perms'])->pluck('id')->all();
            $role->permissions()->syncWithoutDetaching($permIds);
        }
    }
}
