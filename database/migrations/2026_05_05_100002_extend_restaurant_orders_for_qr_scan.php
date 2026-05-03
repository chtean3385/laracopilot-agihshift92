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

        // Allow guest room-QR orders that don't have a restaurant table.
        // Driver-portable: emit the right ALTER per database engine.
        match (DB::getDriverName()) {
            'pgsql'  => DB::statement('ALTER TABLE restaurant_orders ALTER COLUMN table_id DROP NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE restaurant_orders MODIFY table_id BIGINT UNSIGNED NULL'),
            'sqlite' => null, // SQLite ignores NOT NULL changes via raw ALTER; new inserts with null work in practice.
            default  => null,
        };
    }

    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $t) {
            $t->dropColumn([
                'source', 'approval_status', 'room_number',
                'guest_name', 'guest_phone', 'guest_notes', 'cancellation_reason',
            ]);
        });
        // table_id is left nullable on rollback — restoring NOT NULL would
        // either error on existing null rows or require an FK-unsafe back-fill.
    }
};
