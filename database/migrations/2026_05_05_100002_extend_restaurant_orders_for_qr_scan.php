<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $t) {
            if (!Schema::hasColumn('restaurant_orders', 'source')) {
                $t->string('source', 20)->default('staff')->after('booking_id');
            }
            if (!Schema::hasColumn('restaurant_orders', 'approval_status')) {
                $t->string('approval_status', 20)->nullable()->after('source');
            }
            if (!Schema::hasColumn('restaurant_orders', 'room_number')) {
                $t->string('room_number', 20)->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('restaurant_orders', 'guest_name')) {
                $t->string('guest_name', 100)->nullable()->after('room_number');
            }
            if (!Schema::hasColumn('restaurant_orders', 'guest_phone')) {
                $t->string('guest_phone', 30)->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('restaurant_orders', 'guest_notes')) {
                $t->text('guest_notes')->nullable()->after('guest_phone');
            }
            if (!Schema::hasColumn('restaurant_orders', 'cancellation_reason')) {
                $t->text('cancellation_reason')->nullable()->after('guest_notes');
            }
        });

        // Allow guest orders that arrive without a chosen table.
        // Project DB is PostgreSQL (Neon) — use PG syntax. Wrap with driver
        // check so this is at least a clean no-op on other drivers.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE restaurant_orders ALTER COLUMN table_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $t) {
            $t->dropColumn([
                'source', 'approval_status', 'room_number',
                'guest_name', 'guest_phone', 'guest_notes', 'cancellation_reason',
            ]);
        });

        // NOTE: We intentionally do NOT restore NOT NULL on table_id here.
        // Forcing NOT NULL would either error (when null guest-QR rows exist)
        // or require an unsafe `UPDATE ... SET table_id = 0`, which would
        // violate the FK to restaurant_tables. The column is left nullable
        // on rollback, which is safe — no data is destroyed and the schema
        // is still functional for the previous (staff-only) flow.
    }
};
