<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Seed default hotel from existing settings (upgrade path) ──
        $hotelId = null;

        if (Schema::hasTable('settings') && Schema::hasTable('hotels')) {
            $settings = DB::table('settings')->first();
            if ($settings && DB::table('hotels')->count() === 0) {
                $name = $settings->resort_name ?? 'Default Hotel';
                $slug = Str::slug($name) ?: 'default-hotel';
                // Ensure unique slug
                $base = $slug;
                $i    = 1;
                while (DB::table('hotels')->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $hotelId = DB::table('hotels')->insertGetId([
                    'name'       => $name,
                    'slug'       => $slug,
                    'address'    => $settings->address    ?? null,
                    'phone'      => $settings->phone      ?? null,
                    'email'      => $settings->email      ?? null,
                    'status'     => 'active',
                    'plan'       => 'basic',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── Step 2: Add hotel_id to all data tables ──
        $tables = [
            'customers', 'rooms', 'bookings', 'payments', 'invoices',
            'activity_logs', 'roles', 'settings',
            'whatsapp_configs', 'whatsapp_templates',
            'pathik_configs', 'channel_manager_configs',
            'channel_room_mappings', 'channel_bookings',
            'payment_link_configs',
        ];

        foreach ($tables as $tbl) {
            if (!Schema::hasTable($tbl)) continue;
            if (Schema::hasColumn($tbl, 'hotel_id')) continue;

            Schema::table($tbl, function (Blueprint $table) {
                $table->unsignedBigInteger('hotel_id')->nullable()->after('id');
                $table->index('hotel_id');
            });
        }

        // modules table needs special handling: drop old unique on slug, add composite
        if (Schema::hasTable('modules') && !Schema::hasColumn('modules', 'hotel_id')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->unsignedBigInteger('hotel_id')->nullable()->after('id');
                $table->index('hotel_id');
            });
            // Drop old unique index on slug and recreate composite
            try {
                Schema::table('modules', function (Blueprint $table) {
                    $table->dropUnique(['slug']);
                    $table->unique(['hotel_id', 'slug']);
                });
            } catch (\Throwable $e) {
                // SQLite may not support this — ignore
            }
        }

        // roles table: drop single-column unique on name, add composite (hotel_id, name)
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'hotel_id')) {
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->dropUnique(['name']);
                });
            } catch (\Throwable $e) {
                // index may not exist by this name
            }
            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->unique(['hotel_id', 'name']);
                });
            } catch (\Throwable $e) {
                // composite unique may already exist
            }
        }

        // ── Step 3: Back-fill all existing rows with hotel_id ──
        if ($hotelId) {
            $allTables = array_merge($tables, ['modules']);
            foreach ($allTables as $tbl) {
                if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'hotel_id')) {
                    DB::table($tbl)->whereNull('hotel_id')->update(['hotel_id' => $hotelId]);
                }
            }

            // ── Step 4: Migrate existing users into hotel_users ──
            if (Schema::hasTable('users') && Schema::hasTable('hotel_users')) {
                $users = DB::table('users')->where('is_super_admin', false)->get();
                foreach ($users as $user) {
                    $exists = DB::table('hotel_users')
                        ->where('hotel_id', $hotelId)
                        ->where('user_id', $user->id)
                        ->exists();
                    if (!$exists) {
                        DB::table('hotel_users')->insert([
                            'hotel_id'       => $hotelId,
                            'user_id'        => $user->id,
                            'role'           => $user->role ?? 'Admin',
                            'is_hotel_admin' => (($user->role ?? '') === 'Admin') ? 1 : 0,
                            'status'         => 'active',
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'customers', 'rooms', 'bookings', 'payments', 'invoices',
            'activity_logs', 'roles', 'settings', 'modules',
            'whatsapp_configs', 'whatsapp_templates',
            'pathik_configs', 'channel_manager_configs',
            'channel_room_mappings', 'channel_bookings',
            'payment_link_configs',
        ];

        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'hotel_id')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropIndex(['hotel_id']);
                    $table->dropColumn('hotel_id');
                });
            }
        }
    }
};
