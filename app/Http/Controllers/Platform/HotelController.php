<?php

namespace App\Http\Controllers\Platform;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Mail\HotelWelcomeMail;
use App\Models\HotelBackupSetting;
use App\Models\Permission;
use App\Models\PlatformWhatsAppSetting;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HotelController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search    = trim($request->input('search', ''));
        $status    = $request->input('status', '');
        $planFilter = $request->input('plan', '');

        $query = DB::table('hotels')
            ->select(
                'hotels.id', 'hotels.name', 'hotels.slug', 'hotels.email',
                'hotels.phone', 'hotels.plan', 'hotels.status',
                'hotels.billing_cycle', 'hotels.custom_monthly_price', 'hotels.custom_yearly_price',
                'hotels.max_rooms', 'hotels.max_users',
                'hotels.created_at', 'hotels.trial_ends_at', 'hotels.plan_expires_at',
                'hotels.owner_wa_consent',
            )
            ->selectRaw('COALESCE(hotels.billing_included_in_parent, false) as billing_included_in_parent')
            ->selectRaw('(SELECT COUNT(*) FROM rooms WHERE rooms.hotel_id = hotels.id) as room_count')
            ->selectRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.hotel_id = hotels.id) as booking_count')
            ->selectRaw("(SELECT COUNT(*) FROM hotel_users WHERE hotel_users.hotel_id = hotels.id AND hotel_users.status = 'active') as user_count")
            ->orderByDesc('hotels.created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('hotels.name',  'ilike', "%{$search}%")
                  ->orWhere('hotels.slug',  'ilike', "%{$search}%")
                  ->orWhere('hotels.email', 'ilike', "%{$search}%")
                  ->orWhere('hotels.phone', 'ilike', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('hotels.status', $status);
        }

        if ($planFilter === 'custom') {
            $query->where(function ($q) {
                $q->where('hotels.custom_monthly_price', '>', 0)
                  ->orWhere('hotels.custom_yearly_price', '>', 0);
            });
        } elseif ($planFilter !== '') {
            $query->where('hotels.plan', $planFilter);
        }

        $hotels = $query->paginate(15)->withQueryString();

        $totalCount     = DB::table('hotels')->count();
        $currencySymbol = DB::table('settings')->value('currency_symbol') ?? 'Rs';
        $plans          = $this->getAllPlansForDisplay();

        return view('platform.hotels.index', compact(
            'hotels', 'currencySymbol', 'plans',
            'search', 'status', 'planFilter', 'totalCount'
        ));
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
            'name'                 => 'required|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:500',
            'plan'                 => 'required|in:' . implode(',', $validSlugs),
            'billing_cycle'        => 'required|in:monthly,yearly',
            'custom_monthly_price' => 'nullable|integer|min:0',
            'custom_yearly_price'  => 'nullable|integer|min:0',
            'max_rooms'            => 'required|integer|min:1',
            'max_users'            => 'required|integer|min:1',
            'admin_notes'          => 'nullable|string|max:1000',
            'admin_name'           => 'required|string|max:255',
            'admin_email'          => 'required|email|max:255',
            'admin_password'       => 'nullable|string|min:6',
            'trial_days'           => 'nullable|integer|min:1|max:90',
            'plan_expires_days'    => 'nullable|integer|min:1|max:730',
        ]);

        // Check if admin email already exists in users table
        $existingUser = DB::table('users')->where('email', $data['admin_email'])->first();

        // If no existing user and no password provided, reject with validation error
        if (!$existingUser && empty($data['admin_password'])) {
            return back()->withInput()->withErrors([
                'admin_password' => 'Password is required when creating a new admin account.',
            ]);
        }

        $slug = $this->generateUniqueSlug($data['name']);

        $plainPassword = $data['admin_password'] ?? null;

        $trialDays       = !empty($data['trial_days'])        ? (int)$data['trial_days']        : null;
        $planExpiresDays = !empty($data['plan_expires_days']) ? (int)$data['plan_expires_days'] : null;

        DB::transaction(function () use ($data, $slug, $trialDays, $planExpiresDays, $existingUser) {
            $hotelId = DB::table('hotels')->insertGetId([
                'name'                 => $data['name'],
                'slug'                 => $slug,
                'email'                => $data['email'] ?? null,
                'phone'                => $data['phone'] ?? null,
                'address'              => $data['address'] ?? null,
                'plan'                 => $trialDays ? 'trial' : $data['plan'],
                'billing_cycle'        => $data['billing_cycle'],
                'custom_monthly_price' => $data['custom_monthly_price'] ?: null,
                'custom_yearly_price'  => $data['custom_yearly_price'] ?: null,
                'status'               => 'active',
                'max_rooms'            => $data['max_rooms'],
                'max_users'            => $data['max_users'],
                'admin_notes'          => $data['admin_notes'] ?? null,
                'trial_ends_at'        => $trialDays ? now()->addDays($trialDays) : null,
                'plan_expires_at'      => $planExpiresDays ? now()->addDays($planExpiresDays) : null,
                'created_at'           => now(),
                'updated_at'           => now(),
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
                ['slug' => 'whatsapp',          'name' => 'WhatsApp Automation',       'description' => 'Automated WhatsApp messages.',                   'is_enabled' => false],
                ['slug' => 'payment_links',     'name' => 'Payment Links',             'description' => 'Generate UPI QR codes and payment links.',        'is_enabled' => false],
                ['slug' => 'pathik',            'name' => 'Pathik Autofill',           'description' => 'Gujarat Pathik portal autofill.',                 'is_enabled' => false],
                ['slug' => 'channel_manager',   'name' => 'OTA Channel Manager',       'description' => 'Sync with OTA platforms.',                        'is_enabled' => false],
                ['slug' => 'time-slot-pricing', 'name' => 'Time Slot Pricing',  'description' => 'Enable fixed time-block (per-slot) room pricing and booking.', 'is_enabled' => false],
                ['slug' => 'hourly-pricing',    'name' => 'Hourly Pricing',    'description' => 'Enable per-hour room pricing and booking.',                    'is_enabled' => false],
                ['slug' => 'extra-billing',       'name' => 'Extra Billing',         'description' => 'Add post-booking charges (food, laundry, services, etc.) to occupied or confirmed bookings and reflect them on the final bill.', 'is_enabled' => false],
                ['slug' => 'booking-widget',      'name' => 'Booking Widget',         'description' => 'Embeddable website booking form. Guests book directly from your hotel website and bookings appear in CRM instantly.', 'is_enabled' => false],
                ['slug' => 'whole-hotel-booking', 'name' => 'Whole Hotel Booking',    'description' => 'Allow booking the entire hotel at once — all rooms are blocked and the calendar shows a whole-hotel banner.', 'is_enabled' => false],
                ['slug' => 'slot-search-engine',  'name' => 'Slot Search Engine',     'description' => 'Full-screen multi-filter search for slot availability across date ranges, slot types, rooms, and booking status.', 'is_enabled' => false],
                ['slug' => 'ota_whatsapp_sync',   'name' => 'OTA WhatsApp Sync',      'description' => 'Automatically detect and import bookings from OTA WhatsApp confirmation messages (Booking.com, Airbnb, Agoda, MakeMyTrip, Goibibo etc.).', 'is_enabled' => false],
                ['slug' => 'email-parser',        'name' => 'OTA Email Parser',       'description' => 'Auto-read OTA booking confirmation emails (Booking.com, Airbnb, MakeMyTrip, Goibibo, Agoda, Expedia) via IMAP every 5 minutes — auto-creates guests and bookings, detects conflicts.', 'is_enabled' => false],
                // Dormant: standalone Food Menu module — superseded by Restaurant QR.
                // ['slug' => 'food-menu',           'name' => 'QR Food Menu',           'description' => 'In-room food ordering via QR code. Guests scan, browse menu, and place orders that staff approve and auto-bill to the room.', 'is_enabled' => false],
            ];
            foreach ($modules as $m) {
                DB::table('modules')->insert(array_merge($m, [
                    'hotel_id'   => $hotelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            $this->provisionRoles($hotelId);
            $this->seedWhatsAppTemplates($hotelId);
            HotelBackupSetting::create(['hotel_id' => $hotelId, 'auto_backup_enabled' => true, 'interval_hours' => 168, 'retention_count' => 3]);

            if ($existingUser) {
                // User already exists — link them to the new hotel as admin
                $adminId = $existingUser->id;
                // If they're not already linked to this hotel, add the pivot row
                $alreadyLinked = DB::table('hotel_users')
                    ->where('hotel_id', $hotelId)
                    ->where('user_id', $adminId)
                    ->exists();
                if (!$alreadyLinked) {
                    DB::table('hotel_users')->insert([
                        'hotel_id'       => $hotelId,
                        'user_id'        => $adminId,
                        'role'           => 'Admin',
                        'is_hotel_admin' => 1,
                        'status'         => 'active',
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            } else {
                // New user — create and link
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
            }
        });

        $userVerb = $existingUser ? 'linked existing user' : 'created new admin user';

        // Send welcome / onboarding email to the hotel admin
        try {
            Mail::to($data['admin_email'])->send(new HotelWelcomeMail(
                hotelName:     $data['name'],
                adminName:     $existingUser ? $existingUser->name : $data['admin_name'],
                adminEmail:    $data['admin_email'],
                adminPassword: $plainPassword ?? '(use your existing password)',
                plan:          $data['plan'],
            ));
            $emailNote = ' Welcome email sent to ' . $data['admin_email'] . '.';
        } catch (\Throwable $e) {
            $emailNote = ' (Welcome email could not be sent — check SMTP settings.)';
        }

        return redirect()->route('platform.hotels.index')
            ->with('success', "Hotel \"{$data['name']}\" created and provisioned. {$userVerb} {$data['admin_email']}." . $emailNote);
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

        // Fetch other hotels where this same user is also the hotel admin (is_hotel_admin=1 only)
        $relatedHotels = collect();
        if ($hotelAdmin) {
            $relatedHotels = DB::table('hotel_users')
                ->join('hotels', 'hotels.id', '=', 'hotel_users.hotel_id')
                ->where('hotel_users.user_id', $hotelAdmin->id)
                ->where('hotel_users.is_hotel_admin', 1)
                ->where('hotel_users.hotel_id', '!=', $id)
                ->select('hotels.id', 'hotels.name', 'hotels.plan', 'hotels.status')
                ->orderBy('hotels.name')
                ->get();
        }

        $backupSetting = HotelBackupSetting::firstOrCreate(
            ['hotel_id' => $id],
            ['auto_backup_enabled' => true, 'interval_hours' => 168, 'retention_count' => 3]
        );

        return view('platform.hotels.edit', compact('hotel', 'plans', 'hotelAdmin', 'hotelUsers', 'relatedHotels', 'backupSetting'));
    }

    // ── Add Related Hotel (same admin, no new credentials needed) ────────────

    public function addRelatedHotel(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();
        if (!$hotel) {
            return redirect()->route('platform.hotels.edit', $id)->with('error', 'Hotel not found.');
        }

        // Find the current hotel's admin
        $hotelAdmin = DB::table('hotel_users')
            ->join('users', 'users.id', '=', 'hotel_users.user_id')
            ->where('hotel_users.hotel_id', $id)
            ->where('hotel_users.is_hotel_admin', 1)
            ->select('users.id', 'users.name', 'users.email')
            ->first();

        if (!$hotelAdmin) {
            return back()->with('error', 'No admin user found for this hotel. Please assign a hotel admin first.');
        }

        $validSlugs = DB::table('platform_plans')->where('is_active', true)->pluck('slug')->toArray();
        if (empty($validSlugs)) {
            $validSlugs = array_keys(config('plans', []));
        }

        $data = $request->validate([
            'new_hotel_name'          => 'required|string|max:255',
            'new_hotel_plan'          => 'required|in:' . implode(',', $validSlugs),
            'new_hotel_billing_cycle' => 'required|in:monthly,yearly',
            'new_hotel_trial_days'    => 'nullable|integer|min:1|max:90',
            'new_hotel_expires_days'  => 'nullable|integer|min:1|max:730',
        ]);

        // Determine max_rooms / max_users from the chosen plan
        $planRow  = DB::table('platform_plans')->where('slug', $data['new_hotel_plan'])->first();
        $maxRooms = min($planRow->max_rooms ?? 10, 999);
        $maxUsers = min($planRow->max_users ?? 5,  999);

        $trialDays   = !empty($data['new_hotel_trial_days'])   ? (int)$data['new_hotel_trial_days']   : null;
        $expiresDays = !empty($data['new_hotel_expires_days']) ? (int)$data['new_hotel_expires_days'] : null;

        $slug = $this->generateUniqueSlug($data['new_hotel_name']);

        DB::transaction(function () use ($data, $slug, $hotelAdmin, $trialDays, $expiresDays, $maxRooms, $maxUsers) {
            $newHotelId = DB::table('hotels')->insertGetId([
                'name'            => $data['new_hotel_name'],
                'slug'            => $slug,
                'plan'            => $trialDays ? 'trial' : $data['new_hotel_plan'],
                'billing_cycle'   => $data['new_hotel_billing_cycle'],
                'status'          => 'active',
                'max_rooms'       => $maxRooms,
                'max_users'       => $maxUsers,
                'trial_ends_at'   => $trialDays   ? now()->addDays($trialDays)   : null,
                'plan_expires_at' => $expiresDays ? now()->addDays($expiresDays) : null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('settings')->insert([
                'hotel_id'        => $newHotelId,
                'resort_name'     => $data['new_hotel_name'],
                'address'         => '',
                'phone'           => '',
                'email'           => '',
                'tax_rate'        => '12',
                'currency'        => 'INR',
                'currency_symbol' => 'Rs',
                'check_in_time'   => '12:00',
                'check_out_time'  => '11:00',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ([
                ['slug' => 'whatsapp',          'name' => 'WhatsApp Automation',       'description' => 'Automated WhatsApp messages.',                   'is_enabled' => false],
                ['slug' => 'payment_links',     'name' => 'Payment Links',             'description' => 'Generate UPI QR codes and payment links.',        'is_enabled' => false],
                ['slug' => 'pathik',            'name' => 'Pathik Autofill',           'description' => 'Gujarat Pathik portal autofill.',                 'is_enabled' => false],
                ['slug' => 'channel_manager',   'name' => 'OTA Channel Manager',       'description' => 'Sync with OTA platforms.',                        'is_enabled' => false],
                ['slug' => 'time-slot-pricing', 'name' => 'Time Slot Pricing',  'description' => 'Enable fixed time-block (per-slot) room pricing and booking.', 'is_enabled' => false],
                ['slug' => 'hourly-pricing',    'name' => 'Hourly Pricing',     'description' => 'Enable per-hour room pricing and booking.',                    'is_enabled' => false],
                ['slug' => 'extra-billing',       'name' => 'Extra Billing',       'description' => 'Add post-booking charges (food, laundry, services, etc.) to occupied or confirmed bookings and reflect them on the final bill.', 'is_enabled' => false],
                ['slug' => 'booking-widget',      'name' => 'Booking Widget',      'description' => 'Embeddable website booking form. Guests book directly from your hotel website and bookings appear in CRM instantly.', 'is_enabled' => false],
                ['slug' => 'whole-hotel-booking', 'name' => 'Whole Hotel Booking', 'description' => 'Allow booking the entire hotel at once — all rooms are blocked and the calendar shows a whole-hotel banner.', 'is_enabled' => false],
                ['slug' => 'slot-search-engine',  'name' => 'Slot Search Engine',  'description' => 'Full-screen multi-filter search for slot availability across date ranges, slot types, rooms, and booking status.', 'is_enabled' => false],
                ['slug' => 'ota_whatsapp_sync',   'name' => 'OTA WhatsApp Sync',   'description' => 'Automatically detect and import bookings from OTA WhatsApp confirmation messages (Booking.com, Airbnb, Agoda, MakeMyTrip, Goibibo etc.).', 'is_enabled' => false],
                ['slug' => 'email-parser',        'name' => 'OTA Email Parser',    'description' => 'Auto-read OTA booking confirmation emails (Booking.com, Airbnb, MakeMyTrip, Goibibo, Agoda, Expedia) via IMAP every 5 minutes — auto-creates guests and bookings, detects conflicts.', 'is_enabled' => false],
                // Dormant: standalone Food Menu module — superseded by Restaurant QR.
                // ['slug' => 'food-menu',           'name' => 'QR Food Menu',        'description' => 'In-room food ordering via QR code. Guests scan, browse menu, and place orders that staff approve and auto-bill to the room.', 'is_enabled' => false],
            ] as $m) {
                DB::table('modules')->insert(array_merge($m, ['hotel_id' => $newHotelId, 'created_at' => now(), 'updated_at' => now()]));
            }

            $this->provisionRoles($newHotelId);
            $this->seedWhatsAppTemplates($newHotelId);
            HotelBackupSetting::create(['hotel_id' => $newHotelId, 'auto_backup_enabled' => true, 'interval_hours' => 168, 'retention_count' => 3]);

            DB::table('hotel_users')->insert([
                'hotel_id'       => $newHotelId,
                'user_id'        => $hotelAdmin->id,
                'role'           => 'Admin',
                'is_hotel_admin' => 1,
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "Hotel \"{$data['new_hotel_name']}\" created and fully provisioned. Admin {$hotelAdmin->name} ({$hotelAdmin->email}) linked automatically.");
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
            'name'                 => 'required|string|max:255',
            'ota_alias'            => 'nullable|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:50',
            'address'              => 'nullable|string|max:500',
            'plan'                 => 'required|in:' . implode(',', $validSlugs),
            'billing_cycle'        => 'required|in:monthly,yearly',
            'custom_monthly_price' => 'nullable|integer|min:0',
            'custom_yearly_price'  => 'nullable|integer|min:0',
            'max_rooms'            => 'required|integer|min:1',
            'max_users'            => 'required|integer|min:1',
            'status'               => 'required|in:active,suspended',
            'admin_notes'          => 'nullable|string|max:1000',
            'new_admin_user_id'    => 'nullable|integer',
            'backup_auto_enabled'  => 'nullable|boolean',
            'backup_interval'      => 'nullable|in:24,168,720',
        ]);

        DB::table('hotels')->where('id', $id)->update([
            'name'                        => $data['name'],
            'ota_alias'                   => $data['ota_alias'] ?? null,
            'email'                       => $data['email'] ?? null,
            'phone'                       => $data['phone'] ?? null,
            'address'                     => $data['address'] ?? null,
            'owner_wa_consent'            => $request->boolean('owner_wa_consent'),
            'plan'                        => $data['plan'],
            'billing_cycle'               => $data['billing_cycle'],
            'custom_monthly_price'        => $data['custom_monthly_price'] ?: null,
            'custom_yearly_price'         => $data['custom_yearly_price'] ?: null,
            'max_rooms'                   => $data['max_rooms'],
            'max_users'                   => $data['max_users'],
            'status'                      => $data['status'],
            'admin_notes'                 => $data['admin_notes'] ?? null,
            'billing_included_in_parent'  => $request->boolean('billing_included_in_parent'),
            'updated_at'                  => now(),
        ]);

        // Save backup settings
        HotelBackupSetting::updateOrCreate(
            ['hotel_id' => $id],
            [
                'auto_backup_enabled' => $request->boolean('backup_auto_enabled'),
                'interval_hours'      => (int) ($data['backup_interval'] ?? 168),
                'retention_count'     => 3,
            ]
        );

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
                DB::transaction(function () use ($id, $newAdminUserId) {
                    // Strip is_hotel_admin from all hotel users for this hotel
                    DB::table('hotel_users')
                        ->where('hotel_id', $id)
                        ->update(['is_hotel_admin' => 0]);

                    // Set the selected user as hotel admin
                    DB::table('hotel_users')
                        ->where('hotel_id', $id)
                        ->where('user_id', $newAdminUserId)
                        ->update(['is_hotel_admin' => 1]);
                });
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

    // ── Send Welcome Email ────────────────────────────────────────────────────

    public function sendWelcomeEmail(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();
        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        $data = $request->validate([
            'admin_email'    => 'required|email',
            'admin_name'     => 'required|string|max:255',
            'admin_password' => 'required|string|min:4',
        ]);

        try {
            Mail::to($data['admin_email'])->send(new HotelWelcomeMail(
                hotelName:     $hotel->name,
                adminName:     $data['admin_name'],
                adminEmail:    $data['admin_email'],
                adminPassword: $data['admin_password'],
                plan:          $hotel->plan ?? 'Basic',
            ));
            return redirect()->back()
                ->with('success', "Welcome email sent to {$data['admin_email']} for \"{$hotel->name}\".");
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Failed to send email: ' . $e->getMessage() . '. Please check your SMTP settings.');
        }
    }

    // ── Create User for Hotel ─────────────────────────────────────────────────

    public function storeUser(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        $data = $request->validate([
            'user_name'      => 'required|string|max:255',
            'user_email'     => 'required|email|max:255|unique:users,email',
            'user_password'  => 'required|string|min:6',
            'user_role'      => 'required|in:Admin,Manager,Receptionist',
            'make_admin'     => 'nullable|boolean',
        ]);

        $makeAdmin = $request->boolean('make_admin');

        DB::transaction(function () use ($data, $id, $makeAdmin) {
            $userId = DB::table('users')->insertGetId([
                'name'           => $data['user_name'],
                'email'          => $data['user_email'],
                'password'       => Hash::make($data['user_password']),
                'role'           => $data['user_role'],
                'status'         => 'active',
                'is_super_admin' => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // If making admin, strip existing admin flag first
            if ($makeAdmin) {
                DB::table('hotel_users')
                    ->where('hotel_id', $id)
                    ->update(['is_hotel_admin' => 0]);
            }

            DB::table('hotel_users')->insert([
                'hotel_id'       => $id,
                'user_id'        => $userId,
                'role'           => $data['user_role'],
                'is_hotel_admin' => $makeAdmin ? 1 : 0,
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "User \"{$data['user_name']}\" created and added to {$hotel->name}." . ($makeAdmin ? ' They are now the Hotel Admin.' : ''));
    }

    // ── Trial activation / plan extension (Task #19) ─────────────────────────

    public function activateTrial(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();
        if (!$hotel) {
            return redirect()->route('platform.hotels.edit', $id)->with('error', 'Hotel not found.');
        }

        // Fixed 7-day trial; allow custom days only if explicitly submitted by platform admin
        $days = 7;
        if ($request->has('trial_days')) {
            $days = max(1, min((int) $request->input('trial_days', 7), 90));
        }

        DB::table('hotels')->where('id', $id)->update([
            'plan'          => 'trial',
            'trial_ends_at' => now()->addDays($days),
            'updated_at'    => now(),
        ]);

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "{$hotel->name} — {$days}-day free trial activated.");
    }

    public function extendPlan(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();
        if (!$hotel) {
            return redirect()->route('platform.hotels.edit', $id)->with('error', 'Hotel not found.');
        }

        $days = (int) ($request->input('extend_days', 30));
        $days = max(1, min($days, 365));

        // Extend from today or from existing expiry (whichever is later)
        $base = $hotel->plan_expires_at && now()->lt(\Carbon\Carbon::parse($hotel->plan_expires_at))
              ? \Carbon\Carbon::parse($hotel->plan_expires_at)
              : now();

        DB::table('hotels')->where('id', $id)->update([
            'plan_expires_at' => $base->addDays($days),
            'updated_at'      => now(),
        ]);

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "{$hotel->name} — plan extended by {$days} days.");
    }

    public function cancelTrial(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();
        if (!$hotel) {
            return redirect()->route('platform.hotels.edit', $id)->with('error', 'Hotel not found.');
        }

        $validSlugs = DB::table('platform_plans')->where('is_active', true)->pluck('slug')->toArray();
        if (empty($validSlugs)) {
            $validSlugs = array_keys(config('plans', []));
        }
        $revertPlan = $request->input('revert_plan', 'basic');
        if (!in_array($revertPlan, $validSlugs)) {
            $revertPlan = $validSlugs[0] ?? 'basic';
        }

        DB::table('hotels')->where('id', $id)->update([
            'plan'          => $revertPlan,
            'trial_ends_at' => null,
            'updated_at'    => now(),
        ]);

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "{$hotel->name} — trial cancelled. Plan set to {$revertPlan}.");
    }

    public function cancelPlanExpiry(Request $request, int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();
        if (!$hotel) {
            return redirect()->route('platform.hotels.edit', $id)->with('error', 'Hotel not found.');
        }

        DB::table('hotels')->where('id', $id)->update([
            'plan_expires_at' => null,
            'updated_at'      => now(),
        ]);

        return redirect()->route('platform.hotels.edit', $id)
            ->with('success', "{$hotel->name} — plan expiry cleared (no expiry date).");
    }

    // ── Destroy (hard delete) ─────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $hotel = DB::table('hotels')->where('id', $id)->first();

        if (!$hotel) {
            return redirect()->route('platform.hotels.index')->with('error', 'Hotel not found.');
        }

        if ($hotel->status !== 'suspended') {
            return redirect()->route('platform.hotels.index')
                ->with('error', "Cannot delete \"{$hotel->name}\" — hotel must be suspended first before deletion.");
        }

        DB::transaction(function () use ($id) {
            // 1. Channel sub-tables (depend on bookings / rooms)
            DB::table('channel_bookings')->where('hotel_id', $id)->delete();
            DB::table('channel_room_mappings')->where('hotel_id', $id)->delete();

            // 2. Booking guest links (pivot on booking_id)
            $bookingIds = DB::table('bookings')->where('hotel_id', $id)->pluck('id');
            if ($bookingIds->isNotEmpty()) {
                DB::table('booking_guests')->whereIn('booking_id', $bookingIds)->delete();
            }

            // 3. Financial records
            DB::table('invoices')->where('hotel_id', $id)->delete();
            DB::table('payments')->where('hotel_id', $id)->delete();
            DB::table('bookings')->where('hotel_id', $id)->delete();

            // 4. Customer documents (pivot on customer_id) — include soft-deleted customers
            $customerIds = DB::table('customers')->where('hotel_id', $id)->pluck('id');
            if ($customerIds->isNotEmpty()) {
                DB::table('customer_documents')->whereIn('customer_id', $customerIds)->delete();
            }
            DB::table('customers')->where('hotel_id', $id)->delete();

            // 5. Operational tables
            DB::table('rooms')->where('hotel_id', $id)->delete();
            DB::table('activity_logs')->where('hotel_id', $id)->delete();

            // 6. Config / module tables
            DB::table('whatsapp_templates')->where('hotel_id', $id)->delete();
            DB::table('whatsapp_configs')->where('hotel_id', $id)->delete();
            DB::table('pathik_configs')->where('hotel_id', $id)->delete();
            DB::table('channel_manager_configs')->where('hotel_id', $id)->delete();
            DB::table('payment_link_configs')->where('hotel_id', $id)->delete();
            DB::table('modules')->where('hotel_id', $id)->delete();

            // 7. Roles + permissions pivot
            $roleIds = DB::table('roles')->where('hotel_id', $id)->pluck('id');
            if ($roleIds->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
            }
            DB::table('roles')->where('hotel_id', $id)->delete();

            // 8. Settings
            DB::table('settings')->where('hotel_id', $id)->delete();

            // 9. Users — delete hotel_users pivot, then orphaned users (no remaining hotels)
            $userIds = DB::table('hotel_users')->where('hotel_id', $id)->pluck('user_id');
            DB::table('hotel_users')->where('hotel_id', $id)->delete();

            if ($userIds->isNotEmpty()) {
                // Only delete users who no longer belong to any other hotel
                $stillLinked = DB::table('hotel_users')
                    ->whereIn('user_id', $userIds)
                    ->pluck('user_id')
                    ->unique();

                $orphanedUserIds = $userIds->diff($stillLinked);

                if ($orphanedUserIds->isNotEmpty()) {
                    // Clean up recovery codes before deleting users
                    DB::table('platform_recovery_codes')->whereIn('user_id', $orphanedUserIds)->delete();
                    DB::table('users')->whereIn('id', $orphanedUserIds)->delete();
                }
            }

            // 10. Delete the hotel itself
            DB::table('hotels')->where('id', $id)->delete();
        });

        return redirect()->route('platform.hotels.index')
            ->with('success', "Hotel \"{$hotel->name}\" and all associated data have been permanently deleted.");
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
        $allSlugs = [
            'guests.view', 'guests.create', 'guests.edit', 'guests.delete',
            'rooms.view', 'rooms.create', 'rooms.edit', 'rooms.delete',
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
            'checkin.process', 'checkout.process',
            'payments.view', 'payments.create', 'payments.delete',
            'invoices.view', 'invoices.delete',
            'reports.view',
            'settings.view', 'settings.edit',
            'activity_log.view',
            'roles.view', 'roles.edit',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'whatsapp.send',
            // Dormant: food_menu.* permissions (module disabled).
            // 'food_menu.manage', 'food_menu.orders.view', 'food_menu.orders.manage',
        ];

        // Permissions excluded from Admin by default (must be granted explicitly by SaaS admin)
        $adminExcluded = [
            'whatsapp.send',
            // Delete permissions — never auto-granted; SaaS admin must enable per hotel
            'guests.delete', 'rooms.delete', 'bookings.delete',
            'payments.delete', 'invoices.delete', 'users.delete',
        ];

        // Permissions excluded from Manager by default
        $managerExcluded = [
            'settings.view', 'roles.view', 'roles.edit',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            // Delete permissions — admin must grant manually
            'guests.delete', 'rooms.delete', 'bookings.delete',
            'payments.delete', 'invoices.delete',
            'whatsapp.send',
        ];

        $all       = array_values(array_filter($allSlugs, fn($s) => !in_array($s, $adminExcluded)));
        $limited   = array_values(array_filter($allSlugs, fn($s) => !in_array($s, $managerExcluded)));
        $frontdesk = ['guests.view', 'guests.create', 'guests.edit', 'rooms.view',
                      'bookings.view', 'bookings.create', 'bookings.edit',
                      'checkin.process', 'checkout.process', 'payments.view',
                      'payments.create', 'invoices.view'];

        // Ensure the permissions catalog is populated (idempotent seed)
        $existingSlugs = Permission::pluck('slug')->all();
        $missingSlugs  = array_diff($allSlugs, $existingSlugs);
        if (!empty($missingSlugs)) {
            $permLabels = [
                'guests.view' => ['View Guests','Guests',1], 'guests.create' => ['Add Guests','Guests',2],
                'guests.edit' => ['Edit Guests','Guests',3], 'guests.delete' => ['Delete Guests','Guests',4],
                'rooms.view'  => ['View Rooms','Rooms',5],   'rooms.create'  => ['Add Rooms','Rooms',6],
                'rooms.edit'  => ['Edit Rooms','Rooms',7],   'rooms.delete'  => ['Delete Rooms','Rooms',8],
                'bookings.view'   => ['View Bookings','Bookings',9],  'bookings.create' => ['Create Bookings','Bookings',10],
                'bookings.edit'   => ['Edit Bookings','Bookings',11], 'bookings.delete' => ['Delete Bookings','Bookings',12],
                'checkin.process' => ['Process Check-In','Operations',13], 'checkout.process' => ['Process Check-Out','Operations',14],
                'payments.view'   => ['View Payments','Payments',15], 'payments.create' => ['Record Payments','Payments',16],
                'payments.delete' => ['Delete Payments','Payments',17],
                'invoices.view'   => ['View Invoices','Invoices',18], 'invoices.delete' => ['Delete Invoices','Invoices',19],
                'reports.view'    => ['View Reports','Reports',20],
                'settings.view'   => ['View Settings','Settings',21], 'settings.edit' => ['Edit Settings','Settings',22],
                'activity_log.view' => ['View Activity Log','System',23],
                'roles.view'  => ['View Roles & Permissions','System',24], 'roles.edit' => ['Edit Roles & Permissions','System',25],
                'users.view'  => ['View Users','Users',26], 'users.create' => ['Create Users','Users',27],
                'users.edit'  => ['Edit Users','Users',28],  'users.delete' => ['Delete Users','Users',29],
                'whatsapp.send' => ['Send WhatsApp Messages','WhatsApp',30],
                // Dormant: food_menu.* labels (module disabled).
                // 'food_menu.manage'         => ['Manage Food Menu','Food Menu',31],
                // 'food_menu.orders.view'    => ['View Food Orders','Food Menu',32],
                // 'food_menu.orders.manage'  => ['Approve / Manage Food Orders','Food Menu',33],
            ];
            $now = now();
            foreach ($missingSlugs as $slug) {
                [$label, $module, $sort] = $permLabels[$slug] ?? [$slug, 'System', 99];
                DB::table('permissions')->insertOrIgnore([
                    'slug' => $slug, 'label' => $label, 'module' => $module,
                    'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $defs = [
            ['name' => 'Admin',        'description' => 'Full access',                'is_system' => true, 'perms' => $all],
            ['name' => 'Manager',      'description' => 'Manage bookings and reports', 'is_system' => true, 'perms' => $limited],
            ['name' => 'Receptionist', 'description' => 'Front-desk operations',       'is_system' => true, 'perms' => $frontdesk],
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

    private function seedWhatsAppTemplates(int $hotelId): void
    {
        $templates = [
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

        foreach ($templates as $t) {
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
    }

    // ── Platform WhatsApp templates for hotel owner messaging ─────────────────
    public static function platformWaTemplates(): array
    {
        $dashboardUrl = 'https://resort.dreamstechnology.in/';

        // ── Dynamically resolve the latest APPROVED version from DB ──────────────
        // This means after any body edit + auto-versioning + Meta approval,
        // the send code picks up the new name automatically — no hardcoding.
        $crmName = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where(function ($q) {
                $q->where('template_name', 'crm_dashboard_update')
                  ->orWhere('template_name', 'LIKE', 'crm_dashboard_update_v%');
            })
            ->where('approval_status', 'approved')
            ->orderByDesc('id')
            ->value('template_name') ?? 'crm_dashboard_update';

        $loginName = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where(function ($q) {
                $q->where('template_name', 'login_reminder')
                  ->orWhere('template_name', 'LIKE', 'login_reminder_v%');
            })
            ->where('approval_status', 'approved')
            ->orderByDesc('id')
            ->value('template_name') ?? 'login_reminder';

        $templates = [
            'crm_update' => [
                'label'       => 'CRM Dashboard Update',
                'meta_name'   => $crmName,
                'language'    => 'en_US',
                'preview'     => "Hello {name},\n\nYour hotel CRM dashboard has recent updates that can help you manage bookings and customer communication more efficiently.\n\nStay on top of your operations and avoid missing any important updates.\n\n👉 Access your dashboard: {url}\n\nFor support, message us on WhatsApp at +919725225519.\n\n– Dreams Technology",
                'var1'        => 'hotel_name',
                'var2'        => $dashboardUrl,
                'param_count' => 2,
                'is_custom'   => false,
            ],
            'login_reminder' => [
                'label'       => 'Login Reminder',
                'meta_name'   => $loginName,
                'language'    => 'en_US',
                'preview'     => "Hello {name},\n\nWe noticed you haven't logged into your Hotel CRM in a while. Your bookings and guests need attention!\n\n👉 Login here: {url}\n\nFor support, message us on WhatsApp at +919725225519.\n\n– Dreams Technology",
                'var1'        => 'hotel_name',
                'var2'        => $dashboardUrl,
                'param_count' => 2,
                'is_custom'   => false,
            ],
            'final_reminder' => [
                'label'       => 'Final Reminder',
                'meta_name'   => DB::table('whatsapp_templates')
                    ->whereNull('hotel_id')
                    ->where(function ($q) {
                        $q->where('trigger_event', 'final_reminder')
                          ->orWhere('trigger_event', 'final.reminder')
                          ->orWhere('template_name', 'LIKE', '%Final Reminder%');
                    })
                    ->where('approval_status', 'approved')
                    ->orderByDesc('id')
                    ->value('template_name') ?? 'final_reminder',
                'language'    => 'en_US',
                'preview'     => "Hi {hotel_name}, this is the final reminder for your trial period over. If you are not interested, please reply stop and all future updates will be paused.",
                'var1'        => 'hotel_name',
                'var2'        => $dashboardUrl,
                'param_count' => 2,
                'is_custom'   => true,
            ],
        ];

        return $templates;
    }

    // ── Fetch live approved templates from Meta ────────────────────────────────
    public function fetchApprovedWaTemplates(): \Illuminate\Http\JsonResponse
    {
        $platform = PlatformWhatsAppSetting::instance();

        if (!$platform?->saas_token || !$platform?->saas_waba_id) {
            return response()->json(['success' => false, 'templates' => [], 'message' => 'Platform WhatsApp not configured.']);
        }

        try {
            $response = Http::timeout(15)
                ->get("https://graph.facebook.com/v19.0/{$platform->saas_waba_id}/message_templates", [
                    'access_token' => $platform->saas_token,
                    'status'       => 'APPROVED',
                    'fields'       => 'name,status,language,components',
                    'limit'        => 100,
                ]);

            $data = $response->json();

            if (!$response->successful() || !isset($data['data'])) {
                return response()->json(['success' => false, 'templates' => [], 'message' => $data['error']['message'] ?? 'Meta API error.']);
            }

            $templates = collect($data['data'])->map(function ($tpl) {
                $body = collect($tpl['components'] ?? [])->firstWhere('type', 'BODY');
                $preview = $body['text'] ?? '';
                // Truncate preview for display
                $preview = mb_strlen($preview) > 120 ? mb_substr($preview, 0, 120) . '…' : $preview;
                return [
                    'name'     => $tpl['name'],
                    'language' => $tpl['language'],
                    'preview'  => $preview,
                    'label'    => ucwords(str_replace('_', ' ', $tpl['name'])),
                ];
            })->values()->all();

            return response()->json(['success' => true, 'templates' => $templates]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'templates' => [], 'message' => $e->getMessage()]);
        }
    }

    // ── Send quick WhatsApp template to hotel owner ────────────────────────────
    public function sendQuickWA(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $hotel    = DB::table('hotels')->where('id', $id)->first();
        $platform = PlatformWhatsAppSetting::instance();

        if (!$hotel) {
            return response()->json(['success' => false, 'message' => '❌ Hotel not found.']);
        }
        if (!$hotel->phone) {
            return response()->json(['success' => false, 'message' => '❌ This hotel has no phone number set. Add one in Edit Hotel.']);
        }
        if (!$hotel->owner_wa_consent) {
            return response()->json(['success' => false, 'message' => '❌ Owner has not consented to receive WhatsApp messages. Tick the consent box on the Users list or in Edit Hotel first.']);
        }
        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            return response()->json(['success' => false, 'message' => '❌ Platform WhatsApp not configured. Check WhatsApp Settings.']);
        }

        // Accept either a raw template_name+language OR legacy template_key
        $templateName = $request->input('template_name');
        $templateLang = $request->input('template_language', 'en');

        if (!$templateName) {
            // Legacy fallback: map template_key to static template
            $templateKey = $request->input('template_key');
            $templates   = self::platformWaTemplates();
            if (!isset($templates[$templateKey])) {
                return response()->json(['success' => false, 'message' => '❌ No template selected.']);
            }
            $templateName = $templates[$templateKey]['meta_name'];
            $templateLang = $templates[$templateKey]['language'];
        }

        $dashboardUrl = 'https://resort.dreamstechnology.in/';

        // Detect actual param count from DB; fall back via name hint, then to 2
        $dbTpl = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where('template_name', $templateName)
            ->orderByDesc('id')
            ->value('message_body');
        $paramCount = null;
        if ($dbTpl && preg_match_all('/\{\{(\d+)\}\}/', $dbTpl, $pm) && !empty($pm[1])) {
            $paramCount = (int) max($pm[1]);
        }
        if ($paramCount === null) {
            // Name-based fallback: final_reminder has 1 param; crm/login templates have 2
            $paramCount = stripos($templateName, 'final') !== false ? 1 : 2;
        }

        $paramValues = [1 => $hotel->name, 2 => $dashboardUrl];
        $bodyParams  = [];
        for ($i = 1; $i <= max($paramCount, 1); $i++) {
            $bodyParams[] = ['type' => 'text', 'text' => $paramValues[$i] ?? ''];
        }
        $components = [['type' => 'body', 'parameters' => $bodyParams]];

        $phone = PhoneHelper::forWhatsApp($hotel->phone);

        // Language codes to try in order. Meta requires the code to match exactly
        // what was used when the template was created. 'en' and 'en_US' are both common.
        $langCodesToTry = array_unique(array_filter([$templateLang, 'en_US', 'en']));

        try {
            $lastBody    = [];
            $lastErrMsg  = 'Unknown error';
            $lastErrCode = 0;

            foreach ($langCodesToTry as $lang) {
                $response = Http::timeout(15)->withToken($platform->saas_token)
                    ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to'                => $phone,
                        'type'              => 'template',
                        'template'          => [
                            'name'       => $templateName,
                            'language'   => ['code' => $lang],
                            'components' => $components,
                        ],
                    ]);

                $body = $response->json();

                if ($response->successful() && isset($body['messages'])) {
                    Log::info("Platform Quick WA sent to hotel {$id} ({$hotel->name})", [
                        'template' => $templateName,
                        'language' => $lang,
                    ]);
                    return response()->json(['success' => true, 'message' => '✅ WhatsApp sent to ' . $hotel->name]);
                }

                $lastBody    = $body;
                $lastErrMsg  = $body['error']['message'] ?? 'Unknown error';
                $lastErrCode = $body['error']['code'] ?? 0;

                // 132001 = template not approved / wrong language — try next language code
                // Any other error is definitive; stop retrying
                if ($lastErrCode !== 132001) {
                    break;
                }
            }

            if ($lastErrCode === 132001) {
                return response()->json(['success' => false, 'message' => "⚠️ Template \"{$templateName}\" is not approved in Meta Business Manager. Please check its status and resubmit if needed."]);
            }

            Log::warning("Platform Quick WA failed for hotel {$id}", ['error' => $lastBody]);
            return response()->json(['success' => false, 'message' => "❌ Meta error: {$lastErrMsg}"]);

        } catch (\Throwable $e) {
            Log::error("Platform Quick WA exception for hotel {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => '❌ Failed: ' . $e->getMessage()]);
        }
    }

    // ── Send WA to ALL consented hotel owners (deduplicated by phone) ─────────
    public function sendWaAll(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $platform = PlatformWhatsAppSetting::instance();

        if (!$platform?->saas_token || !$platform?->saas_phone_number_id) {
            return response()->json(['success' => false, 'message' => '❌ Platform WhatsApp not configured.']);
        }

        $templateName = $request->input('template_name');
        $templateLang = $request->input('template_language', 'en_US');

        if (!$templateName) {
            return response()->json(['success' => false, 'message' => '❌ No template selected.']);
        }

        $hotels = DB::table('hotels')
            ->where('owner_wa_consent', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get(['id', 'name', 'phone']);

        // Build body parameters from DB template (respect actual param count)
        $dashboardUrl    = 'https://resort.dreamstechnology.in/';
        $dbTplAllBody    = DB::table('whatsapp_templates')
            ->whereNull('hotel_id')
            ->where('template_name', $templateName)
            ->orderByDesc('id')
            ->value('message_body');
        $paramCountAll   = null;
        if ($dbTplAllBody && preg_match_all('/\{\{(\d+)\}\}/', $dbTplAllBody, $mAll) && !empty($mAll[1])) {
            $paramCountAll = (int) max($mAll[1]);
        }
        if ($paramCountAll === null) {
            $paramCountAll = stripos($templateName, 'final') !== false ? 1 : 2;
        }

        // Language codes to try in order (matching what was used when template was created in Meta)
        $langCodesToTryAll = array_unique(array_filter([$templateLang, 'en_US', 'en']));

        $sent              = 0;
        $skippedDuplicate  = 0;
        $skippedNoPhone    = 0;
        $errors            = [];
        $seenPhones        = [];

        foreach ($hotels as $hotel) {
            $phone = PhoneHelper::forWhatsApp($hotel->phone);

            if (strlen($phone) < 10) {
                $skippedNoPhone++;
                continue;
            }

            if (in_array($phone, $seenPhones)) {
                $skippedDuplicate++;
                continue;
            }

            $seenPhones[] = $phone;

            // Build positional params: {{1}}=hotel name, {{2}}=dashboard URL, rest=''
            $paramValuesAll = [1 => $hotel->name, 2 => $dashboardUrl];
            $bodyParamsAll  = [];
            for ($i = 1; $i <= max($paramCountAll, 1); $i++) {
                $bodyParamsAll[] = ['type' => 'text', 'text' => $paramValuesAll[$i] ?? ''];
            }
            $componentsAll = [['type' => 'body', 'parameters' => $bodyParamsAll]];

            try {
                $hotelSent   = false;
                $lastErrMsg  = 'Unknown error';
                $lastErrCode = 0;

                foreach ($langCodesToTryAll as $lang) {
                    $response = Http::timeout(15)->withToken($platform->saas_token)
                        ->post("https://graph.facebook.com/v19.0/{$platform->saas_phone_number_id}/messages", [
                            'messaging_product' => 'whatsapp',
                            'to'                => $phone,
                            'type'              => 'template',
                            'template'          => [
                                'name'       => $templateName,
                                'language'   => ['code' => $lang],
                                'components' => $componentsAll,
                            ],
                        ]);

                    $body = $response->json();
                    if ($response->successful() && isset($body['messages'])) {
                        $sent++;
                        $hotelSent = true;
                        break;
                    }

                    $lastErrMsg  = $body['error']['message'] ?? 'Unknown error';
                    $lastErrCode = $body['error']['code'] ?? 0;

                    if ($lastErrCode !== 132001) {
                        break;
                    }
                }

                if (!$hotelSent) {
                    $errors[] = "{$hotel->name}: {$lastErrMsg}";
                }
            } catch (\Throwable $e) {
                $errors[] = "{$hotel->name}: " . $e->getMessage();
            }
        }

        Log::info("Platform WA All: sent={$sent}, dupes={$skippedDuplicate}, no_phone={$skippedNoPhone}");

        return response()->json([
            'success'           => true,
            'sent'              => $sent,
            'skipped_duplicate' => $skippedDuplicate,
            'skipped_no_phone'  => $skippedNoPhone,
            'errors'            => $errors,
        ]);
    }

    // ── Send quick push notification to a single hotel ────────────────────────
    public function sendQuickPushHotel(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $fcm = app(\App\Services\FcmService::class);

        if (!$fcm->isEnabled()) {
            return response()->json(['success' => false, 'message' => '❌ Firebase not enabled.']);
        }

        $tokens = $fcm->getTokensForHotel($id);
        if (empty($tokens)) {
            return response()->json(['success' => false, 'message' => '⚠️ No devices registered for this hotel.']);
        }

        $title = trim($request->input('title', ''));
        $body  = trim($request->input('body', ''));

        if (!$title && !$body) {
            return response()->json(['success' => false, 'message' => '❌ Please enter a title or message body.']);
        }

        $result = $fcm->sendToTokens($tokens, $title ?: 'Platform Alert', $body);

        return response()->json([
            'success' => true,
            'message' => "✅ Push sent to {$result['success']} device(s)" . ($result['failure'] > 0 ? " (failed: {$result['failure']})" : ''),
        ]);
    }

    // ── Platform Admin: toggle a module on/off for a specific hotel ────────────
    public function moduleToggle(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $request->validate(['module' => 'required|string']);

        $hotel = \App\Models\Hotel::findOrFail($id);
        $module = $request->input('module');

        $row = DB::table('modules')
            ->where('hotel_id', $hotel->id)
            ->where('slug', $module)
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => "Module '{$module}' not found for hotel."], 404);
        }

        $newStatus = !$row->is_enabled;

        DB::table('modules')
            ->where('hotel_id', $hotel->id)
            ->where('slug', $module)
            ->update(['is_enabled' => $newStatus, 'updated_at' => now()]);

        // Propagate to child hotels (parent→child propagation)
        $childIds = DB::table('hotels')
            ->where('parent_hotel_id', $hotel->id)
            ->pluck('id');

        foreach ($childIds as $childId) {
            $childRow = DB::table('modules')
                ->where('hotel_id', $childId)
                ->where('slug', $module)
                ->first();

            if ($childRow) {
                DB::table('modules')
                    ->where('hotel_id', $childId)
                    ->where('slug', $module)
                    ->update(['is_enabled' => $newStatus, 'updated_at' => now()]);
            } else {
                // Upsert: insert module row for child if it doesn't exist yet
                DB::table('modules')->insert([
                    'hotel_id'    => $childId,
                    'slug'        => $module,
                    'name'        => $row->name,
                    'description' => $row->description,
                    'is_enabled'  => $newStatus,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $propagated = count($childIds);

        return response()->json([
            'success'    => true,
            'module'     => $module,
            'active'     => $newStatus,
            'propagated' => $propagated,
            'message'    => "Module '{$module}' " . ($newStatus ? 'enabled' : 'disabled') . " for {$hotel->name}" . ($propagated > 0 ? " and {$propagated} child hotel(s)." : '.'),
        ]);
    }
}
