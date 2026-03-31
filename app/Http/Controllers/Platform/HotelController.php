<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        // Use ALL plans for display/badge rendering (including inactive)
        $plans = $this->getAllPlansForDisplay();

        return view('platform.hotels.index', compact('hotels', 'currencySymbol', 'plans'));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        // Only active plans for new hotel creation
        $plans = $this->getActivePlansForSelection();
        return view('platform.hotels.create', compact('plans'));
    }

    // ── Store (with full atomic provisioning) ────────────────────────────────

    public function store(Request $request)
    {
        $validSlugs = DB::table('platform_plans')->where('is_active', true)->pluck('slug')->toArray();
        if (empty($validSlugs)) {
            $validSlugs = array_keys(config('plans', []));
        }

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string|max:500',
            'plan'           => 'required|in:' . implode(',', $validSlugs),
            'max_rooms'      => 'required|integer|min:1',
            'max_users'      => 'required|integer|min:1',
            'admin_notes'    => 'nullable|string|max:1000',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $slug = $this->generateUniqueSlug($data['name']);

        DB::transaction(function () use ($data, $slug) {
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

            $this->provisionRoles($hotelId);

            $adminId = DB::table('users')->insertGetId([
                'name'           => $data['admin_name'],
                'email'          => $data['admin_email'],
                'password'       => Hash::make($data['admin_password']),
                'role'           => 'Admin',
                'status'         => 'active',
                'is_super_admin' => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('hotel_users')->insert([
                'hotel_id'       => $hotelId,
                'user_id'        => $adminId,
                'role'           => 'Admin',
                'is_hotel_admin' => 1,
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        return redirect()->route('platform.hotels.index')
            ->with('success', "Hotel \"{$data['name']}\" created and fully provisioned with admin user {$data['admin_email']}.");
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        // Fetch the current hotel admin (is_hotel_admin=1)
        $hotelAdmin = DB::table('hotel_users')
            ->join('users', 'users.id', '=', 'hotel_users.user_id')
            ->where('hotel_users.hotel_id', $id)
            ->where('hotel_users.is_hotel_admin', 1)
            ->select('users.id', 'users.name', 'users.email', 'users.status', 'hotel_users.status as hu_status')
            ->first();

        // All active hotel users eligible to become admin (for reassignment dropdown)
        $hotelUsers = DB::table('hotel_users')
            ->join('users', 'users.id', '=', 'hotel_users.user_id')
            ->where('hotel_users.hotel_id', $id)
            ->where('hotel_users.status', 'active')
            ->where('users.status', 'active')
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get();

        // Active plans for new selection + always include hotel's current plan (even if inactive)
        $plans = $this->getActivePlansForSelection($hotel->plan ?? null);
        return view('platform.hotels.edit', compact('hotel', 'plans', 'hotelAdmin', 'hotelUsers'));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        $validSlugs = DB::table('platform_plans')->where('is_active', true)->pluck('slug')->toArray();
        if (empty($validSlugs)) {
            $validSlugs = array_keys(config('plans', []));
        }
        // Always allow hotel's existing plan slug even if now inactive
        if ($hotel->plan && !in_array($hotel->plan, $validSlugs)) {
            $validSlugs[] = $hotel->plan;
        }

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'address'           => 'nullable|string|max:500',
            'plan'              => 'required|in:' . implode(',', $validSlugs),
            'max_rooms'         => 'required|integer|min:1',
            'max_users'         => 'required|integer|min:1',
            'status'            => 'required|in:active,suspended',
            'admin_notes'       => 'nullable|string|max:1000',
            'new_admin_user_id' => 'nullable|integer',
        ]);

        DB::table('hotels')->where('id', $id)->update([
            'name'        => $data['name'],
            'email'       => $data['email'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'plan'        => $data['plan'],
            'max_rooms'   => $data['max_rooms'],
            'max_users'   => $data['max_users'],
            'status'      => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
            'updated_at'  => now(),
        ]);

        // Handle hotel admin reassignment
        if (!empty($data['new_admin_user_id'])) {
            $newAdminUserId = (int) $data['new_admin_user_id'];

            // Confirm the selected user actually belongs to this hotel and is active
            $eligible = DB::table('hotel_users')
                ->join('users', 'users.id', '=', 'hotel_users.user_id')
                ->where('hotel_users.hotel_id', $id)
                ->where('hotel_users.user_id', $newAdminUserId)
                ->where('hotel_users.status', 'active')
                ->where('users.status', 'active')
                ->exists();

            if ($eligible) {
                // Strip is_hotel_admin from all hotel users for this hotel
                DB::table('hotel_users')
                    ->where('hotel_id', $id)
                    ->update(['is_hotel_admin' => 0]);

                // Set the selected user as hotel admin
                DB::table('hotel_users')
                    ->where('hotel_id', $id)
                    ->where('user_id', $newAdminUserId)
                    ->update(['is_hotel_admin' => 1]);
            }
        }

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

    // ── Plan Helpers ──────────────────────────────────────────────────────────

    /**
     * Active plans for new assignment (hotel create form, new selection cards).
     * Optionally includes an extra slug (current hotel plan) even if inactive.
     */
    private function getActivePlansForSelection(?string $includeSlug = null): array
    {
        $query = DB::table('platform_plans')->where('is_active', true);

        if ($includeSlug) {
            $query = DB::table('platform_plans')
                ->where('is_active', true)
                ->orWhere('slug', $includeSlug);
        }

        $dbRows = $query->orderBy('sort_order')->orderBy('id')->get();

        $plans = $this->rowsToPlanArray($dbRows);

        if (empty($plans)) {
            $plans = config('plans', []);
        }

        return $plans;
    }

    /**
     * ALL plans (active + inactive) for display/badge rendering in hotel directory
     * and dashboard — preserves historical assignment labels and prices.
     */
    private function getAllPlansForDisplay(): array
    {
        $dbRows = DB::table('platform_plans')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $plans = $this->rowsToPlanArray($dbRows);

        // Merge config-only plans (slugs not present in DB) as fallback
        foreach (config('plans', []) as $slug => $cfg) {
            if (!isset($plans[$slug])) {
                $plans[$slug] = $cfg;
            }
        }

        return $plans;
    }

    /**
     * Convert DB result rows to the canonical plan array format used by views.
     */
    private function rowsToPlanArray($rows): array
    {
        $plans = [];
        foreach ($rows as $row) {
            $features    = is_string($row->features) ? json_decode($row->features, true) : ($row->features ?? []);
            $isUnlimited = $row->max_rooms >= 9999;
            $plans[$row->slug] = [
                'label'         => $row->label,
                'color'         => $row->color,
                'badge_bg'      => '#f1f5f9',
                'badge_text'    => '#475569',
                'monthly_price' => (int) $row->monthly_price,
                'yearly_price'  => (int) $row->yearly_price,
                'max_rooms'     => $isUnlimited ? PHP_INT_MAX : (int) $row->max_rooms,
                'max_users'     => ($row->max_users >= 9999) ? PHP_INT_MAX : (int) $row->max_users,
                'features'      => $features,
                'limits_note'   => ($isUnlimited ? 'Unlimited' : 'Up to ' . number_format($row->max_rooms)) . ' rooms, '
                                 . (($row->max_users >= 9999) ? 'Unlimited' : number_format($row->max_users)) . ' users',
                'is_active'     => (bool) $row->is_active,
            ];
        }
        return $plans;
    }

    // ── Other Helpers ─────────────────────────────────────────────────────────

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
