<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\HotelWelcomeMail;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
                'hotels.billing_cycle', 'hotels.custom_monthly_price', 'hotels.custom_yearly_price',
                'hotels.max_rooms', 'hotels.max_users',
                'hotels.created_at', 'hotels.trial_ends_at', 'hotels.plan_expires_at',
            )
            ->selectRaw('(SELECT COUNT(*) FROM rooms WHERE rooms.hotel_id = hotels.id) as room_count')
            ->selectRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.hotel_id = hotels.id) as booking_count')
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

        // Fetch all other hotels this admin also manages (for the right-side panel)
        $relatedHotels = collect();
        if ($hotelAdmin) {
            $relatedHotels = DB::table('hotel_users')
                ->join('hotels', 'hotels.id', '=', 'hotel_users.hotel_id')
                ->where('hotel_users.user_id', $hotelAdmin->id)
                ->where('hotel_users.hotel_id', '!=', $id)
                ->select('hotels.id', 'hotels.name', 'hotels.plan', 'hotels.status')
                ->orderBy('hotels.name')
                ->get();
        }

        return view('platform.hotels.edit', compact('hotel', 'plans', 'hotelAdmin', 'hotelUsers', 'relatedHotels'));
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
                ['slug' => 'whatsapp',        'name' => 'WhatsApp Automation', 'description' => 'Automated WhatsApp messages.',            'is_enabled' => false],
                ['slug' => 'payment_links',   'name' => 'Payment Links',       'description' => 'Generate UPI QR codes and payment links.', 'is_enabled' => false],
                ['slug' => 'pathik',          'name' => 'Pathik Autofill',     'description' => 'Gujarat Pathik portal autofill.',          'is_enabled' => false],
                ['slug' => 'channel_manager', 'name' => 'OTA Channel Manager', 'description' => 'Sync with OTA platforms.',                 'is_enabled' => false],
            ] as $m) {
                DB::table('modules')->insert(array_merge($m, ['hotel_id' => $newHotelId, 'created_at' => now(), 'updated_at' => now()]));
            }

            $this->provisionRoles($newHotelId);

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
        ]);

        DB::table('hotels')->where('id', $id)->update([
            'name'                 => $data['name'],
            'email'                => $data['email'] ?? null,
            'phone'                => $data['phone'] ?? null,
            'address'              => $data['address'] ?? null,
            'plan'                 => $data['plan'],
            'billing_cycle'        => $data['billing_cycle'],
            'custom_monthly_price' => $data['custom_monthly_price'] ?: null,
            'custom_yearly_price'  => $data['custom_yearly_price'] ?: null,
            'max_rooms'            => $data['max_rooms'],
            'max_users'            => $data['max_users'],
            'status'               => $data['status'],
            'admin_notes'          => $data['admin_notes'] ?? null,
            'updated_at'           => now(),
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
